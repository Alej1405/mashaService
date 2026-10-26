<?php

namespace App\Livewire;

use App\Models\ComprobanteSri;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\TipoGasto;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Actions\RegistrarCompraElectronica;
use App\Modules\Compras\Actions\ResolverFormaPago;
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
 * Una tarea, de principio a fin: se suben los XML (varios o un .zip) y nada se
 * registra todavía. Cada factura queda aquí para auditarla: cómo dice su XML que
 * se pagó y qué propone el ERP para cada producto. Se registran una por una
 * (cambiando lo que haga falta) o todas juntas tal como dicen sus XML. Una forma
 * de pago para varias facturas solo con un proveedor elegido: es una decisión
 * consciente sobre las facturas de ese proveedor, no sobre toda la bandeja.
 *
 * Cada producto se dice una sola vez: queda recordado para las próximas facturas
 * de ese proveedor (ProductoProveedor).
 */
class CompletarFacturas extends Component
{
    use WithFileUploads;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $xmls = [];

    /** producto_proveedor_id => 'item:12' | 'gasto:5' | 'nuevo:insumo' | 'nuevo:materia_prima' */
    public array $producto = [];

    /** producto_proveedor_id => propuesta del clasificador (valor, etiqueta, motivo, confianza) */
    public array $propuestas = [];

    /** compra_id => tipo_gasto_id: toda la factura a un mismo tipo de gasto (p. ej. una factura médica). */
    public array $gastoTodas = [];

    /** compra_id => 'credito' | 'efectivo:2' | ... cuando se cambia lo que dice el XML */
    public array $pagoCompra = [];

    /** forma => medio ('efectivo' => 'efectivo:2'): de qué caja o banco salió lo que el XML dice */
    public array $cuentaPor = [];

    /** Filtros de la bandeja que contiene este bloque (RUC del proveedor, año y mes); null = todos. */
    #[\Livewire\Attributes\Reactive]
    public ?string $proveedor = null;

    #[\Livewire\Attributes\Reactive]
    public ?int $anio = null;

    #[\Livewire\Attributes\Reactive]
    public ?int $mes = null;

    /** Con un proveedor elegido: una sola forma de pago para todas sus facturas. */
    public string $pagoTodas = '';

    /** false = solo el bloque de subir XML (en «Falta el detalle»); la lista vive en «Listas para registrar». */
    public bool $conLista = true;

    /** Lo que pasó con la última subida, para decirlo en la pantalla. */
    public ?array $resultado = null;

    /** Tabla 24 del SRI, como se lee en la fila. */
    private const SRI = [
        '01' => 'sin sistema financiero (efectivo)', '15' => 'compensación de deudas', '16' => 'tarjeta de débito',
        '17' => 'dinero electrónico', '18' => 'tarjeta prepago', '19' => 'tarjeta de crédito',
        '20' => 'con sistema financiero (transferencia)', '21' => 'endoso de títulos',
    ];

    private const FORMAS = [
        'efectivo' => 'En efectivo', 'transferencia' => 'Por el sistema financiero', 'tarjeta' => 'Con tarjeta de crédito',
    ];

    /** Hasta 80 por subida: cada XML es una lectura en el servicio del SRI y la web corta a los 30 s. */
    private const POR_SUBIDA = 80;

    /** El pago elegido arriba se pone en cada factura del proveedor; se puede cambiar fila a fila. */
    public function updatedPagoTodas(): void
    {
        foreach ($this->pendientes()->pluck('purchases.id') as $id) {
            $this->pagoCompra[$id] = $this->pagoTodas;
        }
    }

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
            $r = $servicio->registrarDesdeXml($empresaId, $xml, null, auth()->id());

            // Sin el listado del portal, la factura entra igual si el SRI todavía la tiene.
            if (! ($r['ok'] ?? false) && str_contains($r['error'] ?? '', 'no está en el listado')) {
                $r = $this->sinListado($xml);
            }

