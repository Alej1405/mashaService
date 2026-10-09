<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioFotoAlbumResource\Pages;
use App\Models\SitioFotoAlbum;
use App\Models\SitioFotoCategoria;
use App\Support\SitioPropio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Los álbumes del portafolio de fotografía. Cada uno pertenece a una
 * categoría y tiene su propia página: mashaec.net/fotografia/<álbum>.
 */
class SitioFotoAlbumResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioFotoAlbum::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-photo';
    protected static ?string $navigationLabel        = 'Fotografía · Álbumes';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 4;
    protected static ?string $modelLabel             = 'Álbum';
    protected static ?string $pluralModelLabel       = 'Álbumes de fotos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('El álbum')->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')->required()->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                Forms\Components\TextInput::make('slug')
                    ->label('En la dirección')->prefix('/fotografia/')
                    ->required()->maxLength(150),
                Forms\Components\Select::make('categoria_id')
                    ->label('Categoría')
                    ->options(fn () => SitioFotoCategoria::withoutGlobalScopes()
                        ->where('empresa_id', SitioPropio::empresaId())
                        ->orderBy('sort_order')
                        ->pluck('nombre', 'id'))
                    ->required()
                    ->native(false),
                Forms\Components\DatePicker::make('fecha')->label('Fecha de la sesión')->native(false),
                Forms\Components\Textarea::make('descripcion')->label('Descripción')->rows(3)->columnSpanFull(),
                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
            ]),
            Forms\Components\Section::make('Fotos')
                ->description('Súbelas todas de una vez y arrástralas para ordenarlas. La portada es la que se ve en el portafolio; si la dejas vacía, se usa la primera foto.')
                ->schema([
                    Forms\Components\FileUpload::make('portada')
                        ->label('Portada')
                        ->image()->disk('public')->directory('sitio/fotografia')
                        ->imagePreviewHeight('160')
                        ->openable(),
                    Forms\Components\FileUpload::make('fotos')
                        ->label('Fotos del álbum')
                        ->image()->disk('public')->directory('sitio/fotografia')
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->panelLayout('grid')
                        ->imagePreviewHeight('140')
                        ->maxSize(15360)
                        ->openable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('portada')->label('')->disk('public')->height(40)
                    ->getStateUsing(fn (SitioFotoAlbum $record) => $record->portada ?: ($record->fotos[0] ?? null)),
                Tables\Columns\TextColumn::make('titulo')->label('Álbum')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría')->badge(),
                Tables\Columns\TextColumn::make('fotos')->label('Fotos')
                    ->state(fn (SitioFotoAlbum $record): int => count($record->fotos ?? [])),
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')->label('Categoría')->relationship('categoria', 'nombre'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioFotoAlbumes::route('/'),
            'create' => Pages\CreateSitioFotoAlbum::route('/create'),
            'edit'   => Pages\EditSitioFotoAlbum::route('/{record}/edit'),
        ];
    }
}
