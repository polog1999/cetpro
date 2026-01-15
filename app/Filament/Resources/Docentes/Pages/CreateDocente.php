<?php

namespace App\Filament\Resources\Docentes\Pages;

use App\Filament\Resources\Docentes\DocenteResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Docente;
use App\Models\Usuario;
use App\Models\Role;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateDocente extends CreateRecord
{
    protected static string $resource = DocenteResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe un docente con el mismo documento
        $exists = Docente::where('tipo_documento', $this->data['tipo_documento'])
            ->where('nro_documento', $this->data['nro_documento'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Docente')
                ->body('Ya existe un docente con este número de documento.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Verificar si ya existe un usuario con este número de documento
        $usuarioExiste = Usuario::where('usuario', $this->data['nro_documento'])->exists();

        if ($usuarioExiste) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Docente')
                ->body('Ya existe un usuario con este número de documento.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $docente = $this->record;

        try {
            DB::transaction(function () use ($docente) {
                // Buscar o crear el rol de Profesor
                $rolProfesor = Role::firstOrCreate(
                    ['nombre' => 'Profesor'],
                    [
                        'descripcion' => 'Docente con acceso al sistema para gestión de notas',
                        'es_admin' => false,
                    ]
                );

                // Crear el usuario para el docente
                $usuario = Usuario::create([
                    'usuario' => $docente->nro_documento,
                    'password' => Hash::make($docente->nro_documento),
                    'docente_id' => $docente->id,
                    'role_id' => $rolProfesor->id,
                    'activo' => true,
                ]);

                Log::info('Usuario de docente creado automáticamente', [
                    'docente_id' => $docente->id,
                    'usuario_id' => $usuario->id,
                    'username' => $docente->nro_documento,
                ]);

                Notification::make()
                    ->success()
                    ->title('Docente y usuario creados')
                    ->body("Usuario: {$docente->nro_documento} / Contraseña: {$docente->nro_documento}")
                    ->persistent()
                    ->send();
            });

        } catch (\Exception $e) {
            Log::error('Error al crear usuario de docente', [
                'docente_id' => $docente->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->warning()
                ->title('Docente creado sin usuario')
                ->body('Ocurrió un error al crear el usuario: ' . $e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
