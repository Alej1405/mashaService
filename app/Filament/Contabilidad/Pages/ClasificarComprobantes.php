<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\ComprobanteSri;
use App\Models\InventoryItem;
use App\Models\TipoGasto;
use App\Filament\Contabilidad\Acciones\ImportarDelPortal;
use App\Services\ClasificadorComprobantesService;
use App\Services\MedioPago;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Decir qué fue cada factura que llegó del portal.
 *
 * Una pantalla, una tarea: vaciar la bandeja. Lo que el ERP ya tenía
 * registrado se concilió solo y no aparece aquí; lo que queda es lo que nadie
 * registró en su momento, y hasta que se clasifique suma al 104 sin existir en
 * la contabilidad.
 *
 * Al clasificar se crea el documento y su asiento: una compra que mueve el
 * kardex, un gasto con su IVA como crédito. Por eso hay dos puertas al
 * inventario —bodega y esta— y es la segunda la que salva los meses viejos.
 *
 * Todo el flujo vive aquí: se sube el archivo del portal, se dice qué fue cada
 * factura y cómo se pagó, y al registrarla quedan el documento, su asiento y su
 * autorización. Cuando un mes queda vacío, su ATS está completo.
 */
class ClasificarComprobantes extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'Clasificar comprobantes';
    protected static ?string $navigationGroup = 'Día a día';
    protected static ?string $title           = 'Clasificar comprobantes del portal';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.contabilidad.clasificar-comprobantes';

    /**
     * comprobante_id => ['que' => 'item:12'|'gasto:5', 'cantidad', 'no_deducible', 'pago']
     *
     * Una sola decisión por factura. Si entra a un ítem del inventario, el tipo
     * —materia prima, insumo, activo fijo— ya lo dijo el formulario de bodega y
     * no se vuelve a preguntar. Lo único que el contador clasifica es lo que no
     * está registrado en ninguna parte: los gastos.
     */
    public array $eleccion = [];

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();

        if (! $empresa) {
            return null;
        }

        $n = app(ClasificadorComprobantesService::class)->pendientes($empresa->id);

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        $servicio = app(ClasificadorComprobantesService::class);

        $this->eleccion = $this->pendientes()
            ->mapWithKeys(function (ComprobanteSri $c) use ($servicio) {
                // Lo que se hizo con ese proveedor la última vez
                $propuesta = $servicio->proponer($c);

                $que = match (true) {
                    (bool) $propuesta['inventory_item_id'] => 'item:' . $propuesta['inventory_item_id'],
                    (bool) $propuesta['tipo_gasto_id']     => 'gasto:' . $propuesta['tipo_gasto_id'],
                    default                                => '',
                };

                return [$c->id => [
                    'que'          => $que,
                    'cantidad'     => 1,
                    'no_deducible' => $propuesta['destino'] === 'no_deducible',
                    'pago'         => $propuesta['pago'] ?? 'credito',
                ]];
            })->all();

        // Las facturas emitidas que no están en el ERP entran como venta.
        foreach ($this->ventasPendientes() as $c) {
            $this->eleccion[$c->id] = ['que' => '', 'cantidad' => 1, 'no_deducible' => false, 'pago' => 'credito'];
        }
    }

    private function ventasPendientes()
    {
        return ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'emitido')
            ->sinClasificar()
            ->orderBy('fecha_emision')
            ->limit(60)
            ->get();
    }

    private function pendientes()
    {
        return ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'recibido')
            ->sinClasificar()
            ->orderBy('fecha_emision')
            ->orderBy('identificacion')
            ->limit(120)
            ->get();
    }

    /** De la única elección de la fila a lo que el servicio necesita. */
    private function datosDe(int $id): array
    {
        $e = $this->eleccion[$id] ?? [];
        [$tipo, $valor] = array_pad(explode(':', (string) ($e['que'] ?? '')), 2, null);

        $pago = (string) ($e['pago'] ?? 'credito');

        if ($tipo === 'item') {
            // El destino sale del ítem: el servicio lo resuelve.
            return ['inventory_item_id' => (int) $valor, 'cantidad' => (float) ($e['cantidad'] ?? 1), 'pago' => $pago];
        }

        if ($tipo === 'gasto') {
            return [
                'destino'       => ($e['no_deducible'] ?? false) ? 'no_deducible' : 'gasto',
                'tipo_gasto_id' => (int) $valor,
                'pago'          => $pago,
            ];
        }

        return [];
    }

    /** Clasifica una factura. */
    public function clasificar(int $id): void
    {
        $c = ComprobanteSri::where('empresa_id', Filament::getTenant()->id)->findOrFail($id);
        $r = app(ClasificadorComprobantesService::class)
            ->clasificar($c, $this->datosDe($id), auth()->id());

        $this->cargar();

        if (! ($r['ok'] ?? false)) {
            Notification::make()->title('No se pudo registrar')->body($r['error'])->danger()->send();

            return;
        }

        Notification::make()->title('Registrado')->body(ucfirst($r['detalle']))->success()->send();
    }

    /**
     * Aplica la misma clasificación a todas las facturas pendientes de ese
     * proveedor. Con veintinueve del mismo emisor, decirlo una vez basta.
     */
    public function clasificarProveedor(string $identificacion, int $desdeId): void
    {
        $r = app(ClasificadorComprobantesService::class)->clasificarProveedor(
            Filament::getTenant()->id,
            $identificacion,
            $this->datosDe($desdeId),
            auth()->id(),
        );

        $this->cargar();

        Notification::make()
            ->title("{$r['hechos']} facturas registradas")
            ->body($r['fallos']
                ? 'No se pudo con ' . count($r['fallos']) . ': ' . implode(' · ', array_slice($r['fallos'], 0, 3))
                : 'Todas las de ese proveedor quedaron con su asiento.')
            ->{$r['fallos'] ? 'warning' : 'success'}()
            ->persistent()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportarDelPortal::make(fn () => $this->cargar()),

            Action::make('ayuda')
                ->label('Qué es esto')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('Por qué hay facturas aquí')
                ->modalDescription('El listado que se baja del portal dice quién facturó y por '
                    . 'cuánto, pero no qué se compró. Hasta que lo digas, esas facturas suman al '
                    . 'formulario 104 y no existen en la contabilidad: el balance y la declaración '
                    . 'no cuadran, y el mes no se puede cerrar. Lo que el ERP ya tenía registrado '
                    . 'no aparece aquí, porque se concilió solo por el número de factura. Al registrar '
                    . 'cada una se consulta su XML en el SRI por la clave de acceso, y la clave queda '
                    . 'como número de autorización: es lo que pide el ATS.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Entendido'),
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $pendientes = $this->pendientes();

        return [
            'empresa'    => $empresa,
            'comprobantes' => $pendientes,
            'total'      => app(ClasificadorComprobantesService::class)->pendientes($empresa->id),
            'ventas'     => $this->ventasPendientes(),
            'medios'     => MedioPago::opciones($empresa->id),
            'mediosVenta' => MedioPago::opciones($empresa->id, true),
            'meses'      => $this->meses($empresa->id),
            'urlDeclaraciones' => Declaraciones::getUrl(),
            'porProveedor' => $pendientes->groupBy('identificacion')->map->count(),
            'destinos'   => ClasificadorComprobantesService::DESTINOS,
            'tiposGasto' => TipoGasto::where('activo', true)
                ->where(fn ($q) => $q->whereNull('empresa_id')->orWhere('empresa_id', $empresa->id))
                ->orderBy('nombre')->pluck('nombre', 'id'),
            // Agrupados por su tipo, que es la clasificación que ya existe.
            'items'      => InventoryItem::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)->where('activo', true)
                ->orderBy('type')->orderBy('nombre')
                ->get()
                ->groupBy('type')
                ->map(fn ($g) => $g->pluck('nombre', 'id')),
        ];
    }

    /**
     * Cómo va cada mes para el ATS: cuántas facturas del portal faltan por
     * registrar. Un mes en cero tiene su ATS completo.
     *
     * @return \Illuminate\Support\Collection<int, object{mes: string, total: int, pendientes: int}>
     */
    private function meses(int $empresaId)
    {
        return ComprobanteSri::where('empresa_id', $empresaId)
            ->selectRaw("to_char(fecha_emision, 'YYYY-MM') as mes, count(*) as total, "
                . 'sum(case when conciliado_con is null and destino is null then 1 else 0 end) as pendientes')
            ->groupBy('mes')->orderByDesc('mes')->limit(6)->get();
    }
}
