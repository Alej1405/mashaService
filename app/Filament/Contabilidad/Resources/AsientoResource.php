<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\AsientoResource\Pages;
use App\Models\JournalEntry;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Libro diario: todos los asientos del periodo, con su detalle. */
class AsientoResource extends Resource
{
    protected static ?string $model = JournalEntry::class;
    protected static ?string $slug = 'asientos';
    protected static ?string $navigationIcon  = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Libro diario';
    protected static ?string $modelLabel = 'asiento';
    protected static ?string $pluralModelLabel = 'Libro diario';
    protected static ?int    $navigationSort = 1;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('numero')->label('Número')->searchable(),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->searchable()->wrap()
                    ->description(fn ($record) => $record->referencia_tipo ? 'origen: ' . $record->referencia_tipo : null),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst(str_replace('_', ' ', (string) $state))),
                Tables\Columns\TextColumn::make('total_debe')->label('Debe')->alignEnd()
                    ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Debe')
                        ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))),
                Tables\Columns\TextColumn::make('total_haber')->label('Haber')->alignEnd()
                    ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Haber')
                        ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))),
                Tables\Columns\IconColumn::make('esta_cuadrado')->label('Cuadrado')->boolean(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'confirmado' => 'success', 'anulado' => 'danger', default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->label('Tipo')->options(fn () =>
                    JournalEntry::query()->select('tipo')->distinct()->pluck('tipo', 'tipo')->all()),
                Tables\Filters\Filter::make('descuadrados')->label('Solo descuadrados')
                    ->query(fn ($q) => $q->where('esta_cuadrado', false)),
            ])
            ->actions([Tables\Actions\ViewAction::make()->label('Ver')->slideOver()]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->columns(3)->schema([
                Infolists\Components\TextEntry::make('numero')->label('Número'),
                Infolists\Components\TextEntry::make('fecha')->label('Fecha')->date('d/m/Y'),
                Infolists\Components\TextEntry::make('status')->label('Estado')->badge(),
                Infolists\Components\TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
            ]),
            Infolists\Components\Section::make('Líneas')->schema([
                Infolists\Components\RepeatableEntry::make('lines')
                    ->label('')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('accountPlan.code')->label('Cuenta'),
                        Infolists\Components\TextEntry::make('descripcion')->label('Detalle')->columnSpan(1),
                        Infolists\Components\TextEntry::make('debe')->label('Debe')
                            ->formatStateUsing(fn ($state) => (float) $state ? '$ ' . number_format((float) $state, 2, ',', '.') : '—'),
                        Infolists\Components\TextEntry::make('haber')->label('Haber')
                            ->formatStateUsing(fn ($state) => (float) $state ? '$ ' . number_format((float) $state, 2, ',', '.') : '—'),
                    ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAsientos::route('/')];
    }
}
