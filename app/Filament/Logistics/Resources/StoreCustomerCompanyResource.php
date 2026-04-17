<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Resources\StoreCustomerCompanyResource\Pages;
use App\Models\StoreCustomer;
use App\Models\StoreCustomerCompany;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoreCustomerCompanyResource extends Resource
{
    protected static ?string $model                  = StoreCustomerCompany::class;
    protected static ?string $tenantRelationshipName = 'storeCustomerCompanies';
    protected static ?string $navigationIcon         = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel        = 'Empresas de clientes';
    protected static ?string $navigationGroup        = 'Clientes';
    protected static ?string $modelLabel             = 'Empresa';
    protected static ?string $pluralModelLabel       = 'Empresas de clientes';
    protected static ?int    $navigationSort         = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Cliente')->schema([
                Select::make('store_customer_id')
                    ->label('Cliente')
                    ->options(function () {
                        return StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', Filament::getTenant()->id)
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => trim($c->nombre . ' ' . ($c->apellido ?? '')) . ' — ' . $c->email,
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),
            ]),

            Section::make('Datos de la empresa')->columns(2)->schema([
                TextInput::make('ruc')
                    ->label('RUC')
                    ->required()
                    ->length(13)
                    ->helperText('Exactamente 13 dígitos'),

                TextInput::make('nombre')
                    ->label('Razón social')
                    ->required()
                    ->maxLength(200),

                TextInput::make('correo')
                    ->label('Correo de la empresa')
                    ->email()
                    ->maxLength(200),

                TextInput::make('cargo')
                    ->label('Cargo del cliente en la empresa')
                    ->maxLength(150)
                    ->placeholder('Ej. Representante legal'),

                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(300)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('storeCustomer.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($state, $record) =>
                        trim(($record->storeCustomer->nombre ?? '') . ' ' . ($record->storeCustomer->apellido ?? ''))
                    )
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('storeCustomer', fn ($q) =>
                            $q->withoutGlobalScopes()
                              ->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%")
                        )
                    )
                    ->sortable(),

                TextColumn::make('ruc')
                    ->label('RUC')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('nombre')
                    ->label('Razón social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->placeholder('—'),

                TextColumn::make('correo')
                    ->label('Correo')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre')
            ->filters([
                SelectFilter::make('store_customer_id')
                    ->label('Cliente')
                    ->options(function () {
                        return StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', Filament::getTenant()->id)
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => trim($c->nombre . ' ' . ($c->apellido ?? '')),
                            ]);
                    })
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreCustomerCompanies::route('/'),
            'create' => Pages\CreateStoreCustomerCompany::route('/create'),
            'edit'   => Pages\EditStoreCustomerCompany::route('/{record}/edit'),
        ];
    }
}
