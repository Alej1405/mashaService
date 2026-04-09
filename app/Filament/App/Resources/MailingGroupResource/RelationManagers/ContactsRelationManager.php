<?php

namespace App\Filament\App\Resources\MailingGroupResource\RelationManagers;

use App\Models\MailingContact;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $label       = 'contacto';
    protected static ?string $pluralLabel = 'contactos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre completo')
                            ->maxLength(150),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'mailing_contacts',
                                column: 'email',
                                modifyRuleUsing: fn ($rule) => $rule->where('empresa_id', Filament::getTenant()->id),
                                ignoreRecord: true,
                            )
                            ->validationMessages(['unique' => 'Este correo ya existe en la lista de contactos.']),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30),

                        Forms\Components\Toggle::make('active')
                            ->label('Activo (recibe campañas)')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Textarea::make('notas')
                    ->label('Notas')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MailingContact $r) => $r->email),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->visibleFrom('lg'),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Agregado')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([50, 100, 250])
            ->defaultPaginationPageOption(50)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo contacto')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['empresa_id'] = Filament::getTenant()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),

                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => MailingContact::whereIn('id', $records->pluck('id'))->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => MailingContact::whereIn('id', $records->pluck('id'))->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
