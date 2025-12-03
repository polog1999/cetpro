<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // No guardar los campos de permisos temporales
        $permisos = array_merge(
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

        // Guardar los permisos en un atributo temporal
        $data['_permisos'] = $permisos;

        return $data;
    }

    protected function afterCreate(): void
    {
        $permisos = $this->data['_permisos'] ?? [];

        if (!empty($permisos)) {
            $this->record->permisos()->sync($permisos);
        }
    }
}
