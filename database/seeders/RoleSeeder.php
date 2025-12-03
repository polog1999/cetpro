<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear rol de Administrador con acceso total
        $admin = Role::updateOrCreate(
            ['nombre' => 'Administrador'],
            [
                'descripcion' => 'Acceso total al sistema',
                'es_admin' => true,
            ]
        );

        // Crear rol de Secretaría con permisos específicos
        $secretaria = Role::updateOrCreate(
            ['nombre' => 'Secretaría'],
            [
                'descripcion' => 'Acceso a gestión estudiantil y financiera',
                'es_admin' => false,
            ]
        );

        // Asignar permisos a Secretaría
        // Gestión Estudiantil
        $permisosSecretaria = Permiso::whereIn('recurso', [
            'MatriculaResource',
            'EstudianteResource',
            'ApoderadoResource',
            'CronogramaResource',
            'PagoResource',
        ])->pluck('id');

        $secretaria->permisos()->sync($permisosSecretaria);

        // Crear rol de Contador (ejemplo de rol personalizado)
        $contador = Role::updateOrCreate(
            ['nombre' => 'Contador'],
            [
                'descripcion' => 'Acceso solo a gestión financiera',
                'es_admin' => false,
            ]
        );

        // Asignar permisos a Contador
        $permisosContador = Permiso::whereIn('recurso', [
            'CronogramaResource',
            'PagoResource',
        ])->pluck('id');

        $contador->permisos()->sync($permisosContador);
    }
}
