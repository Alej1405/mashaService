<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SoporteTicketResource\Pages;
use App\Filament\App\Resources\SupportTicketResource;
use App\Models\Scopes\EmpresaScope;
use App\Models\SupportTicket;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tickets de soporte de todas las empresas, para atenderlos desde el admin.
 * La vista del hilo y "Responder" son las mismas del panel de la empresa.
 */
class SoporteTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $slug = 'tickets-soporte';
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Tickets de soporte';
    protected static ?string $navigationGroup = 'Monitoreo';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'ticket';
    protected static ?string $pluralModelLabel = 'Tickets de soporte';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /** El admin no tiene empresa activa: sin el filtro por empresa ve todos los tickets. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(EmpresaScope::class);
    }

    /** Tickets sin revisar: los que nadie de soporte ha respondido todavía. */
    public static function getNavigationBadge(): ?string
    {
        $abiertos = static::getEloquentQuery()->where('status', SupportTicket::ABIERTO)->count();

        return $abiertos ? (string) $abiertos : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Tickets sin revisar';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return SupportTicketResource::infolist($infolist);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Lo que hay que atender va arriba: abiertos, luego en proceso, al final cerrados.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['empresa:id,name', 'user:id,name'])->withCount('mensajes')
                ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [SupportTicket::ABIERTO, SupportTicket::EN_PROCESO]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('empresa.name')
                    ->label('Empresa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('asunto')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(60)
                    ->description(fn (SupportTicket $record) => $record->user?->name),
                Tables\Columns\TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (SupportTicket $record) => $record->prioridadLabel())
                    ->color(fn (SupportTicket $record) => $record->prioridadColor()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (SupportTicket $record) => $record->statusLabel())
                    ->color(fn (SupportTicket $record) => $record->statusColor()),
                Tables\Columns\TextColumn::make('mensajes_count')
                    ->label('Mensajes')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SupportTicket::opcionesEstado()),
                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Atender'),
            ])
            ->emptyStateHeading('No hay tickets')
            ->emptyStateDescription('Cuando una empresa abra un ticket de soporte, aparece aquí.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoporteTickets::route('/'),
            'view'  => Pages\ViewSoporteTicket::route('/{record}'),
        ];
    }
}
