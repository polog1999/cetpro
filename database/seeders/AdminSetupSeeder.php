<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use App\Models\Usuario;
use App\Enums\Rol;

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
                    'tipo_documento'    => 'DNI', // ajusta a tu enum si aplica
                ]
            );

            // 2) Usuario admin (no duplica por usuario)
            Usuario::firstOrCreate(
                ['usuario' => env('ADMIN_USER', 'admin')],
                [
                    'empleado_id' => $empleado->id,
                    // Si tu modelo Usuario tiene cast 'password' => 'hashed', se hashea solo:
                    'password'    => env('ADMIN_PASSWORD', 'admin123'),
                    'rol'         => Rol::ADMIN->value, // o 'admin' si no usas enum
                    
                ]
            );
        });
    }
}
