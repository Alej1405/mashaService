<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailingContactResource\Pages;
use App\Models\MailingContact;
use App\Services\MailingService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MailingContactResource extends Resource
{
    protected static ?string $model = MailingContact::class;
    protected static ?string $tenantRelationshipName = 'mailingContacts';

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Contactos';
    protected static ?string $navigationGroup  = 'Mailing';
    protected static ?int    $navigationSort   = 3;
    protected static ?string $modelLabel       = 'Contacto';
    protected static ?string $pluralModelLabel = 'Contactos de correo';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre completo')
                            ->placeholder('Juan Pérez')
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
                            ->validationMessages(['unique' => 'Este correo ya existe en tu lista de contactos.']),
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

    public static function table(Table $table): Table
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
            ->headerActions([
                // ── Importar contactos ────────────────────────────────────
                Tables\Actions\Action::make('importar')
                    ->label('Importar contactos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->modalHeading('Importar contactos desde archivo')
                    ->modalDescription('Sube un archivo CSV o Excel. Las columnas requeridas son: nombre, email. Opcionales: telefono, notas.')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\FileUpload::make('archivo')
                            ->label('Archivo de contactos')
                            ->disk('local')
                            ->directory('mailing-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/octet-stream',
                            ])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Acepta: CSV, Excel (.xlsx / .xls). Para Google Sheets: Archivo → Descargar → CSV.'),
                    ])
                    ->action(function (array $data): void {
                        $relativePath = is_array($data['archivo'])
                            ? reset($data['archivo'])
                            : $data['archivo'];

                        $fullPath = Storage::disk('local')->path($relativePath);
                        $contacts = MailingService::parseContactsFile($fullPath);

                        if (empty($contacts)) {
                            Storage::disk('local')->delete($relativePath);
                            Notification::make()
                                ->title('No se encontraron contactos válidos')
                                ->body('Verifica que el archivo tenga la columna "email" con datos válidos.')
                                ->warning()->send();
                            return;
                        }

                        $empresa  = Filament::getTenant();
                        $imported = 0;
                        $skipped  = 0;

                        foreach ($contacts as $contact) {
                            $exists = MailingContact::where('empresa_id', $empresa->id)
                                ->where('email', $contact['email'])
                                ->exists();

                            if (! $exists) {
                                MailingContact::create([
                                    'empresa_id' => $empresa->id,
                                    'nombre'     => $contact['nombre'] ?: '',
                                    'email'      => $contact['email'],
                                    'telefono'   => $contact['telefono'] ?: null,
                                    'notas'      => $contact['notas'] ?: null,
                                    'active'     => true,
                                ]);
                                $imported++;
                            } else {
                                $skipped++;
                            }
                        }

                        Storage::disk('local')->delete($relativePath);

                        Notification::make()
                            ->title("{$imported} contactos importados")
                            ->body($skipped > 0 ? "{$skipped} ya existían y fueron omitidos." : null)
                            ->success()->send();
                    }),

                // ── Descargar plantilla CSV ───────────────────────────────
                Tables\Actions\Action::make('descargarPlantilla')
                    ->label('Descargar plantilla')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(route('mailing.contacts.template'))
                    ->openUrlInNewTab(),
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
                        ->action(fn ($records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMailingContacts::route('/'),
            'create' => Pages\CreateMailingContact::route('/create'),
            'edit'   => Pages\EditMailingContact::route('/{record}/edit'),
        ];
    }
}
