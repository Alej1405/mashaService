<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioMensajeResource\Pages;
use App\Models\SitioMensaje;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Bandeja del formulario de un solo campo. Los mensajes llegan de la API:
 * aqui no se crean, solo se leen y se marcan como atendidos.
 */
class SitioMensajeResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioMensaje::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel        = 'Mensajes';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 6;
    protected static ?string $modelLabel             = 'Mensaje';
    protected static ?string $pluralModelLabel       = 'Mensajes del sitio';

    /** Contador de pendientes en el menú. */
    public static function getNavigationBadge(): ?string
    {
        $pendientes = static::getEloquentQuery()->where('atendido', false)->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('contacto')->label('Contacto')->disabled(),
                Forms\Components\Textarea::make('mensaje')->label('Mensaje')->rows(4)->disabled(),
                Forms\Components\TextInput::make('origen')->label('Escribió desde')->disabled(),
                Forms\Components\Toggle::make('atendido')->label('Atendido'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('contacto')->label('Contacto')->weight('semibold')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('mensaje')->label('Mensaje')->limit(60)->color('gray'),
                Tables\Columns\TextColumn::make('origen')->label('Desde')->color('gray'),
                Tables\Columns\ToggleColumn::make('atendido')
                    ->label('Atendido')
                    ->afterStateUpdated(fn (SitioMensaje $record, bool $state) => $record
                        ->forceFill(['atendido_en' => $state ? now() : null])
                        ->save()),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('atendido')->label('Atendido'),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSitioMensajes::route('/')];
    }
}
