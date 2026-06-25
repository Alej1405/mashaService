<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsTerminosResource\Pages;
use App\Models\CmsTerminos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsTerminosResource extends Resource
{
    protected static ?string $model = CmsTerminos::class;

    protected static ?string $tenantRelationshipName = 'cmsTerminos';
    protected static ?string $navigationIcon         = 'heroicon-o-document-text';
    protected static ?string $navigationLabel        = 'Términos y Condiciones';
    protected static ?string $navigationGroup        = 'Legal';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $modelLabel             = 'Términos';
    protected static ?string $pluralModelLabel       = 'Términos y Condiciones';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Documento legal')
                ->icon('heroicon-o-scale')
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título del documento')
                        ->required()
                        ->default('Términos y Condiciones')
                        ->maxLength(200)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('contenido')
                        ->label('Contenido')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'bulletList', 'orderedList',
                            'h2', 'h3', 'paragraph',
                            'link', 'blockquote', 'undo', 'redo',
                        ])
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('ultima_actualizacion')
                        ->label('Última actualización')
                        ->default(now())
                        ->native(false),

                    Forms\Components\Toggle::make('activo')->label('Visible en el sitio')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->label('Documento')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('ultima_actualizacion')->label('Actualizado')->date('d/m/Y')->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsTerminos::route('/'),
            'create' => Pages\CreateCmsTerminos::route('/create'),
            'edit'   => Pages\EditCmsTerminos::route('/{record}/edit'),
        ];
    }
}
