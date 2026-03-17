<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\JournalEntryLine;
use App\Models\AccountPlan;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class LibroMayor extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Informes Financieros';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Libro Mayor';
    protected static string $view = 'filament.app.pages.reports.libro-mayor';

    public ?int $account_plan_id = null;
    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()']),
            \Filament\Actions\Action::make('exportarExcelAction')
                ->label('Exportar Excel')
                ->icon('heroicon-m-table-cells')
                ->color('success')
                ->action('exportarExcel'),
        ];
    }

    public function exportarExcel()
    {
        if (! $this->account_plan_id) {
            \Filament\Notifications\Notification::make()
                ->title('Selecciona una cuenta antes de exportar.')
                ->warning()
                ->send();
            return;
        }

        $tenant = Filament::getTenant();

        $export = new \App\Exports\LibroMayorExport(
            empresaId:     $tenant->id,
            accountPlanId: $this->account_plan_id,
            fechaDesde:    $this->fecha_desde,
            fechaHasta:    $this->fecha_hasta,
            nombreEmpresa: $tenant->name,
        );

        return $export->download('LibroMayor_' . ($this->fecha_desde ?? 'todo') . '.xlsx');
    }

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
                Select::make('account_plan_id')
                    ->label('Cuenta')
                    ->options(AccountPlan::where('empresa_id', Filament::getTenant()->id)->where('accepts_movements', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                DatePicker::make('fecha_desde')
                    ->label('Desde')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                DatePicker::make('fecha_hasta')
                    ->label('Hasta')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
            ])
            ->columns(3);
    }

    public function updatedFilters()
    {
        $data = $this->form->getState();
        $this->account_plan_id = $data['account_plan_id'];
        $this->fecha_desde = $data['fecha_desde'];
        $this->fecha_hasta = $data['fecha_hasta'];
    }

    public function table(Table $table): Table
    {
        $empresaId = Filament::getTenant()->id;

        return $table
            ->query(
                JournalEntryLine::query()
                    ->whereHas('journalEntry', fn($q) =>
                        $q->withoutGlobalScopes()
                          ->where('empresa_id', $empresaId)
                          ->where('status', 'confirmado')
                          ->where('esta_cuadrado', true)
                          ->when($this->fecha_desde, fn($qe) => $qe->whereDate('fecha', '>=', $this->fecha_desde))
                          ->when($this->fecha_hasta, fn($qe) => $qe->whereDate('fecha', '<=', $this->fecha_hasta))
                    )
                    ->when($this->account_plan_id, fn($q) => $q->where('account_plan_id', $this->account_plan_id))
                    ->with(['journalEntry', 'accountPlan'])
            )
            ->columns([
                TextColumn::make('journalEntry.fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('journalEntry.numero')
                    ->label('Asiento')
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap(),
                TextColumn::make('debe')
                    ->label('Debe')
                    ->money('USD')
                    ->summarize([\Filament\Tables\Columns\Summarizers\Sum::make()->label('')]),
                TextColumn::make('haber')
                    ->label('Haber')
                    ->money('USD')
                    ->summarize([\Filament\Tables\Columns\Summarizers\Sum::make()->label('')]),
            ])
            ->filters([])
            ->headerActions([]);
    }
}
