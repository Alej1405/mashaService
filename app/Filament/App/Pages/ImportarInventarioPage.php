<?php

namespace App\Filament\App\Pages;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportarInventarioPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon    = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup   = 'Inventario';
    protected static ?string $navigationLabel   = 'Importar Ítems';
    protected static ?string $title             = 'Importar Inventario desde Factura XML (SRI)';
    protected static string  $view              = 'filament.app.pages.importar-inventario';
    protected static ?int    $navigationSort    = 99;

    // ── Estado del flujo ────────────────────────────────────────────────────
    public int    $paso            = 1;
    public array  $data            = [];

    // ── Datos del proveedor ─────────────────────────────────────────────────
    public array  $itemsNuevos     = [];
    public array  $itemsExistentes = [];
    public ?array $proveedorData   = null;
    public bool   $proveedorExiste = false;
    public int    $proveedorId     = 0;

    // ── Datos de la factura (para crear la Compra) ──────────────────────────
    public ?array $facturaData     = null;

    // ── Resultado (paso 3) ──────────────────────────────────────────────────
    public string $purchaseNumero  = '';
    public string $journalNumero   = '';

    // ── Mapa de códigos SRI de forma de pago → forma_pago interna ──────────
    private const FORMA_PAGO_MAP = [
        '01' => ['forma' => 'efectivo',      'tipo' => 'contado', 'label' => 'Efectivo'],
        '15' => ['forma' => 'transferencia', 'tipo' => 'contado', 'label' => 'Compensación de deudas'],
        '16' => ['forma' => 'transferencia', 'tipo' => 'contado', 'label' => 'Transferencia bancaria'],
        '17' => ['forma' => 'tarjeta',       'tipo' => 'contado', 'label' => 'Tarjeta de débito'],
        '18' => ['forma' => 'transferencia', 'tipo' => 'contado', 'label' => 'Dinero electrónico'],
        '19' => ['forma' => 'tarjeta',       'tipo' => 'contado', 'label' => 'Tarjeta de crédito'],
        '20' => ['forma' => 'cheque',        'tipo' => 'contado', 'label' => 'Cheque / Sistema financiero'],
        '21' => ['forma' => 'credito',       'tipo' => 'credito', 'label' => 'Endoso de títulos / Crédito'],
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cargar Factura XML del SRI')
                    ->description('Sube el archivo XML de la factura electrónica autorizada por el SRI. El sistema leerá automáticamente el proveedor, los productos y generará la compra con su asiento contable.')
                    ->schema([
                        FileUpload::make('archivo')
                            ->label('Factura XML (.xml)')
                            ->acceptedFileTypes(['text/xml', 'application/xml', 'text/plain'])
                            ->disk('local')
                            ->directory('imports/temp')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function parsearArchivo(): void
    {
        $this->validate(['data.archivo' => 'required']);

        $state       = $this->form->getState();
        $archivoRaw  = $state['archivo'] ?? null;
        $archivoPath = is_array($archivoRaw)
            ? (array_values($archivoRaw)[0] ?? null)
            : $archivoRaw;

        if (! $archivoPath) {
            Notification::make()->title('No se encontró el archivo cargado')->danger()->send();
            return;
        }

        $fullPath = Storage::disk('local')->path($archivoPath);

        if (! file_exists($fullPath)) {
            Notification::make()->title('No se encontró el archivo cargado')->danger()->send();
            return;
        }

        $xmlContent = file_get_contents($fullPath);

        libxml_use_internal_errors(true);
        $outer = simplexml_load_string($xmlContent);

        if (! $outer) {
            Notification::make()
                ->title('El archivo no es un XML válido')
                ->body('Asegúrate de subir el archivo XML tal como lo entrega el SRI.')
                ->danger()->send();
            return;
        }

        // El SRI envuelve la factura en <comprobante><![CDATA[...]]></comprobante>
        $factura = isset($outer->comprobante)
            ? @simplexml_load_string((string) $outer->comprobante)
            : $outer;

        if (! $factura || ! isset($factura->infoTributaria)) {
            Notification::make()
                ->title('No se pudo leer la factura')
                ->body('El XML no tiene la estructura esperada de una factura electrónica SRI.')
                ->danger()->send();
            return;
        }

        // ── Proveedor (emisor) ───────────────────────────────────────────────
        $proveedor = [
            'nombre'         => trim((string) $factura->infoTributaria->razonSocial),
            'identificacion' => trim((string) $factura->infoTributaria->ruc),
            'direccion'      => trim((string) $factura->infoTributaria->dirMatriz),
            'telefono'       => '',
            'email'          => '',
            'contacto'       => trim((string) ($factura->infoTributaria->nombreComercial ?? $factura->infoTributaria->razonSocial)),
        ];

        if (isset($factura->infoAdicional->campoAdicional)) {
            foreach ($factura->infoAdicional->campoAdicional as $campo) {
                $etiqueta = strtolower((string) $campo->attributes()['nombre']);
                if (str_contains($etiqueta, 'mail') || str_contains($etiqueta, 'correo')) {
                    $proveedor['email'] = trim((string) $campo);
                }
            }
        }

        if (empty($proveedor['nombre'])) {
            Notification::make()->title('No se encontró la razón social del proveedor')->warning()->send();
            return;
        }

        // ── Datos de la factura ─────────────────────────────────────────────
        $info = $factura->infoFactura;

        // Número de factura: estab-ptoEmi-secuencial
        $numeroFactura = sprintf(
            '%s-%s-%s',
            (string) $factura->infoTributaria->estab,
            (string) $factura->infoTributaria->ptoEmi,
            (string) $factura->infoTributaria->secuencial
        );

        // Fecha: dd/MM/yyyy → Y-m-d
        $fechaStr  = (string) $info->fechaEmision;
        $fechaParts = explode('/', $fechaStr);
        $fecha = count($fechaParts) === 3
            ? "{$fechaParts[2]}-{$fechaParts[1]}-{$fechaParts[0]}"
            : now()->toDateString();

        // Forma de pago
        $codigoPago    = trim((string) ($info->pagos->pago->formaPago ?? ''));
        $mapaPago      = self::FORMA_PAGO_MAP[$codigoPago]
                         ?? ['forma' => 'transferencia', 'tipo' => 'contado', 'label' => 'Transferencia bancaria (por defecto)'];

        // Totales financieros
        $subtotal = (float) ($info->totalSinImpuestos ?? 0);
        $total    = (float) ($info->importeTotal      ?? 0);
        $iva      = round($total - $subtotal, 4);

        $this->facturaData = [
            'numero'          => $numeroFactura,
            'fecha'           => $fecha,
            'subtotal'        => $subtotal,
            'iva'             => $iva,
            'total'           => $total,
            'forma_pago'      => $mapaPago['forma'],
            'tipo_pago'       => $mapaPago['tipo'],
            'label_pago'      => $mapaPago['label'],
            'codigo_sri'      => $codigoPago ?: '—',
        ];

        // ── Productos ───────────────────────────────────────────────────────
        if (! isset($factura->detalles->detalle)) {
            Notification::make()->title('La factura no contiene productos')->warning()->send();
            return;
        }

        $empresaId           = Filament::getTenant()->id;
        $productosNuevos     = [];
        $productosExistentes = [];

        foreach ($factura->detalles->detalle as $detalle) {
            $nombre       = trim((string) $detalle->descripcion);
            $precioUnit   = (float) $detalle->precioUnitario;
            $cantidad     = (float) $detalle->cantidad;

            // Detectar si aplica IVA (codigoPorcentaje > 0 ó tarifa > 0)
            $aplicaIva = false;
            if (isset($detalle->impuestos->impuesto)) {
                foreach ($detalle->impuestos->impuesto as $imp) {
                    if ((int) $imp->codigoPorcentaje > 0 || (float) $imp->tarifa > 0) {
                        $aplicaIva = true;
                        break;
                    }
                }
            }

            if (empty($nombre)) continue;

            $existe = InventoryItem::where('empresa_id', $empresaId)
                ->whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            if ($existe) {
                $productosExistentes[] = [
                    'id'            => $existe->id,
                    'nombre'        => $nombre,
                    'cantidad'      => $cantidad,
                    'precio_compra' => $precioUnit,
                    'aplica_iva'    => $aplicaIva,
                ];
            } else {
                $productosNuevos[] = [
                    'nombre'        => $nombre,
                    'precio_compra' => $precioUnit,
                    'precio_venta'  => 0,
                    'cantidad'      => $cantidad,
                    'aplica_iva'    => $aplicaIva,
                    'type'          => '',
                    'lote'          => '',
                ];
            }
        }

        $supplierExiste = Supplier::where('empresa_id', $empresaId)
            ->whereRaw('LOWER(nombre) = ?', [strtolower($proveedor['nombre'])])
            ->first();

        $this->proveedorData   = $proveedor;
        $this->proveedorExiste = (bool) $supplierExiste;
        $this->proveedorId     = $supplierExiste?->id ?? 0;
        $this->itemsExistentes = $productosExistentes;
        $this->itemsNuevos     = $productosNuevos;
        $this->paso            = 2;
    }

    public function importar(): void
    {
        // Validar que todos los ítems nuevos tengan tipo
        foreach ($this->itemsNuevos as $item) {
            if (empty($item['type'])) {
                Notification::make()
                    ->title('Falta el tipo de ítem para: ' . $item['nombre'])
                    ->warning()->send();
                return;
            }
        }

        $empresaId = Filament::getTenant()->id;

        try {
            // ── 1. Crear proveedor si es nuevo ───────────────────────────────
            if (! $this->proveedorExiste && $this->proveedorData) {
                $identificacion = $this->proveedorData['identificacion'];
                $tipoId = match (strlen($identificacion)) {
                    13      => 'ruc',
                    10      => 'cedula',
                    default => 'pasaporte',
                };

                $supplier = Supplier::create([
                    'empresa_id'            => $empresaId,
                    'codigo'                => 'PRV-' . strtoupper(uniqid()),
                    'nombre'                => $this->proveedorData['nombre'],
                    'tipo_persona'          => 'juridica',
                    'tipo_identificacion'   => $tipoId,
                    'numero_identificacion' => $identificacion,
                    'contacto_principal'    => $this->proveedorData['contacto'] ?: $this->proveedorData['nombre'],
                    'telefono_principal'    => $this->proveedorData['telefono'] ?: '0000000000',
                    'correo_principal'      => $this->proveedorData['email'] ?: 'pendiente@actualizar.com',
                    'direccion'             => $this->proveedorData['direccion'] ?? '',
                    'pais'                  => 'Ecuador',
                    'tipo_proveedor'        => ['insumos'],
                    'activo'                => true,
                ]);
                $this->proveedorId = $supplier->id;
            }

            // ── 2. Crear ítems nuevos de inventario (stock en 0: el observer lo suma) ─
            $todosLosItems = [];

            foreach ($this->itemsNuevos as $item) {
                $inventoryItem = InventoryItem::create([
                    'empresa_id'     => $empresaId,
                    'codigo'         => 'INV-' . strtoupper(uniqid()),
                    'nombre'         => $item['nombre'],
                    'type'           => $item['type'],
                    'purchase_price' => $item['precio_compra'],
                    'sale_price'     => 0,
                    'stock_actual'   => 0, // El observer lo incrementa al confirmar la compra
                    'stock_minimo'   => 0,
                    'lote'           => $item['lote'] ?: null,
                    'supplier_id'    => $this->proveedorId ?: null,
                    'activo'         => true,
                ]);

                $todosLosItems[] = [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity'          => $item['cantidad'],
                    'unit_price'        => $item['precio_compra'],
                    'aplica_iva'        => $item['aplica_iva'] ?? false,
                ];
            }

            foreach ($this->itemsExistentes as $item) {
                $todosLosItems[] = [
                    'inventory_item_id' => $item['id'],
                    'quantity'          => $item['cantidad'],
                    'unit_price'        => $item['precio_compra'],
                    'aplica_iva'        => $item['aplica_iva'] ?? false,
                ];
            }

            if (empty($todosLosItems)) {
                Notification::make()->title('No hay ítems para registrar en la compra')->warning()->send();
                return;
            }

            // ── 3. Crear la Compra en estado borrador ────────────────────────
            $purchase = Purchase::create([
                'empresa_id'  => $empresaId,
                'supplier_id' => $this->proveedorId ?: null,
                'date'        => $this->facturaData['fecha'],
                'tipo_pago'   => $this->facturaData['tipo_pago'],
                'forma_pago'  => $this->facturaData['forma_pago'],
                'subtotal'    => $this->facturaData['subtotal'],
                'iva'         => $this->facturaData['iva'],
                'total'       => $this->facturaData['total'],
                'status'      => 'borrador',
                'notas'       => 'Importado desde Factura SRI: ' . $this->facturaData['numero'],
            ]);

            // ── 4. Crear ítems de la compra ──────────────────────────────────
            foreach ($todosLosItems as $item) {
                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity'          => $item['quantity'],
                    'unit_price'        => $item['unit_price'],
                    'aplica_iva'        => $item['aplica_iva'],
                ]);
            }

            // ── 5. Sincronizar totales de la compra con los ítems reales ────────
            // El boot de PurchaseItem recalcula subtotal = quantity * unit_price,
            // que puede diferir del total del XML por decimales (subsidio combustible,
            // redondeo SRI, etc.). Iteramos igual que AccountingService para obtener
            // exactamente el mismo DEBE y lo guardamos como HABER vía DB directa,
            // evitando que Eloquent omita la escritura por dirty-check de decimales.
            $purchase->load('items');

            $subtotalReal = 0.0;
            $ivaReal      = 0.0;

            foreach ($purchase->items as $pi) {
                $subtotalReal += (float) $pi->subtotal;
                if ($pi->aplica_iva) {
                    $ivaReal += (float) $pi->iva_monto;
                }
            }

            $totalReal = $subtotalReal + $ivaReal;

            \Illuminate\Support\Facades\DB::table('purchases')
                ->where('id', $purchase->id)
                ->update([
                    'subtotal' => $subtotalReal,
                    'iva'      => $ivaReal,
                    'total'    => $totalReal,
                ]);

            // ── 6. Confirmar la compra → dispara PurchaseObserver ────────────
            //       El observer llama a AccountingService::generarAsientoCompra()
            //       e incrementa el stock de cada ítem automáticamente.
            $purchase->update(['status' => 'confirmado']);

            // ── 6. Obtener números para la notificación ──────────────────────
            $purchase->refresh();
            $this->purchaseNumero = $purchase->number;
            $this->journalNumero  = $purchase->journalEntry?->numero ?? '—';

            Notification::make()
                ->title('Compra registrada correctamente')
                ->body(implode(' | ', array_filter([
                    'Compra: ' . $this->purchaseNumero,
                    'Asiento contable: ' . $this->journalNumero,
                    'Proveedor: ' . ($this->proveedorExiste ? 'existente' : 'creado'),
                    'Ítems nuevos: ' . count($this->itemsNuevos),
                    'Ítems existentes: ' . count($this->itemsExistentes),
                ])))
                ->success()
                ->persistent()
                ->send();

            $this->paso = 3;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al procesar la importación')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function reiniciar(): void
    {
        $this->paso            = 1;
        $this->data            = [];
        $this->itemsNuevos     = [];
        $this->itemsExistentes = [];
        $this->proveedorData   = null;
        $this->proveedorExiste = false;
        $this->proveedorId     = 0;
        $this->facturaData     = null;
        $this->purchaseNumero  = '';
        $this->journalNumero   = '';
        $this->form->fill();
    }
}
