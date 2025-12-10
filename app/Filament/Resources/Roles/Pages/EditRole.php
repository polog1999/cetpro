<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los toggles con los permisos actuales del rol
        $togglesData = RoleForm::fillPermisosToggles($this->record);
        
        return array_merge($data, $togglesData);
    }

    protected array $permisosToSync = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extraer IDs de permisos desde los toggles
        $this->permisosToSync = RoleForm::extractPermisosFromToggles($data);

        // Limpiar todos los campos de permisos del data
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permiso_')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Sincronizar permisos (elimina antiguos y agrega nuevos)
        if (!$this->record->es_admin) {
            $this->record->permisos()->sync($this->permisosToSync);
        } else {
            // Si es admin, quitar todos los permisos (no los necesita)
            $this->record->permisos()->sync([]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
