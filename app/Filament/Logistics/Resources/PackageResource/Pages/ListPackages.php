<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use App\Models\LogisticsShipment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ListPackages extends ListRecords
{
    protected static string $resource = PackageResource::class;

    public string $origenFiltro = 'todos';

    // ── Filtro de origen ─────────────────────────────────────────────────────

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($query && $this->origenFiltro !== 'todos') {
            $query->whereHas('bodega', fn ($q) => $q
                ->withoutGlobalScopes()
                ->where('pais', $this->origenFiltro)
            );
        }

        return $query;
    }

    public function setOrigen(string $origen): void
    {
        $this->origenFiltro = $origen;
    }

    // ── Acciones del header ───────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        $origen = $this->origenFiltro;

        return [
            // ── Radio de origen ──────────────────────────────────────────────
            Action::make('filtro_todos')
                ->label('Todos')
                ->color($origen === 'todos' ? 'primary' : 'gray')
                ->outlined($origen !== 'todos')
                ->size('sm')
                ->action(fn () => $this->setOrigen('todos')),

            Action::make('filtro_eeuu')
                ->label('EE.UU.')
                ->color($origen === 'EEUU' ? 'info' : 'gray')
                ->outlined($origen !== 'EEUU')
                ->size('sm')
                ->action(fn () => $this->setOrigen('EEUU')),

            Action::make('filtro_espana')
                ->label('España')
                ->color($origen === 'España' ? 'warning' : 'gray')
                ->outlined($origen !== 'España')
                ->size('sm')
                ->action(fn () => $this->setOrigen('España')),

            // ── Modal entregados ─────────────────────────────────────────────
            Action::make('entregados')
                ->label('Entregados')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->outlined()
                ->modalHeading('Paquetes Entregados')
                ->modalContent(fn () => new HtmlString($this->renderEntregados()))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalWidth('5xl'),

            CreateAction::make(),
        ];
    }

    // ── Contenido del modal de entregados ────────────────────────────────────

    private function renderEntregados(): string
    {
        $tenant = Filament::getTenant();

        $shipments = LogisticsShipment::withoutGlobalScopes()
            ->where('empresa_id', $tenant->id)
            ->where('estado', 'entregada')
            ->with([
                'consignatario',
                'packages' => fn ($q) => $q->withoutGlobalScopes()->with([
                    'storeCustomer' => fn ($q) => $q->withoutGlobalScopes(),
                    'bodega'        => fn ($q) => $q->withoutGlobalScopes(),
                ]),
            ])
            ->latest()
            ->get();

        if ($shipments->isEmpty()) {
            return '<div class="py-14 text-center text-sm text-gray-400 italic">No hay embarques entregados aún.</div>';
        }

        $rows = '';

        foreach ($shipments as $s) {
            $numEmb        = e($s->numero_embarque);
            $consignatario = e($s->consignatario?->nombre ?? '—');
            $salida        = $s->fecha_embarque?->format('d/m/Y') ?? '—';
            $llegada       = $s->fecha_llegada_ecuador?->format('d/m/Y') ?? '—';

            foreach ($s->packages as $pkg) {
                $tracking = e($pkg->numero_tracking ?? '—');
                $cliente  = $pkg->storeCustomer
                    ? e(trim($pkg->storeCustomer->nombre . ' ' . ($pkg->storeCustomer->apellido ?? '')))
                    : '—';
                $origen   = e($pkg->bodega?->pais ?? '—');
                $peso     = $pkg->peso_kg ? number_format($pkg->peso_kg, 3) . ' kg' : '—';
                $cobro    = $pkg->monto_cobro ? '$' . number_format($pkg->monto_cobro, 2) : '—';

                $rows .= "
                <tr class='border-b border-gray-100 hover:bg-gray-50 transition text-sm'>
                    <td class='px-4 py-3 font-mono text-xs text-gray-500 max-w-[160px] truncate' title='{$tracking}'>{$tracking}</td>
                    <td class='px-4 py-3 font-semibold text-gray-800 whitespace-nowrap'>{$cliente}</td>
                    <td class='px-4 py-3'>
                        <span class='font-mono text-xs font-bold text-primary-600'>{$numEmb}</span>
                        <span class='block text-xs text-gray-400'>{$salida} → {$llegada}</span>
                    </td>
                    <td class='px-4 py-3 text-xs text-gray-600'>{$consignatario}</td>
                    <td class='px-4 py-3 text-center'>
                        <span class='inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold bg-blue-100 text-blue-700'>{$origen}</span>
                    </td>
                    <td class='px-4 py-3 text-right text-gray-600 whitespace-nowrap'>{$peso}</td>
                    <td class='px-4 py-3 text-right font-semibold text-gray-800'>{$cobro}</td>
                </tr>";
            }
        }

        return "
        <div class='overflow-x-auto'>
            <table class='w-full text-sm'>
                <thead>
                    <tr class='border-b-2 border-gray-200 text-xs text-gray-400 uppercase tracking-wide bg-gray-50'>
                        <th class='px-4 py-3 text-left font-medium'>Tracking</th>
                        <th class='px-4 py-3 text-left font-medium'>Cliente</th>
                        <th class='px-4 py-3 text-left font-medium'>Embarque</th>
                        <th class='px-4 py-3 text-left font-medium'>Consignatario</th>
                        <th class='px-4 py-3 text-center font-medium'>Origen</th>
                        <th class='px-4 py-3 text-right font-medium'>Peso</th>
                        <th class='px-4 py-3 text-right font-medium'>Cobro</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>";
    }
}
