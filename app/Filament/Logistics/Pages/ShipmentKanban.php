<?php

namespace App\Filament\Logistics\Pages;

use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Resend\Laravel\Facades\Resend;

class ShipmentKanban extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Kanban';
    protected static ?string $navigationGroup = 'Importaciones';
    protected static ?string $title           = 'Tablero de Paquetes';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.logistics.pages.shipment-kanban';

    // ── Livewire ──────────────────────────────────────────────────────────────

    public bool $mostrarEntregados = false;

    public function mount(): void {}

    public function toggleEntregados(): void
    {
        $this->mostrarEntregados = ! $this->mostrarEntregados;
    }

    // ── Mover paquete (drag-and-drop cambia estado principal) ─────────────────

    public function moverPaquete(int $packageId, string $nuevoEstado): void
    {
        if (! array_key_exists($nuevoEstado, LogisticsPackage::ESTADOS)) {
            return;
        }

        $package = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)
            ->find($packageId);

        if (! $package || $package->estado === $nuevoEstado) {
            return;
        }

        $package->update([
            'estado'            => $nuevoEstado,
            'estado_secundario' => null,
        ]);

        $fresh = $package->fresh();

        $this->sincronizarEmbarques($fresh);

        // Al entrar en aduana: crear nota de venta y notificar con el link de aceptación
        if ($nuevoEstado === 'en_aduana' && $fresh->store_customer_id && $fresh->monto_cobro > 0) {
            $billingRequest = LogisticsBillingRequest::crearParaPaquete($fresh);
            $this->notificarCliente($fresh, true, $billingRequest);
        } else {
            $this->notificarCliente($fresh, false);
        }

        Notification::make()
            ->title('Paquete movido a «' . (LogisticsPackage::ESTADOS[$nuevoEstado]['label'] ?? $nuevoEstado) . '»')
            ->success()
            ->send();
    }

    // ── Cambiar estado secundario desde la tarjeta ────────────────────────────

    public function setEstadoSecundario(int $packageId, string $estadoSecundario): void
    {
        $package = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)
            ->find($packageId);

        if (! $package) {
            return;
        }

        $disponibles = LogisticsPackage::ESTADOS_SECUNDARIOS[$package->estado] ?? [];

        if (! array_key_exists($estadoSecundario, $disponibles)) {
            return;
        }

        // Toggle: si ya está activo, lo quita
        $nuevo = $package->estado_secundario === $estadoSecundario ? null : $estadoSecundario;
        $package->update(['estado_secundario' => $nuevo]);

        $fresh = $package->fresh();

        if ($nuevo) {
            $this->sincronizarEmbarques($fresh);
            $this->notificarCliente($fresh, false);

            Notification::make()
                ->title('Estado: «' . ($disponibles[$nuevo]['label'] ?? $nuevo) . '»')
                ->success()
                ->send();
        }
    }

    // ── Sincronizar estado del embarque basado en los paquetes ───────────────

    /**
     * Prioridad numérica de los estados del embarque (mayor = más avanzado).
     * Permite comparar sin retroceder el estado del embarque.
     */
    private static function prioridadEmbarque(string $estado): int
    {
        static $orden = [
            'embarque_solicitado'        => 1,
            'carga_registrada'           => 2,
            'consolidando'               => 3,
            'fraccionamiento_en_proceso' => 4,
            'carga_embarcada'            => 5,
            'en_aduana'                  => 6,
            'declaracion_transmitida'    => 7,
            'aforo_automatico'           => 8,
            'aforo_documental'           => 8,
            'aforo_fisico'               => 8,
            'liquidada'                  => 9,
            'pagada'                     => 10,
            'autorizado_salida'          => 11,
            'entregada'                  => 12,
        ];

        return $orden[$estado] ?? 0;
    }

    /**
     * Devuelve el estado de embarque que corresponde al estado actual de un paquete.
     * Los estados secundarios de 'en_aduana' coinciden exactamente con estados del embarque.
     */
    private static function estadoEmbarqueDesde(string $pkgEstado, ?string $pkgSecundario): ?string
    {
        return match(true) {
            $pkgEstado === 'embarque_solicitado' && $pkgSecundario === 'embarque_confirmado'
                => 'carga_registrada',
            $pkgEstado === 'embarque_solicitado'
                => 'embarque_solicitado',
            $pkgEstado === 'registrado' && $pkgSecundario === 'arribo_miami'
                => 'carga_embarcada',
            $pkgEstado === 'registrado'
                => 'carga_registrada',
            // Los secundarios de en_aduana coinciden con estados del embarque
            $pkgEstado === 'en_aduana' && $pkgSecundario !== null
                => $pkgSecundario,
            $pkgEstado === 'en_aduana'
                => 'en_aduana',
            $pkgEstado === 'finalizado_aduana' && $pkgSecundario === 'en_despacho'
                => 'autorizado_salida',
            $pkgEstado === 'finalizado_aduana'
                => 'pagada',
            $pkgEstado === 'pago_servicios'
                => 'pagada',
            $pkgEstado === 'en_entrega' && $pkgSecundario === 'entregado'
                => 'entregada',
            $pkgEstado === 'en_entrega'
                => 'autorizado_salida',
            default => null,
        };
    }

    private function sincronizarEmbarques(LogisticsPackage $package): void
    {
        $shipments = LogisticsShipment::withoutGlobalScopes()
            ->whereHas('packages', fn ($q) => $q->where('logistics_packages.id', $package->id))
            ->get();

        foreach ($shipments as $shipment) {
            // Para embarques con varios paquetes: usa el estado mínimo (más conservador)
            $paquetes = $shipment->packages()
                ->withoutGlobalScopes()
                ->get();

            $estadoMinimo = null;
            $prioMin      = PHP_INT_MAX;

            foreach ($paquetes as $pkg) {
                $est  = self::estadoEmbarqueDesde($pkg->estado, $pkg->estado_secundario);
                $prio = self::prioridadEmbarque($est ?? 'embarque_solicitado');

                if ($prio < $prioMin) {
                    $prioMin      = $prio;
                    $estadoMinimo = $est;
                }
            }

            if ($estadoMinimo && array_key_exists($estadoMinimo, LogisticsShipment::ESTADOS)) {
                $shipment->update(['estado' => $estadoMinimo]);
            }
        }
    }

    // ── Notificación al cliente ───────────────────────────────────────────────

    private function notificarCliente(LogisticsPackage $package, bool $solicitarPago = false, ?LogisticsBillingRequest $billingRequest = null): void
    {
        if (! $package->store_customer_id) {
            return;
        }

        $customer = StoreCustomer::find($package->store_customer_id);
        $empresa  = Empresa::find($package->empresa_id);

        if (! $customer || ! $empresa || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $mail = new LogisticsPackageStatusMail($package, $customer, $empresa, $solicitarPago, $billingRequest);

        try {
            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => [$customer->email],
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando notificación logística', [
                'package_id' => $package->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ── Datos para la vista ───────────────────────────────────────────────────

    public function getPackagesByStateProperty(): array
    {
        $tenant = Filament::getTenant();

        $query = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $tenant->id)
            ->with([
                'storeCustomer' => fn ($q) => $q->withoutGlobalScopes(),
                'bodega'        => fn ($q) => $q->withoutGlobalScopes(),
                'shipments'     => fn ($q) => $q->withoutGlobalScopes()
                                               ->orderByDesc('created_at')
                                               ->limit(1),
            ])
            ->latest();

        // Ocultar los paquetes ya entregados (estado_secundario = 'entregado')
        if (! $this->mostrarEntregados) {
            $query->where(fn ($q) => $q
                ->where('estado', '!=', 'en_entrega')
                ->orWhereNull('estado_secundario')
                ->orWhere('estado_secundario', '!=', 'entregado')
            );
        }

        $packages = $query->get();

        $grouped = [];
        foreach (array_keys(LogisticsPackage::ESTADOS) as $estado) {
            $grouped[$estado] = $packages->where('estado', $estado)->values()->all();
        }

        return $grouped;
    }

    public function getColumnsProperty(): array
    {
        return LogisticsPackage::ESTADOS;
    }

    public function getTotalesProperty(): array
    {
        $tenant = Filament::getTenant();
        $total      = LogisticsPackage::withoutGlobalScopes()->where('empresa_id', $tenant->id)->count();
        $entregados = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $tenant->id)
            ->where('estado', 'en_entrega')
            ->where('estado_secundario', 'entregado')
            ->count();

        return [
            'total'      => $total,
            'en_curso'   => $total - $entregados,
            'entregados' => $entregados,
        ];
    }
}
