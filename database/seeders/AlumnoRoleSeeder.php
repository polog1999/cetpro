<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class AlumnoRoleSeeder extends Seeder
{
    /**
     * Crea el rol "Alumno" para estudiantes que acceden al portal.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['nombre' => 'Alumno'],
            [
                'descripcion' => 'Rol para estudiantes. Solo acceso al portal de estudiantes.',
                'es_admin' => false,
            ]
        );
    }
}
