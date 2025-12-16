<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\RoleService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
        $service = app(RoleService::class);
        
        // Cargar toggles de permisos usando el service
        $togglesData = $service->prepararTogglesPermisos($this->record);
        
        return array_merge($data, $togglesData);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
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
        
        // Actualizar rol con permisos usando el service
        return $service->actualizarConPermisos($record->id, $data, $permisosIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
