<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $permisosToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // No guardar los campos de permisos temporales
        $this->permisosToSync = array_merge(
            $data['permisos_estudiantil'] ?? [],
            $data['permisos_academica'] ?? [],
            $data['permisos_administrativa'] ?? [],
            $data['permisos_financiera'] ?? [],
            $data['permisos_usuarios'] ?? []
        );

        unset($data['permisos_estudiantil']);
        unset($data['permisos_academica']);
        unset($data['permisos_administrativa']);
        unset($data['permisos_financiera']);
        unset($data['permisos_usuarios']);
        unset($data['_permisos']); // Ensure this is clean

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->permisosToSync)) {
            $this->record->permisos()->sync($this->permisosToSync);
        }
    }
}
