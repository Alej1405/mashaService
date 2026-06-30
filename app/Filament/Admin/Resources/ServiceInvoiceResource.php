<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServiceInvoiceResource\Pages;
use App\Models\Empresa;
use App\Models\ServiceInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceInvoiceResource extends Resource
{
    protected static ?string $model            = ServiceInvoice::class;
    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationLabel  = 'Facturación';
    protected static ?string $navigationGroup  = 'Clientes';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Factura';
    protected static ?string $pluralModelLabel = 'Facturas';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la factura')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('empresa_id')
                        ->label('Empresa')
                        ->relationship('empresa', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('periodo')
                        ->label('Periodo (ej. 2026-05)')
                        ->placeholder('YYYY-MM')
                        ->required(),

                    Forms\Components\Select::make('plan')
                        ->label('Plan facturado')
                        ->options(['basic' => 'Basic', 'pro' => 'Pro', 'enterprise' => 'Enterprise'])
                        ->required(),

                    Forms\Components\TextInput::make('monto')
                        ->label('Monto (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_emision')
                        ->label('Fecha de emisión')
                        ->default(now())
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_vencimiento')
                        ->label('Fecha de vencimiento')
                        ->default(now()->addDays(30))
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(['pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido'])
                        ->default('pendiente')
                        ->required()
                        ->live(),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->label('Fecha de pago')
                        ->visible(fn (Forms\Get $get): bool => $get('estado') === 'pagado'),

                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de pago')
                        ->options([
                            'transferencia' => 'Transferencia bancaria',
                            'tarjeta'       => 'Tarjeta de crédito',
                            'efectivo'      => 'Efectivo',
                            'paypal'        => 'PayPal',
                            'otro'          => 'Otro',
                        ])
                        ->visible(fn (Forms\Get $get): bool => $get('estado') === 'pagado'),

                    Forms\Components\Textarea::make('notas')
                        ->label('Notas')
                        ->columnSpanFull()
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_emision', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N° Factura')
                    ->searchable()
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('empresa.name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('periodo')
                    ->label('Periodo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan')
                    ->colors([
                        'gray'    => 'basic',
                        'primary' => 'pro',
                        'warning' => 'enterprise',
                    ]),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'pagado',
                        'danger'  => 'vencido',
                    ]),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (ServiceInvoice $r): string => $r->estado === 'pendiente' && $r->fecha_vencimiento->isPast() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Pagado el')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(['pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido']),

                Tables\Filters\SelectFilter::make('plan')
                    ->options(['basic' => 'Basic', 'pro' => 'Pro', 'enterprise' => 'Enterprise']),

                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('marcar_pagado')
                    ->label('Marcar pagado')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceInvoice $r): bool => $r->estado !== 'pagado')
                    ->form([
                        Forms\Components\DatePicker::make('fecha_pago')
                            ->label('Fecha de pago')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('metodo_pago')
                            ->label('Método de pago')
                            ->options([
                                'transferencia' => 'Transferencia bancaria',
                                'tarjeta'       => 'Tarjeta de crédito',
                                'efectivo'      => 'Efectivo',
                                'paypal'        => 'PayPal',
                                'otro'          => 'Otro',
                            ])
                            ->required(),
                    ])
                    ->action(function (ServiceInvoice $record, array $data): void {
                        $record->update([
                            'estado'       => 'pagado',
                            'fecha_pago'   => $data['fecha_pago'],
                            'metodo_pago'  => $data['metodo_pago'],
                        ]);
                        Notification::make()->title('Factura marcada como pagada')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('marcar_vencido')
                    ->label('Marcar como vencidas')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['estado' => 'vencido']))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceInvoices::route('/'),
            'create' => Pages\CreateServiceInvoice::route('/create'),
            'edit'   => Pages\EditServiceInvoice::route('/{record}/edit'),
        ];
    }
}
