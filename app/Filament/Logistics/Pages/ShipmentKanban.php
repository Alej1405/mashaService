<?php

namespace App\Filament\Logistics\Pages;

use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\LogisticsPackage;
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

    public function mount(): void {}

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

        $solicitarPago = $nuevoEstado === 'finalizado_aduana';
        $this->notificarCliente($package->fresh(), $solicitarPago);

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

        if ($nuevo) {
            $this->notificarCliente($package->fresh(), false);

            Notification::make()
                ->title('Estado: «' . ($disponibles[$nuevo]['label'] ?? $nuevo) . '»')
                ->success()
                ->send();
        }
    }

    // ── Notificación al cliente ───────────────────────────────────────────────

    private function notificarCliente(LogisticsPackage $package, bool $solicitarPago = false): void
    {
        if (! $package->store_customer_id) {
            return;
        }

        $customer = StoreCustomer::find($package->store_customer_id);
        $empresa  = Empresa::find($package->empresa_id);

        if (! $customer || ! $empresa || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $mail = new LogisticsPackageStatusMail($package, $customer, $empresa, $solicitarPago);

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

        $packages = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $tenant->id)
            ->with(['storeCustomer', 'bodega'])
            ->latest()
            ->get();

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
