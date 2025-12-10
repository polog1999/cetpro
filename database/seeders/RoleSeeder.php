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

        // (Otros roles eliminados a petición del usuario)


    }
}
