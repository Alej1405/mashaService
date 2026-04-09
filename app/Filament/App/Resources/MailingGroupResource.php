<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailingGroupResource\Pages;
use App\Models\MailingGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MailingGroupResource extends Resource
{
    protected static ?string $model = MailingGroup::class;
    protected static ?string $tenantRelationshipName = 'mailingGroups';

    protected static ?string $navigationIcon   = 'heroicon-o-user-group';
    protected static ?string $navigationLabel  = 'Grupos';
    protected static ?string $navigationGroup  = 'Mailing';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Grupo';
    protected static ?string $pluralModelLabel = 'Grupos de contactos';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del grupo')
                    ->required()
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Grupo')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('active_contacts')
                    ->label('Activos')
                    ->getStateUsing(fn (MailingGroup $r) => $r->contacts()->where('active', true)->count())
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Total')
                    ->counts('contacts')
                    ->alignCenter()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('campaigns_count')
                    ->label('Campañas')
                    ->counts('campaigns')
                    ->alignCenter()
                    ->color('primary')
                    ->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->striped()
            ->actions([
                Tables\Actions\Action::make('ver_contactos')
                    ->label('Ver contactos')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(fn (MailingGroup $r) => static::getUrl('view', ['record' => $r])),

                Tables\Actions\EditAction::make()
                    ->label('Renombrar')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailingGroups::route('/'),
            'view'  => Pages\ViewMailingGroup::route('/{record}'),
            'edit'  => Pages\EditMailingGroup::route('/{record}/edit'),
        ];
    }
}
