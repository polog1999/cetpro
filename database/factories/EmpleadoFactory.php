<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empleado>
 */
class EmpleadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellido_paterno' => $this->faker->lastName(),
            'apellido_materno' => $this->faker->lastName(),
            'correo' => $this->faker->unique()->safeEmail(),
            'celular' => $this->faker->phoneNumber(),
            'tipo_documento' => \App\Enums\TipoDocumento::DNI, // Asumiendo que existe este caso
            'num_documento' => $this->faker->unique()->numerify('########'),
        ];
    }
}
