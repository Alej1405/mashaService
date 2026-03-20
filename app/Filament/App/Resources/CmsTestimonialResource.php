<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsTestimonialResource\Pages;
use App\Models\CmsTestimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsTestimonialResource extends Resource
{
    protected static ?string $model = CmsTestimonial::class;

    protected static ?string $tenantRelationshipName = 'cmsTestimonials';

    protected static ?string $navigationIcon   = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel  = 'Testimonios';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 6;
    protected static ?string $modelLabel       = 'Testimonio';
    protected static ?string $pluralModelLabel = 'Testimonios';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('autor_nombre')
                    ->label('Nombre del cliente')->required()->maxLength(150),

                Forms\Components\TextInput::make('autor_cargo')
                    ->label('Cargo')->placeholder('Gerente de Operaciones')->maxLength(150),

                Forms\Components\TextInput::make('autor_empresa')
                    ->label('Empresa')->maxLength(150),

                Forms\Components\Select::make('estrellas')
                    ->label('Calificación')
                    ->options([5 => '⭐⭐⭐⭐⭐', 4 => '⭐⭐⭐⭐', 3 => '⭐⭐⭐', 2 => '⭐⭐', 1 => '⭐'])
                    ->default(5),

                Forms\Components\Textarea::make('contenido')
                    ->label('Testimonio')
                    ->required()->rows(4)->maxLength(600)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('autor_foto')
                    ->label('Foto del cliente (opcional)')
                    ->image()->disk('public')->directory('cms/testimonials')
                    ->imagePreviewHeight('80')->maxSize(1024)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('autor_foto')->label('')->circular()->size(36),
                Tables\Columns\TextColumn::make('autor_nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('autor_empresa')->label('Empresa')->color('gray'),
                Tables\Columns\TextColumn::make('contenido')->label('Testimonio')->limit(60)->color('gray'),
                Tables\Columns\TextColumn::make('estrellas')->label('★')->alignCenter()
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state)),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsTestimonials::route('/'),
            'create' => Pages\CreateCmsTestimonial::route('/create'),
            'edit'   => Pages\EditCmsTestimonial::route('/{record}/edit'),
        ];
    }
}
