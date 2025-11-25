<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Docente;

class DocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $docentes = [
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '12345678',
                'nombres' => 'María Elena',
                'apellido_paterno' => 'García',
                'apellido_materno' => 'López',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '23456789',
                'nombres' => 'Juan Carlos',
                'apellido_paterno' => 'Rodríguez',
                'apellido_materno' => 'Silva',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '34567890',
                'nombres' => 'Ana Patricia',
                'apellido_paterno' => 'Fernández',
                'apellido_materno' => 'Díaz',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '45678901',
                'nombres' => 'Roberto Miguel',
                'apellido_paterno' => 'Torres',
                'apellido_materno' => 'Ramírez',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '56789012',
                'nombres' => 'Carmen Rosa',
                'apellido_paterno' => 'Vega',
                'apellido_materno' => 'Morales',
            ],
        ];

        foreach ($docentes as $docente) {
            Docente::create($docente);
        }
    }
}
