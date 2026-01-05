<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permiso;

class RolProfesorSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Crear el rol Profesor
        $rolProfesor = Role::create([
            'nombre' => 'Profesor',
            'descripcion' => 'Docente con acceso al sistema para gestionar notas y asistencias de sus estudiantes',
            'es_admin' => false,
        ]);

        // Crear o obtener permisos
        $permisoNotas = Permiso::firstOrCreate(
            ['recurso' => 'notas'],
            [
                'nombre' => 'Gestionar Notas',
                'grupo' => 'Académico',
                'descripcion' => 'Registrar y visualizar calificaciones de estudiantes',
            ]
        );

        $permisoEstudiantes = Permiso::firstOrCreate(
            ['recurso' => 'estudiantes'],
            [
                'nombre' => 'Ver Estudiantes',
                'grupo' => 'Gestión de Matrícula',
                'descripcion' => 'Visualizar información de estudiantes',
            ]
        );

        $permisoHorarios = Permiso::firstOrCreate(
            ['recurso' => 'horarios'],
            [
                'nombre' => 'Ver Horarios',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Visualizar horarios asignados',
            ]
        );

        // Asignar permisos al rol Profesor
        $rolProfesor->permisos()->attach([
            $permisoNotas->id,
            $permisoEstudiantes->id,
            $permisoHorarios->id,
        ]);

        $this->command->info('Rol Profesor creado exitosamente con sus permisos.');
    }
}
