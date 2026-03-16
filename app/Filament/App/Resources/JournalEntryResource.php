<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\JournalEntryResource\Pages;
use App\Filament\App\Resources\JournalEntryResource\RelationManagers;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class JournalEntryResource extends Resource
{
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $navigationLabel = 'Asientos Contables';
    protected static ?string $pluralLabel = 'Asientos Contables';
    protected static ?string $modelLabel = 'Asiento Contable';
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Encabezado del Asiento')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero')
                                    ->label('Número')
                                    ->disabled()
                                    ->placeholder('Autogenerado'),
                                Forms\Components\DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo de Asiento')
                                    ->options([
                                        'manual' => 'Manual',
                                        'ajuste' => 'Ajuste',
                                        'apertura' => 'Apertura',
                                    ])
                                    ->required()
                                    ->default('manual'),
                            ]),
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción / Glosa')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Líneas del Asiento')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('account_plan_id')
                                    ->label('Cuenta Contable')
                                    ->relationship('accountPlan', 'name', function ($query) {
                                        return $query->where('accepts_movements', true);
                                    })
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción línea')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('debe')
                                    ->label('Debe')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $state > 0 ? $set('haber', 0) : null),
                                Forms\Components\TextInput::make('haber')
                                    ->label('Haber')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $state > 0 ? $set('debe', 0) : null),
                            ])
                            ->columns(11)
                            ->defaultItems(2)
                            ->reorderableWithButtons()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $lines = $get('lines') ?? [];
                                $totalDebe = collect($lines)->sum('debe');
                                $totalHaber = collect($lines)->sum('haber');
                                $diferencia = $totalDebe - $totalHaber;
                                
                                $set('total_debe', $totalDebe);
                                $set('total_haber', $totalHaber);
                                $set('esta_cuadrado', bccomp($totalDebe, $totalHaber, 2) === 0);
                            }),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('total_debe_display')
                                    ->label('Total DEBE')
                                    ->content(fn (Forms\Get $get) => '$ ' . number_format(collect($get('lines'))->sum('debe'), 2)),
                                Forms\Components\Placeholder::make('total_haber_display')
                                    ->label('Total HABER')
                                    ->content(fn (Forms\Get $get) => '$ ' . number_format(collect($get('lines'))->sum('haber'), 2)),
                                Forms\Components\Placeholder::make('diferencia_display')
                                    ->label('Diferencia')
                                    ->extraAttributes(fn (Forms\Get $get) => [
                                        'style' => (collect($get('lines'))->sum('debe') - collect($get('lines'))->sum('haber')) != 0 
                                            ? 'color: red; font-weight: bold;' 
                                            : 'color: green; font-weight: bold;'
                                    ])
                                    ->content(fn (Forms\Get $get) => '$ ' . number_format(collect($get('lines'))->sum('debe') - collect($get('lines'))->sum('haber'), 2)),
                                Forms\Components\Placeholder::make('cuadrado_display')
                                    ->label('Estado')
                                    ->content(fn (Forms\Get $get) => bccomp(collect($get('lines'))->sum('debe'), collect($get('lines'))->sum('haber'), 2) === 0 ? '✓ Cuadrado' : '✗ Descuadrado'),
                            ]),
                        
                        Forms\Components\Hidden::make('total_debe')->default(0),
                        Forms\Components\Hidden::make('total_haber')->default(0),
                        Forms\Components\Hidden::make('esta_cuadrado')->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->fontFamily('mono')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'apertura' => 'info',
                        'manual' => 'gray',
                        'compra' => 'success',
                        'venta' => 'warning',
                        'manufactura' => 'primary',
                        'ajuste' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_debe')
                    ->label('Debe')
                    ->money('USD')
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('total_haber')
                    ->label('Haber')
                    ->money('USD')
                    ->alignment('right'),
                Tables\Columns\IconColumn::make('esta_cuadrado')
                    ->label('Cuadrado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'confirmado' => 'success',
                        'anulado' => 'danger',
                    }),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'manual' => 'Manual',
                        'apertura' => 'Apertura',
                        'ajuste' => 'Ajuste',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'borrador' => 'Borrador',
                        'confirmado' => 'Confirmado',
                        'anulado' => 'Anulado',
                    ]),
                Tables\Filters\TernaryFilter::make('esta_cuadrado')
                    ->label('¿Está cuadrado?'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'borrador' || !$record->esta_cuadrado)
                    ->action(fn ($record) => $record->update([
                        'status' => 'confirmado',
                        'confirmado_por' => Auth::id(),
                        'confirmado_at' => now(),
                    ])),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'confirmado' || $record->tipo === 'apertura')
                    ->action(fn ($record) => \App\Services\AccountingService::revertirAsiento($record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status !== 'borrador'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
