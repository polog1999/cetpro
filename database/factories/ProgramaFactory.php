<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Programa>
 */
class ProgramaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_programa' => $this->faker->sentence(3),
            'duracion' => $this->faker->randomElement(['3 meses', '6 meses', '12 meses', '2 años']),
            'num_cursos' => $this->faker->numberBetween(5, 15),
            'id_especialidad' => \App\Models\Especialidad::factory(),
            // Usar valores directos en vez de constantes de enum para evitar problemas
            'tipo_programa' => $this->faker->randomElement(['Programa', 'Formación continua']),
        ];
    }
}
