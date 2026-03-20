<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use Filament\Actions;
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
            Actions\DeleteAction::make()
                ->label('Eliminar usuario')
                ->visible(fn (): bool => $this->record->id !== auth()->id()),
        ];
    }

    /**
     * Pre-rellena el selector de rol con el rol actual del usuario.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->roles->first()?->name;

        return $data;
    }

    /**
     * Extrae el rol, actualiza el usuario y sincroniza el rol.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        // No sobreescribir empresa_id ni password vacío
        unset($data['empresa_id']);

        $record->update($data);

        if ($role) {
            $record->syncRoles([$role]);
        }

        return $record;
    }
}
