<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\ComprobanteSri;
use App\Filament\Contabilidad\Acciones\ImportarDelPortal;
use App\Services\ClasificadorComprobantesService;
use App\Services\MedioPago;
use App\Jobs\RegistrarComprobantesDelPortal;
use Illuminate\Support\Facades\Cache;
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
 * Todo el flujo vive aquí. Al subir el archivo del portal se trae del SRI el
 * detalle de cada factura recibida, y quedan dos grupos: las que lo tienen, listas
 * para elegir cómo se pagaron y registrarlas; y las que el SRI ya no entrega, que
 * esperan su XML. Sin detalle no se registra: no se sabe qué fue esa factura.
 * Una compra personal se saca con un clic desde cualquiera de los dos grupos.
 * Cuando un mes queda vacío, su ATS está completo.
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
     * comprobante_id => ['pago' => 'credito'|'efectivo:2'|…]
     *
     * El cobro de cada venta, y el pago de una compra personal cuando el 104 de su
     * mes ya la declaró (entra como gasto no deducible). Lo demás lo dice el XML.
     */
    public array $eleccion = [];

    /** Año que se trabaja en la bandeja; null = todos. Queda en la URL. */
    #[\Livewire\Attributes\Url]
    public ?int $anio = null;

    /** RUC del proveedor que se trabaja; null = todos. Queda en la URL. */
    #[\Livewire\Attributes\Url]
    public ?string $proveedor = null;

    /**
     * Qué parte de la bandeja se mira. Cada una es un estado del detalle:
     *   por_registrar · todavía no se trae su detalle del SRI
     *   esperan       · con su detalle: lista para elegir cómo se pagó y registrarla
     *   sin_detalle   · el SRI ya no la devuelve: espera su XML
     *   registradas   · con asiento; con su detalle o registrada a mano antes
     */
    #[\Livewire\Attributes\Url]
    public string $vista = 'esperan';

    public const VISTAS = ['por_registrar', 'esperan', 'sin_detalle', 'registradas'];

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

    /** Al cambiar de año o de vista, las elecciones se vuelven a armar para lo que se ve. */
    public function updatedAnio(): void
    {
        $this->cargar();
    }

    public function updatedProveedor(): void
    {
        $this->proveedor = $this->proveedor ?: null;
        $this->cargar();
    }

    public function verVista(string $vista): void
    {
        $this->vista = in_array($vista, self::VISTAS, true) ? $vista : 'esperan';
        $this->cargar();
    }

    /** Los de esta vista: con detalle por traer, o los que hay que decir a mano. */
    private function deLaVista($q)
    {
        $sin = ClasificadorComprobantesService::SIN_DETALLE;

        return $this->vista === 'sin_detalle'
            ? $q->whereIn('desglose', $sin)
            : $q->where(fn ($q) => $q->whereNull('desglose')->orWhereNotIn('desglose', $sin));
    }

    /**
     * Cuántos hay en cada vista, con el año elegido.
     *
     * @return array<string, int>
     */
    private function conteos(int $empresaId): array
    {
        $base = fn () => $this->delFiltro(ComprobanteSri::where('empresa_id', $empresaId));
        $sin = ClasificadorComprobantesService::SIN_DETALLE;
        $esperan = $this->esperanQuery($empresaId)->count();

        return [
            'por_registrar' => $base()->sinClasificar()
                ->where(fn ($q) => $q->whereNull('desglose')->orWhereNotIn('desglose', $sin))->count(),
            'sin_detalle'   => $base()->sinClasificar()->whereIn('desglose', $sin)->count(),
            'esperan'       => $esperan,
            'registradas'   => $base()->whereNotNull('conciliado_con')->count() - $esperan,
        ];
    }

    /** Registradas como compra que espera que se diga qué es un producto nuevo. */
    private function esperanQuery(int $empresaId)
    {
        return $this->delFiltro(ComprobanteSri::where('empresa_id', $empresaId)->where('conciliado_con', 'purchase'))
            ->whereIn('conciliado_id', \App\Models\Purchase::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->where('status', \App\Models\Purchase::BORRADOR)->select('id'));
    }

    /**
     * Las registradas con lo que se registró de cada una.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: array<int, array{tipo: string, con_detalle: bool, lineas: array}>}
     */
    private function registradas(int $empresaId): array
    {
        $comprobantes = $this->delFiltro(ComprobanteSri::where('empresa_id', $empresaId)->whereNotNull('conciliado_con'))
            ->whereNotIn('id', $this->esperanQuery($empresaId)->select('id'))
            ->orderByDesc('fecha_emision')->limit(120)->get();

        $ids = fn (string $tipo) => $comprobantes->where('conciliado_con', $tipo)->pluck('conciliado_id')->filter()->all();
        $compras = \App\Models\Purchase::withoutGlobalScopes()->with('items.inventoryItem')->whereIn('id', $ids('purchase'))->get()->keyBy('id');
        $gastos  = \App\Models\Gasto::with('lineas')->whereIn('id', $ids('gasto'))->get()->keyBy('id');
        $ventas  = \App\Models\Sale::withoutGlobalScopes()->with('items.inventoryItem')->whereIn('id', $ids('sale'))->get()->keyBy('id');

        $detalle = [];
        foreach ($comprobantes as $c) {
            $detalle[$c->id] = match ($c->conciliado_con) {
                'purchase' => ($d = $compras[$c->conciliado_id] ?? null) ? [
                    'tipo' => 'Compra',
                    // Con detalle: cada línea viene de un producto del proveedor (del XML).
                    'con_detalle' => $d->items->whereNotNull('producto_proveedor_id')->isNotEmpty(),
                    'lineas' => $d->items->map(fn ($i) => [$i->descripcion ?: $i->inventoryItem?->nombre ?: 'Sin descripción',
                        (float) $i->quantity, (float) $i->total_item])->all(),
                ] : null,
                'gasto' => ($d = $gastos[$c->conciliado_id] ?? null) ? [
                    'tipo' => 'Gasto',
                    // A mano, la línea dice "Del portal del SRI"; con el XML, el producto.
                    'con_detalle' => $d->lineas->contains(fn ($l) => ! str_starts_with((string) $l->descripcion, 'Del portal del SRI')),
                    'lineas' => $d->lineas->map(fn ($l) => [$l->descripcion, null, (float) $l->base + (float) $l->iva])->all(),
                ] : null,
                'sale' => ($d = $ventas[$c->conciliado_id] ?? null) ? [
                    'tipo' => 'Venta',
                    'con_detalle' => str_contains((string) $d->notas, 'con el detalle'),
                    'lineas' => $d->items->map(fn ($i) => [$i->inventoryItem?->nombre ?? $i->descripcion_servicio,
                        (float) $i->cantidad, (float) $i->total])->all(),
                ] : null,
                default => null,
            } ?? ['tipo' => 'Registrada', 'con_detalle' => false, 'lineas' => []];
        }

        return [$comprobantes, $detalle];
    }

    /** Filtra por el año y el proveedor elegidos, si los hay. */
    private function delFiltro($q)
    {
        return $q->when($this->anio, fn ($q) => $q->whereYear('fecha_emision', $this->anio))
            ->when($this->proveedor, fn ($q) => $q->where('identificacion', $this->proveedor));
    }

    /**
     * Proveedores con facturas pendientes en el año elegido —sin registrar o esperando
     * un producto—, con cuántas tiene cada uno.
     *
     * @return \Illuminate\Support\Collection<int, object{identificacion: string, nombre: string, n: int}>
     */
    private function proveedores(int $empresaId)
    {
        $esperan = ComprobanteSri::where('empresa_id', $empresaId)->where('conciliado_con', 'purchase')
            ->whereIn('conciliado_id', \App\Models\Purchase::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->where('status', \App\Models\Purchase::BORRADOR)->select('id'))
            ->select('id');

        return ComprobanteSri::where('empresa_id', $empresaId)->where('origen', 'recibido')
            ->where(fn ($q) => $q->sinClasificar()->orWhereIn('id', $esperan))
            ->when($this->anio, fn ($q) => $q->whereYear('fecha_emision', $this->anio))
            ->selectRaw("identificacion, max(coalesce(nullif(razon_social, ''), identificacion)) as nombre, count(*) as n")
            ->groupBy('identificacion')->orderBy('nombre')->get();
    }

    /** Los años que tienen comprobantes del portal, del más reciente al más viejo. */
    private function anios(int $empresaId): array
    {
        return ComprobanteSri::where('empresa_id', $empresaId)
            ->selectRaw('distinct extract(year from fecha_emision)::int as anio')
            ->orderByDesc('anio')->pluck('anio')->all();
    }

    private function cargar(): void
    {
        $this->eleccion = $this->pendientes()->mapWithKeys(fn ($c) => [$c->id => ['pago' => '']])->all();

        // Las facturas emitidas que no están en el ERP entran como venta.
        foreach ($this->ventasPendientes() as $c) {
            $this->eleccion[$c->id] = ['pago' => 'credito'];
        }
    }

    private function ventasPendientes()
    {
        return $this->deLaVista($this->delFiltro(ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'emitido')
            ->sinClasificar()))
            ->orderBy('fecha_emision')
            ->limit(60)
            ->get();
    }

    private function pendientes()
    {
        return $this->deLaVista($this->delFiltro(ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'recibido')
            ->sinClasificar()))
            ->orderBy('fecha_emision')
            ->orderBy('identificacion')
            ->limit(120)
            ->get();
    }

    /**
     * Registra una venta con su detalle: el XML del SRI trae cada producto con su
     * cantidad y precio. Si el SRI ya no la devuelve, espera su XML.
     */
    public function registrar(int $id): void
    {
        $c = ComprobanteSri::where('empresa_id', Filament::getTenant()->id)->findOrFail($id);
        $r = app(ClasificadorComprobantesService::class)
            ->registrarConDetalle($c, ($this->eleccion[$id]['pago'] ?? '') ?: null, auth()->id());

        $this->cargar();

        if (! ($r['ok'] ?? false)) {
            Notification::make()->title(($r['manual'] ?? false) ? 'Falta el detalle' : 'No se pudo registrar')
                ->body($r['error'])->{($r['manual'] ?? false) ? 'warning' : 'danger'}()->send();

            return;
        }

        Notification::make()->title('Registrada')->body(ucfirst($r['detalle']))->success()->send();
    }

    /**
     * Trae del SRI el detalle de las que faltan (con el año y el proveedor elegidos).
     * Va a la cola: un año son cientos de consultas al SRI y la web corta a los 30 s.
     */
    public function traerDetalle(): void
    {
        if (! RegistrarComprobantesDelPortal::encolar(Filament::getTenant()->id, auth()->id(), [], $this->anio, $this->proveedor)) {
            Notification::make()->title('Ya se está trayendo el detalle')->body('Mira el avance aquí arriba.')->info()->send();

            return;
        }

        Notification::make()->title('Trayendo el detalle en segundo plano')
            ->body('Cada factura se consulta en el SRI. Puedes seguir trabajando: te aviso en la campana al terminar.')
            ->info()->send();
    }

    /**
     * No es de la empresa: una compra personal con su RUC. Sale de la contabilidad,
     * del 104 y del ATS; si el 104 de su mes ya la declaró, entra como no deducible.
     */
    public function personal(int $id): void
    {
        $c = ComprobanteSri::where('empresa_id', Filament::getTenant()->id)->sinClasificar()->findOrFail($id);
        $r = app(ClasificadorComprobantesService::class)
            ->marcarPersonal($c, ($this->eleccion[$id]['pago'] ?? '') ?: null, auth()->id());

        $this->cargar();

        if (! ($r['ok'] ?? false)) {
            Notification::make()->title('No se pudo sacar')->body($r['error'])->danger()->send();

            return;
        }

        Notification::make()->title('Compra personal')->body(ucfirst($r['detalle']))->success()->send();
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
                    . 'no aparece aquí, porque se concilió solo por el número de factura. Al subir el '
                    . 'archivo, el ERP trae del SRI el detalle de cada factura por su clave de acceso: '
                    . 'las que lo tienen quedan listas para decir cómo se pagaron y registrarlas; las '
                    . 'que el SRI ya no entrega esperan su XML, porque sin detalle no se sabe qué fue. '
                    . 'La clave queda como número de autorización: es lo que pide el ATS.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Entendido'),
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $pendientes = in_array($this->vista, ['por_registrar', 'sin_detalle'], true) ? $this->pendientes() : collect();
        [$registradas, $detalle] = $this->vista === 'registradas' ? $this->registradas($empresa->id) : [collect(), []];

        return [
            'conteos'    => $this->conteos($empresa->id),
            'registradas' => $registradas,
            'detalle'    => $detalle,
            'empresa'    => $empresa,
            'comprobantes' => $pendientes,
            'total'      => $this->delFiltro(ComprobanteSri::where('empresa_id', $empresa->id)
                ->where('origen', 'recibido')->sinClasificar())->count(),
            'anios'      => $this->anios($empresa->id),
            'proveedores' => $this->proveedores($empresa->id),
            'ventas'     => in_array($this->vista, ['por_registrar', 'sin_detalle'], true) ? $this->ventasPendientes() : collect(),
            'medios'     => MedioPago::opciones($empresa->id),
            'mediosVenta' => MedioPago::opciones($empresa->id, true),
            'meses'      => $this->meses($empresa->id),
            'urlDeclaraciones' => Declaraciones::getUrl(),
            'sinDetalle' => ClasificadorComprobantesService::SIN_DETALLE,
            'avance'     => Cache::get(RegistrarComprobantesDelPortal::claveAvance($empresa->id)),
            // Personal en un mes ya declarado entra como no deducible: pide cómo se pagó.
            'declarados' => $pendientes->groupBy(fn ($c) => $c->fecha_emision->format('Y-m'))
                ->filter(fn ($mes) => app(ClasificadorComprobantesService::class)->mesDeclarado($mes->first()))
                ->flatten()->pluck('id')->flip()->all(),
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
        // Pendiente: sin registrar, o registrada como compra que espera que se diga
        // qué es un producto nuevo (en borrador no tiene asiento ni entra al ATS).
        return ComprobanteSri::where('comprobantes_sri.empresa_id', $empresaId)
            ->when($this->anio, fn ($q) => $q->whereYear('comprobantes_sri.fecha_emision', $this->anio))
            ->leftJoin('purchases as p', fn ($j) => $j->on('p.id', '=', 'comprobantes_sri.conciliado_id')
                ->where('comprobantes_sri.conciliado_con', '=', 'purchase'))
            ->selectRaw("to_char(comprobantes_sri.fecha_emision, 'YYYY-MM') as mes, count(*) as total, "
                . "sum(case when (comprobantes_sri.conciliado_con is null and comprobantes_sri.destino is null) "
                . "or p.status = 'borrador' then 1 else 0 end) as pendientes")
            ->groupBy('mes')->orderByDesc('mes')->limit(12)->get();
    }
}
