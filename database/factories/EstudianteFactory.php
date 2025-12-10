<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estudiante>
 */
class EstudianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_documento' => \App\Enums\TipoDocumento::DNI,
            'nro_documento' => $this->faker->unique()->numerify('########'),
            'nombres' => $this->faker->firstName(),
            'apellido_paterno' => $this->faker->lastName(),
            'apellido_materno' => $this->faker->lastName(),
            'genero' => \App\Enums\TipoGenero::MASCULINO,
            'estado_civil' => \App\Enums\EstadoCivil::SOLTERO,
            'fecha_nacimiento' => $this->faker->date(),
            'telefono' => $this->faker->phoneNumber(),
            'direccion' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'grado_instruccion' => \App\Enums\GradoInstruccion::SECUNDARIA_COMPLETA,
            'provincia' => \App\Enums\Provincia::LIMA,
            'distrito' => \App\Enums\DistritoLima::LIMA,
        ];
    }
}
