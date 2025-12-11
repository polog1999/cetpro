<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Enums\TipoDocumento;
use Illuminate\Database\Seeder;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        Empleado::create([
            'nombre'           => 'Luis',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Torres',
            'correo'           => 'luis.ramirez@cetpro.test',
            'celular'          => '987654321',
            'tipo_documento'   => TipoDocumento::DNI,   // ajusta según tu enum
            'num_documento'    => '12345678',
        ]);

        Empleado::create([
            'nombre'           => 'Carla',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Salazar',
            'correo'           => 'carla.gomez@cetpro.test',
            'celular'          => '998877665',
            'tipo_documento'   => TipoDocumento::DNI,
            'num_documento'    => '23456789',
        ]);

        Empleado::create([
            'nombre'           => 'Jorge',
            'apellido_paterno' => 'Fernández',
            'apellido_materno' => 'Quispe',
            'correo'           => 'jorge.fernandez@cetpro.test',
            'celular'          => '912345678',
            'tipo_documento'   => TipoDocumento::DNI,
            'num_documento'    => '34567890',
        ]);
    }
}
