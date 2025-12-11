<?php

namespace Database\Seeders;

use App\Models\Docente;
use Illuminate\Database\Seeder;

class DocenteSeeder extends Seeder
{
    public function run(): void
    {
        Docente::create([
            'tipo_documento'   => 'DNI',
            'nro_documento'    => '12345678',
            'nombres'          => 'Juan Carlos',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'García',
        ]);

        Docente::create([
            'tipo_documento'   => 'DNI',
            'nro_documento'    => '23456789',
            'nombres'          => 'María José',
            'apellido_paterno' => 'Lopez',
            'apellido_materno' => 'Ramírez',
        ]);

        Docente::create([
            'tipo_documento'   => 'DNI',
            'nro_documento'    => '34567890',
            'nombres'          => 'Luis Alberto',
            'apellido_paterno' => 'Fernández',
            'apellido_materno' => 'Quispe',
        ]);

        Docente::create([
            'tipo_documento'   => 'DNI',
            'nro_documento'    => '45678901',
            'nombres'          => 'Ana Lucía',
            'apellido_paterno' => 'Rojas',
            'apellido_materno' => 'Salazar',
        ]);

        Docente::create([
            'tipo_documento'   => 'DNI',
            'nro_documento'    => '56789012',
            'nombres'          => 'Carlos Eduardo',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Torres',
        ]);
    }
}
