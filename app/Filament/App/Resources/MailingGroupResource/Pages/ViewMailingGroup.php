<?php

namespace App\Filament\App\Resources\MailingGroupResource\Pages;

use App\Filament\App\Resources\MailingGroupResource;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewMailingGroup extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithRecord;

    protected static string $resource = MailingGroupResource::class;
    protected static string $view     = 'filament.app.pages.view-mailing-group';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        static::authorizeResourceAccess();
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            MailingGroupResource::getUrl() => 'Grupos',
            '#' => $this->record->name,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('renombrar')
                ->label('Renombrar grupo')
                ->icon('heroicon-o-pencil')
                ->url(MailingGroupResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MailingContact::withoutGlobalScopes()
                    ->where('empresa_id', Filament::getTenant()->id)
                    ->where('mailing_group_id', $this->record->id)
            )
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
                    ->model(MailingContact::class)
                    ->form($this->contactForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['empresa_id']       = Filament::getTenant()->id;
                        $data['mailing_group_id'] = $this->record->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->form($this->contactForm()),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),

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

    private function contactForm(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->maxLength(150),

                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ]),
            Forms\Components\Grid::make(2)->schema([
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
        ];
    }
}
