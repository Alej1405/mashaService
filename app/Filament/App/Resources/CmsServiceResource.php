<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsServiceResource\Pages;
use App\Models\CmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsServiceResource extends Resource
{
    protected static ?string $model = CmsService::class;

    protected static ?string $tenantRelationshipName = 'cmsServices';

    protected static ?string $navigationIcon   = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel  = 'Servicios';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 3;
    protected static ?string $modelLabel       = 'Servicio';
    protected static ?string $pluralModelLabel = 'Servicios';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Nombre del servicio')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('caracteristicas')
                    ->label('Características del servicio')
                    ->schema([
                        Forms\Components\TextInput::make('texto')
                            ->label('Característica')
                            ->required()
                            ->maxLength(200)
                            ->placeholder('Ej: Entrega en 24 horas'),
                    ])
                    ->addActionLabel('Agregar característica')
                    ->defaultItems(0)
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('icono')
                    ->label('Ícono (emoji o nombre heroicon)')
                    ->placeholder('🚚  ó  heroicon-o-truck')
                    ->maxLength(60)
                    ->helperText('Escribe un emoji o el nombre de un heroicon.'),

                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen del servicio')
                    ->image()
                    ->disk('public')
                    ->directory('cms/services')
                    ->imagePreviewHeight('80')
                    ->maxSize(2048),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('icono')->label('')->alignCenter(),
                Tables\Columns\TextColumn::make('titulo')->label('Servicio')->searchable(),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->limit(60)->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsServices::route('/'),
            'create' => Pages\CreateCmsService::route('/create'),
            'edit'   => Pages\EditCmsService::route('/{record}/edit'),
        ];
    }
}
