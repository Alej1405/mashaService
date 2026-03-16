<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\AccountPlan;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class BalanceComprobacion extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Informes Financieros';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Balance de Comprobación';
    protected static string $view = 'filament.app.pages.reports.balance-comprobacion';

    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;

    public function mount(): void
    {
        $this->fecha_desde = now()->startOfYear()->toDateString();
        $this->fecha_hasta = now()->toDateString();
        $this->form->fill([
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('fecha_desde')
                    ->label('Desde')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                DatePicker::make('fecha_hasta')
                    ->label('Hasta')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
            ])
            ->columns(2);
    }

    public function updatedFilters()
    {
        $data = $this->form->getState();
        $this->fecha_desde = $data['fecha_desde'];
        $this->fecha_hasta = $data['fecha_hasta'];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AccountPlan::query()
                    ->where('empresa_id', Filament::getTenant()->id)
                    ->where('accepts_movements', true)
                    ->whereIn('id', function ($q) {
                        $empresaId = Filament::getTenant()->id;
                        $q->select('account_plan_id')
                            ->from('journal_entry_lines')
                            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                            ->where('journal_entries.empresa_id', $empresaId)
                            ->where('journal_entries.status', 'confirmado')
                            ->where('journal_entries.esta_cuadrado', true);
                    })
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Cuenta')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('total_debe')
                    ->label('Total Debe')
                    ->money('USD')
                    ->state(function (AccountPlan $record): float {
                        $empresaId = Filament::getTenant()->id;

                        return (float) \App\Models\JournalEntryLine::where('account_plan_id', $record->id)
                            ->whereHas('journalEntry', fn($q) =>
                                $q->withoutGlobalScopes()
                                  ->where('empresa_id', $empresaId)
                                  ->where('status', 'confirmado')
                                  ->where('esta_cuadrado', true)
                                  ->when($this->fecha_desde, fn($query) => $query->whereDate('fecha', '>=', $this->fecha_desde))
                                  ->when($this->fecha_hasta, fn($query) => $query->whereDate('fecha', '<=', $this->fecha_hasta))
                            )->sum('debe');
                    }),
                TextColumn::make('total_haber')
                    ->label('Total Haber')
                    ->money('USD')
                    ->state(function (AccountPlan $record): float {
                        $empresaId = Filament::getTenant()->id;

                        return (float) \App\Models\JournalEntryLine::where('account_plan_id', $record->id)
                            ->whereHas('journalEntry', fn($q) =>
                                $q->withoutGlobalScopes()
                                  ->where('empresa_id', $empresaId)
                                  ->where('status', 'confirmado')
                                  ->where('esta_cuadrado', true)
                                  ->when($this->fecha_desde, fn($query) => $query->whereDate('fecha', '>=', $this->fecha_desde))
                                  ->when($this->fecha_hasta, fn($query) => $query->whereDate('fecha', '<=', $this->fecha_hasta))
                            )->sum('haber');
                    }),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('USD')
                    ->state(function (AccountPlan $record): float {
                        $empresaId = Filament::getTenant()->id;
                        $debe = (float) \App\Models\JournalEntryLine::where('account_plan_id', $record->id)
                            ->whereHas('journalEntry', fn($q) =>
                                $q->withoutGlobalScopes()
                                  ->where('empresa_id', $empresaId)
                                  ->where('status', 'confirmado')
                                  ->where('esta_cuadrado', true)
                                  ->when($this->fecha_desde, fn($query) => $query->whereDate('fecha', '>=', $this->fecha_desde))
                                  ->when($this->fecha_hasta, fn($query) => $query->whereDate('fecha', '<=', $this->fecha_hasta))
                            )->sum('debe');

                        $haber = (float) \App\Models\JournalEntryLine::where('account_plan_id', $record->id)
                            ->whereHas('journalEntry', fn($q) =>
                                $q->withoutGlobalScopes()
                                  ->where('empresa_id', $empresaId)
                                  ->where('status', 'confirmado')
                                  ->where('esta_cuadrado', true)
                                  ->when($this->fecha_desde, fn($query) => $query->whereDate('fecha', '>=', $this->fecha_desde))
                                  ->when($this->fecha_hasta, fn($query) => $query->whereDate('fecha', '<=', $this->fecha_hasta))
                            )->sum('haber');

                        return $record->nature === 'deudora' ? $debe - $haber : $haber - $debe;
                    }),
            ])
            ->filters([]);
    }
}
