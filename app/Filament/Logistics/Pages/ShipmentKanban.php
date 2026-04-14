<?php

namespace App\Filament\Logistics\Pages;

use App\Models\LogisticsShipment;
use App\Models\LogisticsShipmentHistory;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ShipmentKanban extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Kanban Embarques';
    protected static ?string $navigationGroup = 'Importaciones';
    protected static ?string $title           = 'Tablero de Embarques';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.logistics.pages.shipment-kanban';

    // ── Datos reactivos ───────────────────────────────────────────────────────

    public string $filtroEstado = '';
    public string $filtroTipo   = '';

    // ── Livewire lifecycle ────────────────────────────────────────────────────

    public function mount(): void {}

    // ── Métodos llamados desde la vista via $wire ─────────────────────────────

    public function moverEmbarque(int $shipmentId, string $nuevoEstado): void
    {
        $estadosValidos = array_keys(LogisticsShipment::ESTADOS);

        if (! in_array($nuevoEstado, $estadosValidos)) {
            return;
        }

        $shipment = LogisticsShipment::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)
            ->with('consignatario')
            ->find($shipmentId);

        if (! $shipment) {
            return;
        }

        $estadoAnterior = $shipment->estado;

        if ($estadoAnterior === $nuevoEstado) {
            return;
        }

        $shipment->update(['estado' => $nuevoEstado]);

        // Registrar en historial
        $labelAnterior = LogisticsShipment::ESTADOS[$estadoAnterior]['label'] ?? $estadoAnterior;
        $labelNuevo    = LogisticsShipment::ESTADOS[$nuevoEstado]['label'] ?? $nuevoEstado;
        LogisticsShipmentHistory::registrar(
            shipmentId:     $shipment->id,
            tipo:           'cambio_estado',
            descripcion:    "Estado cambiado de «{$labelAnterior}» a «{$labelNuevo}»",
            estadoAnterior: $estadoAnterior,
            estadoNuevo:    $nuevoEstado,
        );

        // Cuando se entrega, actualizar acumulado del consignatario
        if ($nuevoEstado === 'entregada' && $estadoAnterior !== 'entregada') {
            $shipment->consignatario?->recalcularAcumulado();
        }

        Notification::make()
            ->title('Embarque ' . $shipment->numero_embarque . ' movido a «' . LogisticsShipment::ESTADOS[$nuevoEstado]['label'] . '»')
            ->success()
            ->send();
    }

    // ── Datos para la vista ───────────────────────────────────────────────────

    public function getShipmentsByStateProperty(): array
    {
        $tenant = Filament::getTenant();

        $query = LogisticsShipment::withoutGlobalScopes()
            ->where('empresa_id', $tenant->id)
            ->with(['consignatario', 'bodega'])
            ->withCount('packages');

        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }

        $shipments = $query->latest()->get();

        $grouped = [];
        foreach (array_keys(LogisticsShipment::ESTADOS) as $estado) {
            $grouped[$estado] = $shipments->where('estado', $estado)->values()->all();
        }

        return $grouped;
    }

    public function getColumnsProperty(): array
    {
        return LogisticsShipment::ESTADOS;
    }

    public function getTotalesProperty(): array
    {
        $tenant = Filament::getTenant();
        return [
            'total'     => LogisticsShipment::withoutGlobalScopes()->where('empresa_id', $tenant->id)->count(),
            'en_curso'  => LogisticsShipment::withoutGlobalScopes()->where('empresa_id', $tenant->id)->whereNotIn('estado', ['entregada'])->count(),
            'entregada' => LogisticsShipment::withoutGlobalScopes()->where('empresa_id', $tenant->id)->where('estado', 'entregada')->count(),
        ];
    }
}
