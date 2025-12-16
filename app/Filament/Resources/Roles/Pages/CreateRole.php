<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\RoleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(RoleService::class);
        
        // Extraer permisos desde toggles
        $permisosIds = $service->extraerPermisosDeToggles($data);
        
        // Limpiar toggles del data
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permiso_')) {
                unset($data[$key]);
            }
        }
        
        // Crear rol con permisos usando el service
        return $service->crearConPermisos($data, $permisosIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
