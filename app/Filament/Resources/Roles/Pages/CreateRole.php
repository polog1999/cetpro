<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $permisosToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extraer IDs de permisos desde los toggles
        $this->permisosToSync = RoleForm::extractPermisosFromToggles($data);

        // Limpiar todos los campos de permisos del data
        // (para que no intente guardarlos en la tabla roles)
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permiso_')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sincronizar permisos después de crear el rol
        if (!empty($this->permisosToSync) && !$this->record->es_admin) {
            $this->record->permisos()->sync($this->permisosToSync);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
