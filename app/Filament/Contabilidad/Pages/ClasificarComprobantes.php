<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\ComprobanteSri;
use App\Models\InventoryItem;
use App\Models\TipoGasto;
use App\Filament\Contabilidad\Acciones\ImportarDelPortal;
use App\Services\ClasificadorComprobantesService;
use App\Services\MedioPago;
use App\Models\ProductoProveedor;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use Illuminate\Validation\ValidationException;
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

    /** Año que se trabaja en la bandeja; null = todos. Queda en la URL. */
    #[\Livewire\Attributes\Url]
    public ?int $anio = null;

    /**
     * Qué parte de la bandeja se mira. Cada una es un estado del detalle:
     *   por_registrar · el detalle se trae del SRI al registrar
     *   sin_detalle   · el SRI ya no la devuelve: se sube su XML o se dice a mano
     *   esperan       · registrada con detalle, espera que se diga qué es un producto nuevo
     *   registradas   · con asiento; con su detalle o registrada a mano
     */
    #[\Livewire\Attributes\Url]
    public string $vista = 'por_registrar';

    public const VISTAS = ['por_registrar', 'sin_detalle', 'esperan', 'registradas'];

    /** producto_proveedor_id => 'item:12' | 'gasto:5' — se dice una vez por producto, no por factura. */
    public array $productos = [];

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

    public function verVista(string $vista): void
    {
        $this->vista = in_array($vista, self::VISTAS, true) ? $vista : 'por_registrar';
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
        $base = fn () => $this->delAnio(ComprobanteSri::where('empresa_id', $empresaId));
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
        return $this->delAnio(ComprobanteSri::where('empresa_id', $empresaId)->where('conciliado_con', 'purchase'))
            ->whereIn('conciliado_id', \App\Models\Purchase::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->where('status', \App\Models\Purchase::BORRADOR)->select('id'));
    }

    /**
     * Las registradas (o esperando) con lo que se registró de cada una.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: array<int, array{tipo: string, con_detalle: bool, lineas: array}>}
     */
    private function registradas(int $empresaId, bool $soloEsperan): array
    {
        $comprobantes = ($soloEsperan
            ? $this->esperanQuery($empresaId)
            : $this->delAnio(ComprobanteSri::where('empresa_id', $empresaId)->whereNotNull('conciliado_con'))
                ->whereNotIn('id', $this->esperanQuery($empresaId)->select('id')))
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

    /** Filtra por el año elegido, si hay uno. */
    private function delAnio($q)
    {
        return $q->when($this->anio, fn ($q) => $q->whereYear('fecha_emision', $this->anio));
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
        return $this->deLaVista($this->delAnio(ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'emitido')
            ->sinClasificar()))
            ->orderBy('fecha_emision')
            ->limit(60)
            ->get();
    }

    private function pendientes()
    {
        return $this->deLaVista($this->delAnio(ComprobanteSri::where('empresa_id', Filament::getTenant()->id)
            ->where('origen', 'recibido')
            ->sinClasificar()))
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

    /**
     * Registra una factura con su detalle: el XML del SRI trae cada producto con
     * su cantidad, precio e IVA. Solo se pide algo a mano si el SRI ya no la
     * devuelve, o si en una venta no se reconoce el producto.
     */
    public function registrar(int $id): void
    {
        $c = ComprobanteSri::where('empresa_id', Filament::getTenant()->id)->findOrFail($id);
        $r = app(ClasificadorComprobantesService::class)
            ->registrarConDetalle($c, $this->eleccion[$id]['pago'] ?? 'credito', auth()->id());

        $this->cargar();

        if (! ($r['ok'] ?? false)) {
            Notification::make()->title(($r['manual'] ?? false) ? 'Hay que decirlo a mano' : 'No se pudo registrar')
                ->body($r['error'])->{($r['manual'] ?? false) ? 'warning' : 'danger'}()->send();

            return;
        }

        Notification::make()->title('Registrada')->body(ucfirst($r['detalle']))->success()->send();
    }

    /**
     * Todas las pendientes con su detalle, cada una con el pago elegido en su fila.
     * Va a la cola: un año son cientos de consultas al SRI y la web corta a los 30 s.
     */
    public function registrarTodas(): void
    {
        $empresaId = Filament::getTenant()->id;

        if ((Cache::get(RegistrarComprobantesDelPortal::claveAvance($empresaId))['corriendo'] ?? false)) {
            Notification::make()->title('Ya se está registrando')->body('Mira el avance aquí arriba.')->info()->send();

            return;
        }

        $pagos = collect($this->eleccion)->map(fn ($e) => $e['pago'] ?? null)->filter()->all();
        Cache::put(RegistrarComprobantesDelPortal::claveAvance($empresaId),
            ['corriendo' => true, 'total' => 0, 'hechos' => 0], now()->addHours(6));
        RegistrarComprobantesDelPortal::dispatch($empresaId, auth()->id(), $pagos, $this->anio);

        Notification::make()->title('Registrando en segundo plano')
            ->body('Cada factura se consulta en el SRI. Puedes seguir trabajando: te aviso en la campana al terminar.')
            ->info()->send();
    }

    /**
     * Subir el XML de facturas que el SRI ya no devuelve por clave: uno, varios o
     * un .zip. Cada uno se casa con su factura del listado del portal por la clave.
     */
    private function accionSubirXml(): Action
    {
        return Action::make('subirXml')
            ->label('Subir XML')
            ->icon('heroicon-o-document-arrow-up')
            ->color('gray')
            ->modalDescription('Para las facturas viejas que el SRI ya no entrega por su clave. Sube el XML de cada '
                . 'una (del portal del SRI o del correo del proveedor), varios a la vez o un .zip. Se registran con '
                . 'su detalle y se casan solas con su factura del listado.')
            ->form([
                \Filament\Forms\Components\FileUpload::make('archivos')
                    ->label('XML o .zip')
                    ->multiple()
                    ->maxFiles(60)
                    ->acceptedFileTypes(['text/xml', 'application/xml', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                    ->maxSize(20480)
                    ->disk('local')
                    ->directory('sri/xml-temporal')
                    ->required(),
                \Filament\Forms\Components\Select::make('pago')
                    ->label('Cómo se pagaron')
                    ->options(fn () => MedioPago::opciones(Filament::getTenant()->id))
                    ->default('credito')
                    ->required(),
            ])
            ->action(function (array $data) {
                $empresaId = Filament::getTenant()->id;
                $servicio = app(ClasificadorComprobantesService::class);
                $hechas = 0;
                $fallos = [];

                // Cada XML es una lectura en el servicio del SRI: por tanda, hasta 80 (la web corta a los 30 s).
                $xmls = $this->xmlsDe($data['archivos']);
                $resto = max(count($xmls) - 80, 0);

                foreach (array_slice($xmls, 0, 80, true) as $nombre => $xml) {
                    $r = $servicio->registrarDesdeXml($empresaId, $xml, $data['pago'], auth()->id());
                    if ($r['ok'] ?? false) {
                        $hechas++;
                    } else {
                        $fallos[] = ($r['numero'] ?? $nombre) . ': ' . $r['error'];
                    }
                }

                $this->cargar();

                Notification::make()
                    ->title("{$hechas} facturas registradas con su XML")
                    ->body(trim(($fallos ? 'No se pudo con ' . count($fallos) . ': ' . implode(' · ', array_slice($fallos, 0, 3)) : 'Todas quedaron con su detalle.')
                        . ($resto ? " Quedaron {$resto} sin procesar: súbelas en otra tanda." : '')))
                    ->{$fallos ? 'warning' : 'success'}()->persistent()->send();
            });
    }

    /**
     * Los XML de lo subido: los sueltos tal cual y los que vienen dentro de un .zip.
     *
     * @return array<string, string> nombre => contenido
     */
    private function xmlsDe(array $rutas): array
    {
        $xmls = [];

        foreach ($rutas as $relativa) {
            $ruta = storage_path('app/private/' . $relativa);
            if (! file_exists($ruta)) {
                $ruta = storage_path('app/' . $relativa);
            }

            if (str_ends_with(strtolower($relativa), '.zip')) {
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
                $xmls[basename($relativa)] = file_get_contents($ruta);
            }

            @unlink($ruta);
        }

        return $xmls;
    }

    /** Dice qué es un producto de un proveedor: vale para esta factura y las que vengan. */
    public function configurarProducto(int $id): void
    {
        $producto = app(ComprasElectronicasPendientes::class)->productos(Filament::getTenant()->id)->findOrFail($id);
        [$tipo, $valor] = array_pad(explode(':', (string) ($this->productos[$id] ?? '')), 2, null);

        try {
            $confirmadas = app(ConfirmarCompraElectronica::class)->producto(
                $producto, $tipo === 'item' ? (int) $valor : null, $tipo === 'gasto' ? (int) $valor : null, auth()->id(),
            );
        } catch (ValidationException $e) {
            Notification::make()->title('Falta elegir')->body(collect($e->errors())->flatten()->first())->warning()->send();

            return;
        }

        Notification::make()->title('Producto guardado')
            ->body($confirmadas ? "{$confirmadas} compras quedaron registradas con su asiento." : 'Se usará en las próximas facturas de ese proveedor.')
            ->success()->send();
    }

    /** Clasifica a mano una factura que el SRI ya no devuelve. */
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
            $this->accionSubirXml(),

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
        $pendientes = in_array($this->vista, ['por_registrar', 'sin_detalle'], true) ? $this->pendientes() : collect();
        [$registradas, $detalle] = in_array($this->vista, ['esperan', 'registradas'], true)
            ? $this->registradas($empresa->id, $this->vista === 'esperan')
            : [collect(), []];

        return [
            'conteos'    => $this->conteos($empresa->id),
            'registradas' => $registradas,
            'detalle'    => $detalle,
            'empresa'    => $empresa,
            'comprobantes' => $pendientes,
            'total'      => $this->delAnio(ComprobanteSri::where('empresa_id', $empresa->id)
                ->where('origen', 'recibido')->sinClasificar())->count(),
            'anios'      => $this->anios($empresa->id),
            'ventas'     => in_array($this->vista, ['por_registrar', 'sin_detalle'], true) ? $this->ventasPendientes() : collect(),
            'medios'     => MedioPago::opciones($empresa->id),
            'mediosVenta' => MedioPago::opciones($empresa->id, true),
            'meses'      => $this->meses($empresa->id),
            'urlDeclaraciones' => Declaraciones::getUrl(),
            'sinDetalle' => ClasificadorComprobantesService::SIN_DETALLE,
            'avance'     => Cache::get(RegistrarComprobantesDelPortal::claveAvance($empresa->id)),
            // Productos de proveedor que aparecieron en una factura y nadie ha dicho qué son.
            'productosNuevos' => app(ComprasElectronicasPendientes::class)->productos($empresa->id)
                ->with('supplier:id,nombre')->orderBy('supplier_id')->limit(80)->get(),
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
