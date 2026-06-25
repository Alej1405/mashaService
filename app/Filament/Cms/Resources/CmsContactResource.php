<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsContactResource\Pages;
use App\Models\CmsContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsContactResource extends Resource
{
    protected static ?string $model = CmsContact::class;

    protected static ?string $tenantRelationshipName = 'cmsContacts';
    protected static ?string $navigationIcon         = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel        = 'Contacto';
    protected static ?string $navigationGroup        = 'Contacto';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $modelLabel             = 'Contacto';
    protected static ?string $pluralModelLabel       = 'Contacto';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de contacto')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('direccion')
                        ->label('Dirección física')
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo de contacto')
                        ->email()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('whatsapp')
                        ->label('WhatsApp (número con código de país)')
                        ->placeholder('+593999999999')
                        ->maxLength(30),
                ])->columns(2),

            Forms\Components\Section::make('Redes Sociales')
                ->icon('heroicon-o-share')
                ->schema([
                    Forms\Components\TextInput::make('facebook')->label('Facebook')->url()->maxLength(300),
                    Forms\Components\TextInput::make('instagram')->label('Instagram')->url()->maxLength(300),
                    Forms\Components\TextInput::make('linkedin')->label('LinkedIn')->url()->maxLength(300),
                    Forms\Components\TextInput::make('youtube')->label('YouTube')->url()->maxLength(300),
                    Forms\Components\TextInput::make('tiktok')->label('TikTok')->url()->maxLength(300),
                ])->columns(2),

            Forms\Components\Section::make('Mapa')
                ->icon('heroicon-o-map')
                ->schema([
                    Forms\Components\Textarea::make('mapa_embed')
                        ->label('Código embed de Google Maps')
                        ->rows(4)
                        ->placeholder('<iframe src="https://www.google.com/maps/embed?..." ...></iframe>')
                        ->helperText('Pega el código iframe de Google Maps → Compartir → Insertar mapa.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Toggle::make('activo')->label('Visible en el sitio')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable(),
                Tables\Columns\TextColumn::make('telefono')->label('Teléfono')->color('gray'),
                Tables\Columns\TextColumn::make('whatsapp')->label('WhatsApp')->color('gray'),
                Tables\Columns\IconColumn::make('facebook')->label('FB')->boolean()->getStateUsing(fn ($record) => (bool) $record->facebook),
                Tables\Columns\IconColumn::make('instagram')->label('IG')->boolean()->getStateUsing(fn ($record) => (bool) $record->instagram),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsContacts::route('/'),
            'create' => Pages\CreateCmsContact::route('/create'),
            'edit'   => Pages\EditCmsContact::route('/{record}/edit'),
        ];
    }
}
