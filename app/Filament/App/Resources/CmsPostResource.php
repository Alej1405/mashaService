<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsPostResource\Pages;
use App\Models\CmsPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CmsPostResource extends Resource
{
    protected static ?string $model = CmsPost::class;

    protected static ?string $tenantRelationshipName = 'cmsPosts';

    protected static ?string $navigationIcon   = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel  = 'Noticias';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 9;
    protected static ?string $modelLabel       = 'Noticia';
    protected static ?string $pluralModelLabel = 'Noticias';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título de la noticia')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) =>
                        $set('slug', Str::slug($state ?? ''))
                    )
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->label('URL amigable (slug)')
                    ->required()
                    ->maxLength(200)
                    ->helperText('Se genera automáticamente del título. Puedes editarlo.')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('contenido')
                    ->label('Contenido de la noticia')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline',
                        'bulletList', 'orderedList',
                        'h2', 'h3', 'paragraph',
                        'link', 'blockquote', 'undo', 'redo',
                    ])
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen principal')
                    ->image()->disk('public')->directory('cms/posts')
                    ->imagePreviewHeight('120')->maxSize(3072)
                    ->helperText('JPG o PNG. Recomendado: 1200×630 px.')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('publicado_en')
                    ->label('Fecha de publicación')
                    ->placeholder('Dejar vacío para publicar ahora')
                    ->helperText('Si se deja vacío, se usará la fecha actual al activar.'),

                Forms\Components\Toggle::make('activo')->label('Publicada')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->height(40)->width(64),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->description(fn (CmsPost $r) => $r->slug),

                Tables\Columns\TextColumn::make('publicado_en')
                    ->label('Publicada')
                    ->since()
                    ->placeholder('Sin fecha')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\ToggleColumn::make('activo')->label('Publicada'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsPosts::route('/'),
            'create' => Pages\CreateCmsPost::route('/create'),
            'edit'   => Pages\EditCmsPost::route('/{record}/edit'),
        ];
    }
}
