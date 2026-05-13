<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmpresaUser extends EditRecord
{
    protected static string $resource = EmpresaUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quitar_acceso')
                ->label('Quitar acceso')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Quitar acceso a la empresa')
                ->modalDescription(fn () =>
                    "¿Seguro que deseas quitarle el acceso a {$this->record->name}? No se eliminará su cuenta."
                )
                ->modalSubmitActionLabel('Sí, quitar acceso')
                ->action(function (): void {
                    $this->record->empresasAcceso()->detach(Filament::getTenant()?->id);

                    Notification::make()
                        ->title('Acceso eliminado')
                        ->body("{$this->record->name} ya no tiene acceso a esta empresa.")
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->id !== auth()->id()),
        ];
    }

    /** Pre-rellena el selector de rol con el rol del pivot para esta empresa. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $empresaId   = Filament::getTenant()?->id;
        $pivotEntry  = $this->record->empresasAcceso()->where('empresas.id', $empresaId)->first();

        $data['role'] = $pivotEntry?->pivot->rol
            ?? $this->record->roles->first()?->name;

        return $data;
    }

    /** Actualiza el usuario y sincroniza el rol en el pivot. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = $data['role'] ?? null;
        unset($data['role'], $data['empresa_id']);

        $record->update($data);

        if ($role) {
            $record->syncRoles([$role]);

            $record->empresasAcceso()->updateExistingPivot(
                Filament::getTenant()?->id,
                ['rol' => $role]
            );
        }

        return $record;
    }
}
