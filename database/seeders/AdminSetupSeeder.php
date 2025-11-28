<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use App\Models\Usuario;
use App\Models\Role;

class AdminSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Empleado base (no duplica si ya existe por documento)
            $empleado = Empleado::firstOrCreate(
                ['num_documento' => env('ADMIN_DOC', '00000000')],
                [
                    'nombre'            => 'Admin',
                    'apellido_paterno'  => 'Sistema',
                    'apellido_materno'  => null,
                    'correo'            => env('ADMIN_EMAIL', 'admin@example.com'),
                    'celular'           => null,
                    'tipo_documento'    => 'DNI',
                ]
            );

            // 2) Obtener el rol de Administrador
            $rolAdmin = Role::where('nombre', 'Administrador')->first();
            
            if (!$rolAdmin) {
                throw new \Exception('Rol "Administrador" no encontrado. Ejecuta primero PermisoSeeder y RoleSeeder.');
            }

            // 3) Usuario admin (no duplica por usuario)
            Usuario::firstOrCreate(
                ['usuario' => env('ADMIN_USER', 'admin')],
                [
                    'empleado_id' => $empleado->id,
                    'password'    => env('ADMIN_PASSWORD', 'admin123'),
                    'role_id'     => $rolAdmin->id, // Usar el nuevo sistema de roles
                ]
            );
        });
    }
}
