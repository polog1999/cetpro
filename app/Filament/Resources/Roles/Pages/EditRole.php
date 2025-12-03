<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Permiso;
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
        // Cargar permisos actuales del rol y separarlos por grupo
        $permisosActuales = $this->record->permisos()->pluck('permisos.id')->toArray();

        $data['permisos_estudiantil'] = Permiso::where('grupo', 'Gestión Estudiantil')
            ->whereIn('id', $permisosActuales)
            ->pluck('id')
            ->toArray();

        $data['permisos_academica'] = Permiso::where('grupo', 'Gestión Académica')
            ->whereIn('id', $permisosActuales)
            ->pluck('id')
            ->toArray();

        $data['permisos_administrativa'] = Permiso::where('grupo', 'Gestión Administrativa')
            ->whereIn('id', $permisosActuales)
            ->pluck('id')
            ->toArray();

        $data['permisos_financiera'] = Permiso::where('grupo', 'Gestión Financiera')
            ->whereIn('id', $permisosActuales)
            ->pluck('id')
            ->toArray();

        $data['permisos_usuarios'] = Permiso::where('grupo', 'Gestión de Usuarios')
            ->whereIn('id', $permisosActuales)
            ->pluck('id')
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Combinar todos los  permisos de los diferentes grupos
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

        // Guardar en atributo temporal
        $data['_permisos'] = $permisos;

        return $data;
    }

    protected function afterSave(): void
    {
        $permisos = $this->data['_permisos'] ?? [];

        // Sincronizar permisos (esto eliminará los antiguos y agregará los nuevos)
        $this->record->permisos()->sync($permisos);
    }
}
