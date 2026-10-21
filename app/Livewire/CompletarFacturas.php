<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\TipoGasto;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Actions\RegistrarCompraElectronica;
use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use App\Services\ClasificadorComprobantesService;
use App\Services\ClasificadorProductos;
use App\Services\MedioPago;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Registrar facturas de compra desde su XML, en un solo lugar.
 *
 * Una tarea, de principio a fin: se suben los XML (varios o un .zip) y lo que no
 * entra solo queda aquí mismo, factura por factura, con sus productos a la vista.
 * Lo que falta se dice en la misma línea —qué es un producto nuevo, cómo se pagó—
 * y un botón la registra. Antes eso estaba repartido en tres pantallas de dos
 * paneles; ahora las tres usan este componente.
 *
 * Cada producto se dice una sola vez: queda recordado para las próximas facturas
 * de ese proveedor (ProductoProveedor).
 */
class CompletarFacturas extends Component
{
    use WithFileUploads;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $xmls = [];

    /** Cómo se pagaron las que se suben (formato MedioPago). */
    public string $pago = 'credito';

    /** producto_proveedor_id => 'item:12' | 'gasto:5' | 'nuevo:insumo' | 'nuevo:materia_prima' */
    public array $producto = [];

    /** producto_proveedor_id => propuesta del clasificador (valor, etiqueta, motivo, confianza) */
    public array $propuestas = [];

    /** compra_id => 'credito' | 'efectivo:2' | ... (solo si la compra no lo sabe) */
    public array $pagoCompra = [];

    /** Lo que pasó con la última subida, para decirlo en la pantalla. */
    public ?array $resultado = null;

    /** Hasta 80 por subida: cada XML es una lectura en el servicio del SRI y la web corta a los 30 s. */
    private const POR_SUBIDA = 80;

    /** Una subida nueva: el resultado vuelve a cero y cada grupo de archivos le suma. */
    public function empezarSubida(): void
    {
        $this->resultado = ['registradas' => 0, 'esperan' => 0, 'fallos' => [], 'resto' => 0];
        $this->resetErrorBag();
    }

    /**
     * Se procesa apenas llega cada grupo de archivos: elegir es la intención.
     * El navegador los manda de 15 en 15 porque PHP solo acepta 20 por petición
     * y descarta el resto sin avisar.
     */
    public function updatedXmls(): void
    {
        $this->validate(['xmls.*' => 'file|max:20480|mimes:xml,zip,txt']);

        $empresaId = Filament::getTenant()->id;
        $servicio = app(ClasificadorComprobantesService::class);
        $contenidos = $this->leerArchivos();
        $resto = max(count($contenidos) - self::POR_SUBIDA, 0);
        $cuenta = $this->resultado && ! isset($this->resultado['texto'])
            ? $this->resultado
            : ['registradas' => 0, 'esperan' => 0, 'fallos' => [], 'resto' => 0];

        foreach (array_slice($contenidos, 0, self::POR_SUBIDA, true) as $nombre => $xml) {
            $r = $servicio->registrarDesdeXml($empresaId, $xml, $this->pago, auth()->id());

            // Sin el listado del portal, la factura entra igual si el SRI todavía la tiene.
            if (! ($r['ok'] ?? false) && str_contains($r['error'] ?? '', 'no está en el listado')) {
                $r = $this->sinListado($xml);
            }

            if (! ($r['ok'] ?? false)) {
                $cuenta['fallos'][] = ($r['numero'] ?? $nombre) . ': ' . $r['error'];
            } elseif (($r['estado'] ?? '') === 'pendiente') {
                $cuenta['esperan']++;
            } else {
                $cuenta['registradas']++;
            }
        }

        $cuenta['resto'] += $resto;
        $this->resultado = $cuenta;
        $this->xmls = [];
    }

    /** Registra todas las facturas que ya tienen elegido qué es cada producto nuevo. */
    public function registrarTodas(): void
    {
        $hechas = 0;
        foreach ($this->pendientes()->get() as $compra) {
            $listas = $compra->items->every(fn ($l) => ! $l->productoProveedor || $l->productoProveedor->estaConfigurado()
                || ! empty($this->producto[$l->productoProveedor->id]));
            if ($listas && $this->registrar($compra->id, false)) {
                $hechas++;
            }
        }

        $this->resultado = ['registradas' => $hechas, 'esperan' => 0, 'fallos' => [], 'resto' => 0,
                            'texto' => "{$hechas} " . ($hechas === 1 ? 'factura registrada' : 'facturas registradas') . ' con su asiento.'];
    }

    /** Registra una factura con lo que se eligió en su fila: productos nuevos y, si falta, el pago. */
    public function registrar(int $compraId, bool $avisar = true): bool
    {
        $compra = app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)
            ->with('items.productoProveedor')->findOrFail($compraId);
        $confirmar = app(ConfirmarCompraElectronica::class);

