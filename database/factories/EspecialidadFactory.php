<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Especialidad>
 */
class EspecialidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_especialidad' => $this->faker->word(),
            'costo_mensual' => $this->faker->randomFloat(2, 100, 500),
            'num_resolucion' => $this->faker->bothify('RES-####'),
            'fecha_registro' => $this->faker->date(),
            'fecha_inicio_vigencia' => $this->faker->date(),
            'fecha_fin_vigencia' => $this->faker->date(),
        ];
    }
}
