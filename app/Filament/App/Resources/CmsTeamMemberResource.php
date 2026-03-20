<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsTeamMemberResource\Pages;
use App\Models\CmsTeamMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsTeamMemberResource extends Resource
{
    protected static ?string $model = CmsTeamMember::class;

    protected static ?string $tenantRelationshipName = 'cmsTeamMembers';

    protected static ?string $navigationIcon   = 'heroicon-o-user-group';
    protected static ?string $navigationLabel  = 'Equipo';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 4;
    protected static ?string $modelLabel       = 'Integrante';
    protected static ?string $pluralModelLabel = 'Equipo';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->required()->maxLength(150),

                Forms\Components\TextInput::make('cargo')
                    ->label('Cargo / Rol')
                    ->placeholder('Gerente General')
                    ->maxLength(150),

                Forms\Components\Textarea::make('bio')
                    ->label('Biografía corta')
                    ->rows(3)->maxLength(400)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('foto')
                    ->label('Foto')
                    ->image()->disk('public')->directory('cms/team')
                    ->imagePreviewHeight('100')->maxSize(2048)
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
                Tables\Columns\ImageColumn::make('foto')->label('')->circular()->size(40),
                Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('cargo')->label('Cargo')->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsTeamMembers::route('/'),
            'create' => Pages\CreateCmsTeamMember::route('/create'),
            'edit'   => Pages\EditCmsTeamMember::route('/{record}/edit'),
        ];
    }
}
