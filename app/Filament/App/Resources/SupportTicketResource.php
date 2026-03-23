<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static ?string $tenantRelationshipName = 'supportTickets';

    protected static ?string $navigationIcon   = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel  = 'Soporte';
    protected static ?string $navigationGroup  = null;
    protected static ?int    $navigationSort   = 20;
    protected static ?string $modelLabel       = 'Ticket';
    protected static ?string $pluralModelLabel = 'Tickets de soporte';

    public static function canAccess(): bool
    {
        return true;
    }

    /** Admins ven todos los tickets de la empresa; el resto solo los propios. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('empresa_id', Filament::getTenant()?->id);

        if (! auth()->user()?->hasRole(['admin_empresa', 'super_admin'])) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $isAdmin = auth()->user()?->hasRole(['admin_empresa', 'super_admin']);

        return $form->schema([
            Forms\Components\Section::make('Solicitud de soporte')->schema([
                Forms\Components\TextInput::make('asunto')
                    ->label('Asunto')
                    ->required()
                    ->maxLength(200)
                    ->placeholder('Describe brevemente el problema...')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción detallada')
                    ->required()
                    ->rows(5)
                    ->placeholder('Explica con detalle el problema o lo que necesitas...')
                    ->columnSpanFull(),

                Forms\Components\Select::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'baja'  => 'Baja',
                        'media' => 'Media',
                        'alta'  => 'Alta',
                    ])
                    ->default('media')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto'    => 'Abierto',
                        'en_proceso' => 'En proceso',
                        'cerrado'    => 'Cerrado',
                    ])
                    ->default('abierto')
                    ->required()
                    ->visible($isAdmin),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->hasRole(['admin_empresa', 'super_admin']);

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('asunto')
                    ->label('Asunto')
                    ->searchable()
                    ->description(fn (SupportTicket $r) => $isAdmin ? $r->user?->name : null),

                Tables\Columns\TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (SupportTicket $r) => $r->prioridadLabel())
                    ->color(fn (SupportTicket $r) => $r->prioridadColor()),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (SupportTicket $r) => $r->statusLabel())
                    ->color(fn (SupportTicket $r) => $r->statusColor()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->striped()
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->visible(fn () => $isAdmin),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (SupportTicket $r) =>
                        $isAdmin || ($r->user_id === auth()->id() && $r->status === 'abierto')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->visible(fn () => $isAdmin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit'   => Pages\EditSupportTicket::route('/{record}/edit'),
            'view'   => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }
}
