<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Resources\ConsignatarioResource\Pages;
use App\Models\LogisticsConsignatario;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsignatarioResource extends Resource
{
    protected static ?string $model                  = LogisticsConsignatario::class;
    protected static ?string $tenantRelationshipName = 'logisticsConsignatarios';
    protected static ?string $navigationIcon         = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Consignatarios';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $modelLabel      = 'Consignatario';
    protected static ?string $pluralModelLabel = 'Consignatarios';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos del consignatario')->schema([
                TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('cedula_pasaporte')
                    ->label('Cédula / Pasaporte / RUC')
                    ->helperText('Requerido para declaraciones de aduana SENAE.')
                    ->columnSpan(1),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->columnSpan(1),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->columnSpan(1),
            ])->columns(2),

            Section::make('Dirección de entrega')->schema([
                Textarea::make('direccion_destino')
                    ->label('Dirección en Ecuador')
                    ->rows(2)
                    ->columnSpanFull(),
            ]),

            Section::make('Notas')->collapsed()->schema([
                Textarea::make('notas')->label('Notas')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cedula_pasaporte')
                    ->label('CI / Pasaporte')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('total_embarques')
                    ->label('Embarques')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                TextColumn::make('valor_declarado_acumulado')
                    ->label('Valor acumulado declarado')
                    ->money('USD')
                    ->sortable()
                    ->description(fn ($record) => $record->valor_declarado_acumulado > 400
                        ? '⚠️ Supera $400 — requiere revisión'
                        : null),
            ])
            ->defaultSort('nombre')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListConsignatarios::route('/'),
            'create' => Pages\CreateConsignatario::route('/create'),
            'edit'   => Pages\EditConsignatario::route('/{record}/edit'),
        ];
    }
}
