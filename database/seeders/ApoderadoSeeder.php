<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Apoderado;

class ApoderadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apoderados = [
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '40123456',
                'nombres' => 'Carlos Eduardo',
                'apellido_paterno' => 'Ramírez',
                'apellido_materno' => 'Santos',
                'telefono' => '987654321',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '41234567',
                'nombres' => 'Rosa María',
                'apellido_paterno' => 'Gonzales',
                'apellido_materno' => 'Pérez',
                'telefono' => '976543210',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '42345678',
                'nombres' => 'Luis Alberto',
                'apellido_paterno' => 'Mendoza',
                'apellido_materno' => 'Castro',
                'telefono' => '965432109',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '43456789',
                'nombres' => 'Patricia Elena',
                'apellido_paterno' => 'Vargas',
                'apellido_materno' => 'Flores',
                'telefono' => '954321098',
            ],
            [
                'tipo_documento' => 'DNI',
                'nro_documento' => '44567890',
                'nombres' => 'Miguel Ángel',
                'apellido_paterno' => 'Sánchez',
                'apellido_materno' => 'Rivas',
                'telefono' => '943210987',
            ],
        ];

        foreach ($apoderados as $apoderado) {
            Apoderado::create($apoderado);
        }
    }
}
