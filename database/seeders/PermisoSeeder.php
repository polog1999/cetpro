<?php

namespace Database\Seeders;

use App\Models\Permiso;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            // Gestión Estudiantil
            [
                'recurso' => 'MatriculaResource',
                'nombre' => 'Matrículas',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Gestión de matriculas de estudiantes',
            ],
            [
                'recurso' => 'EstudianteResource',
                'nombre' => 'Estudiantes',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Gestión de estudiantes',
            ],
            [
                'recurso' => 'ApoderadoResource',
                'nombre' => 'Apoderados',
                'grupo' => 'Gestión Estudiantil',
                'descripcion' => 'Gestión de apoderados',
            ],

            // Gestión Académica
            [
                'recurso' => 'ProgramaResource',
                'nombre' => 'Programas',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Gestión de programas académicos',
            ],
            [
                'recurso' => 'HorarioResource',
                'nombre' => 'Horarios',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Gestión de horarios',
            ],
            [
                'recurso' => 'EspecialidadResource',
                'nombre' => 'Especialidades',
                'grupo' => 'Gestión Académica',
                'descripcion' => 'Gestión de especialidades',
            ],

            // Gestión Administrativa
            [
                'recurso' => 'DocenteResource',
                'nombre' => 'Docentes',
                'grupo' => 'Gestión Administrativa',
                'descripcion' => 'Gestión de docentes',
            ],
            [
                'recurso' => 'EmpleadoResource',
                'nombre' => 'Empleados',
                'grupo' => 'Gestión Administrativa',
                'descripcion' => 'Gestión de empleados',
            ],

            // Gestión Financiera
            [
                'recurso' => 'CronogramaResource',
                'nombre' => 'Cronogramas',
                'grupo' => 'Gestión Financiera',
                'descripcion' => 'Gestión de cronogramas de pago',
            ],
            [
                'recurso' => 'PagoResource',
                'nombre' => 'Pagos',
                'grupo' => 'Gestión Financiera',
                'descripcion' => 'Gestión de pagos',
            ],

            // Gestión de Usuarios (solo admin)
            [
                'recurso' => 'UsuarioResource',
                'nombre' => 'Usuarios',
                'grupo' => 'Gestión de Usuarios',
                'descripcion' => 'Gestión de usuarios del sistema',
            ],
            [
                'recurso' => 'RoleResource',
                'nombre' => 'Roles',
                'grupo' => 'Gestión de Usuarios',
                'descripcion' => 'Gestión de roles y permisos',
            ],
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['recurso' => $permiso['recurso']],
                $permiso
            );
        }
    }
}
