<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\JournalEntry;
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
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Actions\Action;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class LibroDiario extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Informes';
    protected static ?string $navigationIcon       = 'heroicon-o-document-text';
    protected static ?string $title                = 'Libro Diario';
    protected static string $view = 'filament.app.pages.reports.libro-diario';

    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;
    public ?string $tipo = null;

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
        $tenant = Filament::getTenant();

        $export = new \App\Exports\LibroDiarioExport(
            empresaId:     $tenant->id,
            fechaDesde:    $this->fecha_desde,
            fechaHasta:    $this->fecha_hasta,
            tipo:          $this->tipo,
            nombreEmpresa: $tenant->name,
        );

        return $export->download('LibroDiario_' . ($this->fecha_desde ?? 'todo') . '.xlsx');
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
                DatePicker::make('fecha_desde')
                    ->label('Desde')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                DatePicker::make('fecha_hasta')
                    ->label('Hasta')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'compra'  => 'Compra',
                        'venta'   => 'Venta',
                        'ajuste'  => 'Ajuste',
                        'apertura'=> 'Apertura',
                        'manual'  => 'Manual',
                    ])
                    ->placeholder('Todos')
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
            ])
            ->columns(3);
    }

    public function updatedFilters()
    {
        $data = $this->form->getState();
        $this->fecha_desde = $data['fecha_desde'];
        $this->fecha_hasta = $data['fecha_hasta'];
        $this->tipo = $data['tipo'];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JournalEntry::withoutGlobalScopes()
                    ->where('empresa_id', Filament::getTenant()->id)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->when($this->fecha_desde, fn($q) => $q->whereDate('fecha', '>=', $this->fecha_desde))
                    ->when($this->fecha_hasta, fn($q) => $q->whereDate('fecha', '<=', $this->fecha_hasta))
                    ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
            )
            ->columns([
                TextColumn::make('numero')
                    ->label('Número')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('total_debe')
                    ->label('Total Debe')
                    ->money('USD')
                    ->summarize([\Filament\Tables\Columns\Summarizers\Sum::make()->label('')]),
                TextColumn::make('total_haber')
                    ->label('Total Haber')
                    ->money('USD')
                    ->summarize([\Filament\Tables\Columns\Summarizers\Sum::make()->label('')]),
            ])
            ->filters([])
            ->actions([])
            ->contentGrid([
                'md' => 1,
            ]);
    }
}
