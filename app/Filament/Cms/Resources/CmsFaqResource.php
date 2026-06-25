<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsFaqResource\Pages;
use App\Models\CmsFaq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsFaqResource extends Resource
{
    protected static ?string $model = CmsFaq::class;

    protected static ?string $tenantRelationshipName = 'cmsFaqs';
    protected static ?string $navigationIcon         = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel        = 'Preguntas frecuentes';
    protected static ?string $navigationGroup        = 'Contenido Web';
    protected static ?int    $navigationSort         = 8;
    protected static ?string $modelLabel             = 'Pregunta';
    protected static ?string $pluralModelLabel       = 'Preguntas frecuentes (FAQ)';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('pregunta')->label('Pregunta')->required()->maxLength(300)->columnSpanFull(),
            Forms\Components\Textarea::make('respuesta')->label('Respuesta')->required()->rows(4)->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
            Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('pregunta')->label('Pregunta')->searchable()->limit(80)->weight('semibold'),
                Tables\Columns\TextColumn::make('respuesta')->label('Respuesta')->limit(80)->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsFaqs::route('/'),
            'create' => Pages\CreateCmsFaq::route('/create'),
            'edit'   => Pages\EditCmsFaq::route('/{record}/edit'),
        ];
    }
}