        try {
            foreach ($compra->items as $linea) {
                $p = $linea->productoProveedor;
                if (! $p || $p->estaConfigurado()) {
                    continue;
                }

                [$tipo, $valor] = array_pad(explode(':', (string) ($this->producto[$p->id] ?? '')), 2, null);
                if (! $tipo) {
                    throw ValidationException::withMessages(['producto' => "Falta decir qué es «{$p->descripcion}»."]);
                }

                // Un bien que el inventario no tenía: se crea aquí, con el nombre de la factura.
                if ($tipo === 'nuevo') {
                    $valor = InventoryItem::withoutGlobalScopes()->create([
                        'empresa_id' => $compra->empresa_id, 'nombre' => $p->descripcion,
                        'type' => $valor === 'materia_prima' ? 'materia_prima' : 'insumo',
                        'purchase_price' => (float) $p->ultimo_precio, 'activo' => true,
                    ])->id;
                    $tipo = 'item';
                }

                $confirmar->producto($p, $tipo === 'item' ? (int) $valor : null, $tipo === 'gasto' ? (int) $valor : null, auth()->id());
            }

            $compra->refresh();
            if ($compra->requiere_forma_pago) {
                [$forma, $medio] = array_pad(explode(':', (string) ($this->pagoCompra[$compra->id] ?? 'credito'), 2), 2, null);
                $confirmar->formaPago($compra, $forma, $medio ? (int) $medio : null);
            } else {
                $confirmar->intentar($compra);
            }
        } catch (ValidationException $e) {
            $this->addError("compra.{$compraId}", collect($e->errors())->flatten()->first());

            return false;
        }

        $compra->refresh();
        if ($compra->status !== Purchase::CONFIRMADO) {
            $this->addError("compra.{$compraId}", $compra->error_contable_msg ?: 'No se pudo registrar; revisa lo elegido.');

            return false;
        }

        if ($avisar) {
            $this->resultado = ['registradas' => 1, 'esperan' => 0, 'fallos' => [], 'resto' => 0,
                                'texto' => "Factura {$compra->numero_factura} registrada con su asiento."];
        }

        return true;
    }

    private function pendientes()
    {
        return app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)
            ->with(['supplier:id,nombre,numero_identificacion', 'items.productoProveedor', 'items.inventoryItem:id,nombre'])
            ->orderBy('date')->limit(60);
    }

    /**
     * Lo que el ERP propone para cada producto nuevo, ya elegido en su selector.
     * Quien registra solo confirma o cambia; el motivo queda a la vista.
     */
    private function proponer($compras): void
    {
        $clasificador = app(ClasificadorProductos::class);

        foreach ($compras as $compra) {
            foreach ($compra->items as $linea) {
                $p = $linea->productoProveedor;
                if (! $p || $p->estaConfigurado() || isset($this->propuestas[$p->id])) {
                    continue;
                }

                $this->propuestas[$p->id] = $clasificador->proponer(
                    $compra->empresa_id, (string) $p->descripcion, $compra->supplier?->numero_identificacion,
                );
                $this->producto[$p->id] ??= $this->propuestas[$p->id]['valor'];
            }
        }
    }

    public function render()
    {
        $empresa = Filament::getTenant();

        $compras = $this->pendientes()->get();
        $this->proponer($compras);

        return view('livewire.completar-facturas', [
            'compras'    => $compras,
            'medios'     => MedioPago::opciones($empresa->id),
            'items'      => InventoryItem::withoutGlobalScopes()->where('empresa_id', $empresa->id)->where('activo', true)
                ->orderBy('type')->orderBy('nombre')->get()->groupBy('type')->map(fn ($g) => $g->pluck('nombre', 'id')),
            'tiposGasto' => TipoGasto::where('activo', true)
                ->where(fn ($q) => $q->whereNull('empresa_id')->orWhere('empresa_id', $empresa->id))
                ->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    /** Un XML que no está en el listado del portal: entra si el SRI confirma que está autorizado. */
    private function sinListado(string $xml): array
    {
        $r = app(RegistrarCompraElectronica::class)->desdeXml(Filament::getTenant(), $xml, Purchase::ORIGEN_PORTAL);

        return ($r['ok'] ?? false)
            ? ['ok' => true, 'estado' => $r['estado']]
            : ['ok' => false, 'error' => $r['mensaje'] . ' Si es vieja, sube primero el listado .txt del mes.'];
    }

    /**
     * Los XML de lo subido: los sueltos tal cual y los de dentro de un .zip.
     *
     * @return array<string, string> nombre => contenido
     */
    private function leerArchivos(): array
    {
        $xmls = [];

        foreach ($this->xmls as $archivo) {
            $ruta = $archivo->getRealPath();

            if (strtolower($archivo->getClientOriginalExtension()) === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($ruta) === true) {
                    for ($i = 0; $i < min($zip->numFiles, 500); $i++) {
                        $nombre = $zip->getNameIndex($i);
                        if (str_ends_with(strtolower($nombre), '.xml')) {
                            $xmls[basename($nombre)] = $zip->getFromIndex($i);
                        }
                    }
                    $zip->close();
                }
            } else {
                $xmls[$archivo->getClientOriginalName()] = file_get_contents($ruta);
            }
        }

        return $xmls;
    }
}
