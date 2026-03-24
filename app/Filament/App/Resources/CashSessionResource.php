<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashRegisterResource;
use App\Filament\App\Resources\CashSessionResource\Pages;
use App\Models\CashSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CashSessionResource extends Resource
{
    protected static ?string $model = CashSession::class;

    protected static ?string $navigationIcon       = 'heroicon-o-clock';
    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Caja';

    protected static ?string $tenantRelationshipName = 'cashSessions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de Sesión')
                    ->schema([
                        Forms\Components\Select::make('cash_register_id')
                            ->label('Caja')
                            ->relationship('cashRegister', 'nombre', fn($query) => $query->where('activo', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionModalHeading('Nueva Caja')
                            ->createOptionForm(fn () => CashRegisterResource::getQuickCreateFormSchema())
                            ->createOptionUsing(function (array $data): int {
                                return \App\Models\CashRegister::create([
                                    ...$data,
                                    'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                    'activo'     => true,
                                ])->getKey();
                            }),
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                        Forms\Components\DatePicker::make('fecha')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('saldo_apertura')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('saldo_cierre')
                            ->label('Saldo Final (Conteo)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\Select::make('estado')
                            ->options([
                                'abierta' => 'Abierta',
                                'cerrada' => 'Cerrada',
                            ])
                            ->default('abierta')
                            ->required(),
                        Forms\Components\Textarea::make('observaciones')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('apertura_at')
                            ->default(now()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashRegister.nombre')
                    ->label('Caja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_apertura')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total_ingresos')
                    ->money('USD')
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_egresos')
                    ->money('USD')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abierta' => 'success',
                        'cerrada' => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'abierta' => 'Abierta',
                        'cerrada' => 'Cerrada',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cerrar')
                    ->label('Cerrar Caja')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn($record) => $record->estado === 'abierta')
                    ->form([
                        Forms\Components\TextInput::make('saldo_cierre')
                            ->label('Saldo en Efectivo al Cierre')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones'),
                    ])
                    ->action(function($record, array $data) {
                        $diferencia = $data['saldo_cierre'] 
                            - ($record->saldo_apertura 
                               + $record->total_ingresos 
                               - $record->total_egresos);
                        
                        $record->update([
                            'saldo_cierre'   => $data['saldo_cierre'],
                            'diferencia'     => $diferencia,
                            'estado'         => 'cerrada',
                            'observaciones'  => $data['observaciones'] ?? null,
                            'cierre_at'      => now(),
                        ]);
                    }),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->estado === 'cerrada'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashSessions::route('/'),
            'create' => Pages\CreateCashSession::route('/create'),
            'edit' => Pages\EditCashSession::route('/{record}/edit'),
        ];
    }
}
