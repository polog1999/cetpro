<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Primero eliminar TODOS los permisos antiguos
        Permiso::query()->delete();
        
        $permisos = [
            // Gestión Estudiantil
            ['nombre' => 'Matrículas', 'recurso' => 'matriculas', 'grupo' => 'Gestión Estudiantil', 'descripcion' => 'Acceso completo al módulo de matrículas'],
            ['nombre' => 'Estudiantes', 'recurso' => 'estudiantes', 'grupo' => 'Gestión Estudiantil', 'descripcion' => 'Acceso completo al módulo de estudiantes'],
            ['nombre' => 'Apoderados', 'recurso' => 'apoderados', 'grupo' => 'Gestión Estudiantil', 'descripcion' => 'Acceso completo al módulo de apoderados'],

            // Gestión Académica
            ['nombre' => 'Programas', 'recurso' => 'programas', 'grupo' => 'Gestión Académica', 'descripcion' => 'Acceso completo al módulo de programas'],
            ['nombre' => 'Cursos', 'recurso' => 'cursos', 'grupo' => 'Gestión Académica', 'descripcion' => 'Acceso completo al módulo de cursos'],
            ['nombre' => 'Horarios', 'recurso' => 'horarios', 'grupo' => 'Gestión Académica', 'descripcion' => 'Acceso completo al módulo de horarios'],

            // Gestión Administrativa
            ['nombre' => 'Docentes', 'recurso' => 'docentes', 'grupo' => 'Gestión Administrativa', 'descripcion' => 'Acceso completo al módulo de docentes'],
            ['nombre' => 'Empleados', 'recurso' => 'empleados', 'grupo' => 'Gestión Administrativa', 'descripcion' => 'Acceso completo al módulo de empleados'],

            // Gestión Financiera
            ['nombre' => 'Cronogramas', 'recurso' => 'cronogramas', 'grupo' => 'Gestión Financiera', 'descripcion' => 'Acceso completo al módulo de cronogramas de pago'],
            ['nombre' => 'Pagos', 'recurso' => 'pagos', 'grupo' => 'Gestión Financiera', 'descripcion' => 'Acceso completo al módulo de pagos'],

            // Gestión de Usuarios
            ['nombre' => 'Usuarios', 'recurso' => 'usuarios', 'grupo' => 'Gestión de Usuarios', 'descripcion' => 'Acceso completo al módulo de usuarios'],
            ['nombre' => 'Roles', 'recurso' => 'roles', 'grupo' => 'Gestión de Usuarios', 'descripcion' => 'Acceso completo al módulo de roles y permisos'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }

        $this->command->info('✅ Permisos recreados correctamente (12 permisos por resource)');
    }
}
