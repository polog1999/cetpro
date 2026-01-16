<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permiso;

class RolDirectoraSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * 
     * Crea el rol de Directora con acceso a los módulos:
     * - Matrículas, Apoderados, Documentos, Estudiantes, Notas
     * - Registrar Alumnos Antiguos, Cronogramas, Pagos
     * - Docentes, Servicios TUSNE, Horarios, Programas, Empleados
     * 
     * Sin acceso a: Roles y Usuarios
     */
    public function run(): void
    {
        // Crear el rol Directora
        $rolDirectora = Role::create([
            'nombre' => 'Directora',
            'descripcion' => 'Directora con acceso completo a gestión académica, estudiantil y administrativa, sin acceso a gestión de roles y usuarios',
            'es_admin' => false,
        ]);

        // Obtener o crear los permisos necesarios
        $permisos = [];

        // Gestión Estudiantil
        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'matriculas'],
            [
                'nombre' => 'Matrículas',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Acceso completo al módulo de matrículas',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'apoderados'],
            [
                'nombre' => 'Apoderados',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Acceso completo al módulo de apoderados',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'documentos'],
            [
                'nombre' => 'Documentos',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Acceso completo al módulo de documentos',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'estudiantes'],
            [
                'nombre' => 'Estudiantes',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Acceso completo al módulo de estudiantes',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'notas'],
            [
                'nombre' => 'Notas',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Acceso completo al módulo de notas',
            ]
        );

        // Páginas especiales
        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'servicios_tusne'],
            [
                'nombre' => 'Servicios TUSNE',
                'grupo' => 'Administración',
                'descripcion' => 'Acceso a los servicios de integración con Oracle TUSNE',
            ]
        );

        // Gestión Financiera
        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'cronogramas'],
            [
                'nombre' => 'Cronogramas',
                'grupo' => 'Gestión Financiera',
                'descripcion' => 'Acceso completo al módulo de cronogramas de pago',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'pagos'],
            [
                'nombre' => 'Pagos',
                'grupo' => 'Gestión Financiera',
                'descripcion' => 'Acceso completo al módulo de pagos',
            ]
        );

        // Gestión Académica
        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'docentes'],
            [
                'nombre' => 'Docentes',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Acceso completo al módulo de docentes',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'horarios'],
            [
                'nombre' => 'Horarios',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Acceso completo al módulo de horarios',
            ]
        );

        $permisos[] = Permiso::firstOrCreate(
            ['recurso' => 'programas'],
            [
                'nombre' => 'Programas',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Acceso completo al módulo de programas',
            ]
        );

        // Asignar permisos al rol Directora
        $permisoIds = collect($permisos)->pluck('id')->toArray();
        $rolDirectora->permisos()->attach($permisoIds);

        $this->command->info('✅ Rol Directora creado exitosamente con ' . count($permisoIds) . ' permisos.');
        $this->command->info('   Módulos con acceso: Matrículas, Apoderados, Documentos, Estudiantes, Notas,');
        $this->command->info('   Cronogramas, Pagos, Docentes, Servicios TUSNE, Horarios, Programas');
        $this->command->info('   Sin acceso a: Roles, Usuarios y Empleados');
    }
}

