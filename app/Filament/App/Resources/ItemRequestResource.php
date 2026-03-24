<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ItemRequestResource\Pages;
use App\Models\InventoryItem;
use App\Models\ItemRequest;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemRequestResource extends Resource
{
    protected static ?string $model = ItemRequest::class;

    protected static ?string $tenantRelationshipName = 'itemRequests';

    protected static ?string $navigationIcon  = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Solicitudes de Insumos';
    protected static ?string $navigationGroup = 'Diseño de Producto';
    protected static ?string $modelLabel      = 'Solicitud de Insumo';
    protected static ?string $pluralModelLabel = 'Solicitudes de Insumos';
    protected static ?int    $navigationSort  = 2;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')
                ->label('Nombre del Insumo')
                ->required()
                ->maxLength(150)
                ->columnSpan(2),
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'insumo'        => 'Insumo',
                    'materia_prima' => 'Materia Prima',
                ])
                ->columnSpan(1),
            TextInput::make('unidad_medida_sugerida')
                ->label('Unidad Sugerida')
                ->maxLength(50)
                ->columnSpan(1),
            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobado'  => 'Aprobado',
                    'rechazado' => 'Rechazado',
                ])
                ->required()
                ->default('pendiente')
                ->columnSpan(2),
            Select::make('inventory_item_id')
                ->label('Ítem de Inventario Vinculado')
                ->options(fn () => InventoryItem::where('activo', true)
                    ->get()
                    ->mapWithKeys(fn ($item) => [$item->id => "{$item->codigo} — {$item->nombre}"]))
                ->searchable()
                ->nullable()
                ->helperText('Vincular cuando se aprueba la solicitud y se crea el ítem en inventario.')
                ->columnSpan(2),
            Textarea::make('notas_admin')
                ->label('Notas del Administrador')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Insumo Solicitado')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'insumo'        => 'Insumo',
                        'materia_prima' => 'Materia Prima',
                        default         => ucfirst($state ?? '—'),
                    }),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pendiente' => 'warning',
                        'aprobado'  => 'success',
                        'rechazado' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('requestedBy.name')
                    ->label('Solicitado por')
                    ->searchable(),
                TextColumn::make('inventoryItem.nombre')
                    ->label('Ítem Vinculado')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado'  => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ItemRequest $record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('inventory_item_id')
                            ->label('Vincular a Ítem de Inventario existente')
                            ->options(fn () => InventoryItem::where('activo', true)
                                ->get()
                                ->mapWithKeys(fn ($item) => [$item->id => "{$item->codigo} — {$item->nombre}"]))
                            ->searchable()
                            ->nullable()
                            ->helperText('Opcional: si ya creaste el ítem en inventario, vincúlalo aquí.'),
                        Textarea::make('notas_admin')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (ItemRequest $record, array $data) {
                        $record->update([
                            'estado'            => 'aprobado',
                            'inventory_item_id' => $data['inventory_item_id'] ?? null,
                            'notas_admin'       => $data['notas_admin'] ?? null,
                        ]);
                        Notification::make()
                            ->title('Solicitud aprobada')
                            ->success()
                            ->send();
                    }),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ItemRequest $record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notas_admin')
                            ->label('Motivo del rechazo')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (ItemRequest $record, array $data) {
                        $record->update([
                            'estado'      => 'rechazado',
                            'notas_admin' => $data['notas_admin'],
                        ]);
                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->danger()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListItemRequests::route('/'),
            'create' => Pages\CreateItemRequest::route('/create'),
            'edit'   => Pages\EditItemRequest::route('/{record}/edit'),
        ];
    }
}