            if (! ($r['ok'] ?? false)) {
                $cuenta['fallos'][] = ($r['numero'] ?? $nombre) . ': ' . $r['error'];
            } elseif (($r['estado'] ?? '') === 'pendiente') {
                $cuenta['esperan']++;
            } else {
                $cuenta['registradas']++; // ya estaba en el ERP: el XML queda de respaldo
            }
        }

        $cuenta['resto'] += $resto;
        $this->resultado = $cuenta;
        $this->xmls = [];
    }

    /**
     * Registra todas tal como dicen sus XML: la forma de pago de cada una y lo que
     * se ve elegido en cada producto. Las que no tienen todo (un producto sin decir,
     * o un pago sin caja o banco) se quedan, cada una con su motivo.
     */
    public function registrarTodas(): void
    {
        $hechas = 0;
        $quedan = 0;
        foreach ($this->pendientes()->get() as $compra) {
            $this->registrar($compra->id, false) ? $hechas++ : $quedan++;
        }

        $texto = "{$hechas} " . ($hechas === 1 ? 'factura registrada' : 'facturas registradas') . ' con su asiento.';
        $this->resultado = ['registradas' => $hechas, 'esperan' => 0, 'fallos' => [], 'resto' => 0,
                            'texto' => $quedan ? "{$texto} {$quedan} siguen abajo con lo que les falta." : $texto];
    }

    /**
     * Una factura con el RUC de la empresa que no es de la empresa (una compra
     * personal), en un solo paso: se quita el borrador, que no tiene asiento ni
     * kardex, y la factura queda como personal: sin gasto ni asiento, solo su IVA.
     */
    public function noEsDeLaEmpresa(int $compraId): void
    {
        $compra = app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)->findOrFail($compraId);
        $c = ComprobanteSri::where('empresa_id', $compra->empresa_id)->where('conciliado_con', 'purchase')
            ->where('conciliado_id', $compra->id)->first();
        $servicio = app(ClasificadorComprobantesService::class);

        if (! $c) {
            $this->addError("compra.{$compraId}", 'Esta factura no vino del portal: no hay comprobante que sacar.');

            return;
        }

        try {
            $r = \DB::transaction(function () use ($compra, $c, $servicio) {
                $c->update(['conciliado_con' => null, 'conciliado_id' => null, 'pago' => null]);
                $compra->items()->delete();
                $compra->delete();

                $r = $servicio->marcarPersonal($c->refresh(), auth()->id());
                if (! ($r['ok'] ?? false)) {
                    throw new \RuntimeException($r['error'] ?? 'No se pudo sacar.');
                }

                return $r;
            });
        } catch (\RuntimeException $e) {
            $this->addError("compra.{$compraId}", $e->getMessage());

            return;
        }

        $this->resultado = ['registradas' => 0, 'esperan' => 0, 'fallos' => [], 'resto' => 0,
                            'texto' => ucfirst($r['detalle']) . '.'];
    }

    /** Cómo se pagó: lo que se eligió en la fila o, si no, lo que dice el XML. */
    private function pagoDe(Purchase $compra): ?string
    {
        return ($this->pagoCompra[$compra->id] ?? '') ?: app(ResolverFormaPago::class)
            ->sugerida($compra->empresa_id, $compra->forma_pago_sri, $this->cuentaPor);
    }

    /** Registra una factura con lo que se eligió en su fila: productos nuevos y, si falta, el pago. */
    /** Pone el mismo tipo de gasto en todas las líneas nuevas de esa factura. */
    public function updatedGastoTodas($tipoGastoId, $compraId): void
    {
        if (! $tipoGastoId) {
            return;
        }

        $compra = app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)
            ->with('items.productoProveedor')->findOrFail((int) $compraId);

        foreach ($compra->items as $linea) {
            if ($linea->productoProveedor && ! $linea->productoProveedor->estaConfigurado()) {
                $this->producto[$linea->productoProveedor->id] = "gasto:{$tipoGastoId}";
            }
        }
    }

    public function registrar(int $compraId, bool $avisar = true): bool
    {
        $compra = app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)
            ->with('items.productoProveedor')->findOrFail($compraId);
        $confirmar = app(ConfirmarCompraElectronica::class);

        try {
            $pago = $this->pagoDe($compra);
            if (! $pago) {
                throw ValidationException::withMessages(['pago' => $compra->forma_pago_sri
                    ? 'Elige arriba de qué caja o cuenta salió el pago, o cámbialo en esta factura.'
                    : 'El XML no dice cómo se pagó: elígelo en esta factura.']);
            }

            foreach ($compra->items as $linea) {
                $p = $linea->productoProveedor;
                if (! $p || $p->estaConfigurado()) {
                    continue;
                }

                [$tipo, $valor] = array_pad(explode(':', (string) ($this->producto[$p->id] ?? '')), 2, null);
                if (! $tipo) {
                    throw ValidationException::withMessages(['producto' => "Falta decir qué es «{$p->descripcion}»."]);
                }

                // Un bien que el inventario no tenía: se crea aquí, con el nombre de la factura sin abreviaturas.
                if ($tipo === 'nuevo') {
                    $valor = InventoryItem::withoutGlobalScopes()->create([
                        'empresa_id' => $compra->empresa_id, 'nombre' => ClasificadorProductos::nombreLegible((string) $p->descripcion),
                        'type' => $valor === 'materia_prima' ? 'materia_prima' : 'insumo',
                        'purchase_price' => (float) $p->ultimo_precio, 'activo' => true,
                    ])->id;
                    $tipo = 'item';
                }

                $confirmar->producto($p, $tipo === 'item' ? (int) $valor : null, $tipo === 'gasto' ? (int) $valor : null, auth()->id());
            }

            // La forma de pago al final: es lo que la deja lista y genera el asiento.
            [$forma, $medio] = array_pad(explode(':', $pago, 2), 2, null);
            $confirmar->formaPago($compra->refresh(), $forma, $medio ? (int) $medio : null);
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
            ->when($this->proveedor, fn ($q) => $q->whereHas('supplier', fn ($q) => $q->where('numero_identificacion', $this->proveedor)))
            ->when($this->anio, fn ($q) => $q->whereYear('date', $this->anio))
            ->when($this->anio && $this->mes, fn ($q) => $q->whereMonth('date', $this->mes))
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

        $compras = $this->conLista ? $this->pendientes()->get() : collect();
        $this->proponer($compras);
        $formas = app(ResolverFormaPago::class);
        $medios = MedioPago::opciones($empresa->id);

        // Por cada tipo de pago que dicen los XML y que tiene más de una caja o banco: de cuál salió.
        $cuentasPorElegir = [];
        foreach ($compras->groupBy(fn ($c) => ResolverFormaPago::formaDelSri($c->forma_pago_sri)) as $forma => $grupo) {
            $opciones = collect($medios)->filter(fn ($e, $v) => str_starts_with($v, "{$forma}:"))->all();
            if ($forma && count($opciones) > 1) {
                $cuentasPorElegir[$forma] = ['etiqueta' => self::FORMAS[$forma] ?? $forma,
                                             'cuantas' => $grupo->count(), 'opciones' => $opciones];
            }
        }

        return view('livewire.completar-facturas', [
            'compras'    => $compras,
            'medios'     => $medios,
            'sugeridas'  => $compras->mapWithKeys(fn ($c) => [$c->id => $formas->sugerida($empresa->id, $c->forma_pago_sri, $this->cuentaPor)])->all(),
            'cuentasPorElegir' => $cuentasPorElegir,
            'formasSri'  => self::SRI,
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
