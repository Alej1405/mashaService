<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SupplierResource\Pages;
use App\Filament\App\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->default(fn () => 'PRV-' . strtoupper(uniqid()))
                            ->readOnly()
                            ->required(),
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre/Razón Social')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial')
                            ->maxLength(255),
                        Forms\Components\Select::make('tipo_persona')
                            ->label('Tipo de Persona')
                            ->options([
                                'juridica' => 'Jurídica',
                                'natural' => 'Natural',
                            ])
                            ->required(),
                        Forms\Components\Select::make('tipo_identificacion')
                            ->label('Tipo de Identificación')
                            ->options([
                                'ruc' => 'RUC',
                                'cedula' => 'Cédula',
                                'pasaporte' => 'Pasaporte',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('numero_identificacion')
                            ->label('Número de Identificación')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Tipo de Proveedor')
                    ->schema([
                        Forms\Components\CheckboxList::make('tipo_proveedor')
                            ->label('Clasificación')
                            ->options([
                                'insumos' => 'Insumos',
                                'materias_primas' => 'Materias Primas',
                                'productos_terminados' => 'Productos Terminados',
                                'servicios' => 'Servicios',
                                'activos_fijos' => 'Activos Fijos',
                            ])
                            ->required()
                            ->columns(3),
                        Forms\Components\Toggle::make('es_importador')
                            ->label('Es Importador')
                            ->visible(fn () => filament()->getTenant()->tiene_comercio_exterior ?? false)
                            ->reactive()
                            ->default(false),
                        Forms\Components\TextInput::make('pais_origen')
                            ->label('País de Origen')
                            ->visible(fn (Forms\Get $get) => $get('es_importador'))
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('contacto_principal')
                            ->label('Contacto Principal')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono_principal')
                            ->label('Teléfono Principal')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono_secundario')
                            ->label('Teléfono Secundario')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('correo_principal')
                            ->label('Correo Principal')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('correo_secundario')
                            ->label('Correo Secundario')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('pais')
                            ->label('País')
                            ->default('Ecuador')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Contabilidad')
                    ->schema([
                        Forms\Components\Select::make('cuenta_contable_id')
                            ->label('Cuenta Contable (Pasivo)')
                            ->relationship(
                                'accountPlan',
                                'name',
                                fn ($query) => $query->where('accepts_movements', true)->where('type', 'pasivo')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('activo')
                            ->label('Proveedor Activo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_proveedor')
                    ->label('Tipo(s)')
                    ->badge(),
                Tables\Columns\TextColumn::make('numero_identificacion')
                    ->label('Identificación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pais')
                    ->label('País')
                    ->searchable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('es_importador')
                    ->label('Importador')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('es_importador')
                    ->label('Es Importador')
                    ->options([
                        '1' => 'Sí',
                        '0' => 'No',
                    ]),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getQuickCreateFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre/Razón Social')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('tipo_persona')
                ->label('Tipo de Persona')
                ->options([
                    'juridica' => 'Jurídica',
                    'natural' => 'Natural',
                ])
                ->required(),
            Forms\Components\Select::make('tipo_identificacion')
                ->label('Tipo de Identificación')
                ->options([
                    'ruc' => 'RUC',
                    'cedula' => 'Cédula',
                    'pasaporte' => 'Pasaporte',
                ])
                ->required(),
            Forms\Components\TextInput::make('numero_identificacion')
                ->label('Número de Identificación')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('contacto_principal')
                ->label('Contacto Principal')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('telefono_principal')
                ->label('Teléfono Principal')
                ->tel()
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('correo_principal')
                ->label('Correo Principal')
                ->email()
                ->required()
                ->maxLength(255),
        ];
    }
}
