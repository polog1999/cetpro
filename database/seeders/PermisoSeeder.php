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
        // Definir todos los permisos del sistema
        $permisos = [
            // Estudiantes
            ['recurso' => 'estudiantes.view', 'nombre' => 'Ver estudiantes', 'grupo' => 'Estudiantes', 'descripcion' => 'Permite ver la lista de estudiantes'],
            ['recurso' => 'estudiantes.create', 'nombre' => 'Crear estudiantes', 'grupo' => 'Estudiantes', 'descripcion' => 'Permite crear nuevos estudiantes'],
            ['recurso' => 'estudiantes.edit', 'nombre' => 'Editar estudiantes', 'grupo' => 'Estudiantes', 'descripcion' => 'Permite editar estudiantes existentes'],
            ['recurso' => 'estudiantes.delete', 'nombre' => 'Eliminar estudiantes', 'grupo' => 'Estudiantes', 'descripcion' => 'Permite eliminar estudiantes'],

            // Docentes
            ['recurso' => 'docentes.view', 'nombre' => 'Ver docentes', 'grupo' => 'Docentes', 'descripcion' => 'Permite ver la lista de docentes'],
            ['recurso' => 'docentes.create', 'nombre' => 'Crear docentes', 'grupo' => 'Docentes', 'descripcion' => 'Permite crear nuevos docentes'],
            ['recurso' => 'docentes.edit', 'nombre' => 'Editar docentes', 'grupo' => 'Docentes', 'descripcion' => 'Permite editar docentes existentes'],
            ['recurso' => 'docentes.delete', 'nombre' => 'Eliminar docentes', 'grupo' => 'Docentes', 'descripcion' => 'Permite eliminar docentes'],

            // Matrículas
            ['recurso' => 'matriculas.view', 'nombre' => 'Ver matrículas', 'grupo' => 'Matrículas', 'descripcion' => 'Permite ver la lista de matrículas'],
            ['recurso' => 'matriculas.create', 'nombre' => 'Crear matrículas', 'grupo' => 'Matrículas', 'descripcion' => 'Permite crear nuevas matrículas'],
            ['recurso' => 'matriculas.edit', 'nombre' => 'Editar matrículas', 'grupo' => 'Matrículas', 'descripcion' => 'Permite editar matrículas existentes'],
            ['recurso' => 'matriculas.delete', 'nombre' => 'Eliminar matrículas', 'grupo' => 'Matrículas', 'descripcion' => 'Permite eliminar matrículas'],

            // Horarios
            ['recurso' => 'horarios.view', 'nombre' => 'Ver horarios', 'grupo' => 'Horarios', 'descripcion' => 'Permite ver la lista de horarios'],
            ['recurso' => 'horarios.create', 'nombre' => 'Crear horarios', 'grupo' => 'Horarios', 'descripcion' => 'Permite crear nuevos horarios'],
            ['recurso' => 'horarios.edit', 'nombre' => 'Editar horarios', 'grupo' => 'Horarios', 'descripcion' => 'Permite editar horarios existentes'],
            ['recurso' => 'horarios.delete', 'nombre' => 'Eliminar horarios', 'grupo' => 'Horarios', 'descripcion' => 'Permite eliminar horarios'],

            // Programas
            ['recurso' => 'programas.view', 'nombre' => 'Ver programas', 'grupo' => 'Programas', 'descripcion' => 'Permite ver la lista de programas'],
            ['recurso' => 'programas.create', 'nombre' => 'Crear programas', 'grupo' => 'Programas', 'descripcion' => 'Permite crear nuevos programas'],
            ['recurso' => 'programas.edit', 'nombre' => 'Editar programas', 'grupo' => 'Programas', 'descripcion' => 'Permite editar programas existentes'],
            ['recurso' => 'programas.delete', 'nombre' => 'Eliminar programas', 'grupo' => 'Programas', 'descripcion' => 'Permite eliminar programas'],

            // Cursos
            ['recurso' => 'cursos.view', 'nombre' => 'Ver cursos', 'grupo' => 'Cursos', 'descripcion' => 'Permite ver la lista de cursos'],
            ['recurso' => 'cursos.create', 'nombre' => 'Crear cursos', 'grupo' => 'Cursos', 'descripcion' => 'Permite crear nuevos cursos'],
            ['recurso' => 'cursos.edit', 'nombre' => 'Editar cursos', 'grupo' => 'Cursos', 'descripcion' => 'Permite editar cursos existentes'],
            ['recurso' => 'cursos.delete', 'nombre' => 'Eliminar cursos', 'grupo' => 'Cursos', 'descripcion' => 'Permite eliminar cursos'],

            // Pagos y Cronogramas
            ['recurso' => 'pagos.view', 'nombre' => 'Ver pagos', 'grupo' => 'Pagos', 'descripcion' => 'Permite ver la lista de pagos'],
            ['recurso' => 'pagos.create', 'nombre' => 'Registrar pagos', 'grupo' => 'Pagos', 'descripcion' => 'Permite registrar nuevos pagos'],
            ['recurso' => 'pagos.edit', 'nombre' => 'Editar pagos', 'grupo' => 'Pagos', 'descripcion' => 'Permite editar pagos existentes'],
            ['recurso' => 'pagos.delete', 'nombre' => 'Eliminar pagos', 'grupo' => 'Pagos', 'descripcion' => 'Permite eliminar pagos'],

            // Empleados
            ['recurso' => 'empleados.view', 'nombre' => 'Ver empleados', 'grupo' => 'Empleados', 'descripcion' => 'Permite ver la lista de empleados'],
            ['recurso' => 'empleados.create', 'nombre' => 'Crear empleados', 'grupo' => 'Empleados', 'descripcion' => 'Permite crear nuevos empleados'],
            ['recurso' => 'empleados.edit', 'nombre' => 'Editar empleados', 'grupo' => 'Empleados', 'descripcion' => 'Permite editar empleados existentes'],
            ['recurso' => 'empleados.delete', 'nombre' => 'Eliminar empleados', 'grupo' => 'Empleados', 'descripcion' => 'Permite eliminar empleados'],

            // Usuarios y Roles
            ['recurso' => 'usuarios.view', 'nombre' => 'Ver usuarios', 'grupo' => 'Usuarios', 'descripcion' => 'Permite ver la lista de usuarios'],
            ['recurso' => 'usuarios.create', 'nombre' => 'Crear usuarios', 'grupo' => 'Usuarios', 'descripcion' => 'Permite crear nuevos usuarios'],
            ['recurso' => 'usuarios.edit', 'nombre' => 'Editar usuarios', 'grupo' => 'Usuarios', 'descripcion' => 'Permite editar usuarios existentes'],
            ['recurso' => 'usuarios.delete', 'nombre' => 'Eliminar usuarios', 'grupo' => 'Usuarios', 'descripcion' => 'Permite eliminar usuarios'],

            ['recurso' => 'roles.view', 'nombre' => 'Ver roles', 'grupo' => 'Roles', 'descripcion' => 'Permite ver la lista de roles'],
            ['recurso' => 'roles.create', 'nombre' => 'Crear roles', 'grupo' => 'Roles', 'descripcion' => 'Permite crear nuevos roles'],
            ['recurso' => 'roles.edit', 'nombre' => 'Editar roles', 'grupo' => 'Roles', 'descripcion' => 'Permite editar roles existentes'],
            ['recurso' => 'roles.delete', 'nombre' => 'Eliminar roles', 'grupo' => 'Roles', 'descripcion' => 'Permite eliminar roles'],

            // Reportes
            ['recurso' => 'reportes.view', 'nombre' => 'Ver reportes', 'grupo' => 'Reportes', 'descripcion' => 'Permite ver reportes del sistema'],
            ['recurso' => 'reportes.export', 'nombre' => 'Exportar reportes', 'grupo' => 'Reportes', 'descripcion' => 'Permite exportar reportes'],
        ];

        // Crear los permisos (updateOrCreate para evitar duplicados)
        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['recurso' => $permiso['recurso']],
                $permiso
            );
        }
    }
}
