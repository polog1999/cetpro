<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estudiante;
use App\Enums\TipoGenero;
use App\Enums\EstadoCivil;
use App\Enums\TipoDocumento;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;

class EstudiantesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estudiantes = [
            [
                'nombres' => 'Carlos Alberto',
                'apellido_paterno' => 'García',
                'apellido_materno' => 'López',
                'nro_documento' => '72345678',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'María Elena',
                'apellido_paterno' => 'Rodríguez',
                'apellido_materno' => 'Fernández',
                'nro_documento' => '73456789',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'José Luis',
                'apellido_paterno' => 'Martínez',
                'apellido_materno' => 'Sánchez',
                'nro_documento' => '74567890',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Ana Patricia',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Ramírez',
                'nro_documento' => '75678901',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Roberto Carlos',
                'apellido_paterno' => 'Torres',
                'apellido_materno' => 'Vargas',
                'nro_documento' => '76789012',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Carmen Rosa',
                'apellido_paterno' => 'Flores',
                'apellido_materno' => 'Gutiérrez',
                'nro_documento' => '77890123',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Miguel Ángel',
                'apellido_paterno' => 'Díaz',
                'apellido_materno' => 'Castro',
                'nro_documento' => '78901234',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Sandra Liliana',
                'apellido_paterno' => 'Rojas',
                'apellido_materno' => 'Mendoza',
                'nro_documento' => '79012345',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Fernando José',
                'apellido_paterno' => 'Vega',
                'apellido_materno' => 'Morales',
                'nro_documento' => '70123456',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Gabriela Andrea',
                'apellido_paterno' => 'Ramos',
                'apellido_materno' => 'Silva',
                'nro_documento' => '71234567',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Ricardo Martín',
                'apellido_paterno' => 'Herrera',
                'apellido_materno' => 'Quispe',
                'nro_documento' => '72234567',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Lucía Mercedes',
                'apellido_paterno' => 'Chávez',
                'apellido_materno' => 'Paredes',
                'nro_documento' => '73234567',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Andrés Felipe',
                'apellido_paterno' => 'Salazar',
                'apellido_materno' => 'Huamán',
                'nro_documento' => '74234567',
                'genero' => TipoGenero::MASCULINO,
            ],
            [
                'nombres' => 'Roxana Beatriz',
                'apellido_paterno' => 'Ortiz',
                'apellido_materno' => 'Campos',
                'nro_documento' => '75234567',
                'genero' => TipoGenero::FEMENINO,
            ],
            [
                'nombres' => 'Jorge Enrique',
                'apellido_paterno' => 'Navarro',
                'apellido_materno' => 'Ccari',
                'nro_documento' => '76234567',
                'genero' => TipoGenero::MASCULINO,
            ],
        ];

        $distritos = [
            DistritoLima::SAN_JUAN_DE_LURIGANCHO,
            DistritoLima::SAN_MARTIN_DE_PORRES,
            DistritoLima::ATE,
            DistritoLima::COMAS,
            DistritoLima::VILLA_EL_SALVADOR,
            DistritoLima::VILLA_MARIA_DEL_TRIUNFO,
            DistritoLima::SAN_JUAN_DE_MIRAFLORES,
            DistritoLima::LOS_OLIVOS,
            DistritoLima::PUENTE_PIEDRA,
            DistritoLima::SANTIAGO_DE_SURCO,
        ];

        foreach ($estudiantes as $index => $data) {
            Estudiante::create([
                'tipo_documento' => TipoDocumento::DNI,
                'nro_documento' => $data['nro_documento'],
                'nombres' => $data['nombres'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
                'genero' => $data['genero'],
                'estado_civil' => EstadoCivil::SOLTERO,
                'fecha_nacimiento' => now()->subYears(rand(18, 45))->format('Y-m-d'),
                'telefono' => '9' . rand(10000000, 99999999),
                'direccion' => 'Av. Principal ' . rand(100, 999) . ', ' . $distritos[$index % count($distritos)]->value,
                'email' => strtolower(
                    str_replace(' ', '', $data['nombres']) . '.' . 
                    $data['apellido_paterno'] . '@gmail.com'
                ),
                'grado_instruccion' => GradoInstruccion::SECUNDARIA_COMPLETA,
                'provincia' => Provincia::LIMA,
                'distrito' => $distritos[$index % count($distritos)],
                'apoderado_id' => null,
            ]);
        }
    }
}
