<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Programa;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Curso>
 */
class CursoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_curso' => fake()->randomElement([
                'Corte y Confección',
                'Gastronomía Básica',
                'Repostería',
                'Cosmetología',
                'Computación e Informática',
            ]) . ' - ' . fake()->unique()->numberBetween(1, 100),
            'duracion' => fake()->randomElement([3, 6, 9, 12]), // meses
            'fecha_inicio' => fake()->dateTimeBetween('now', '+1 month'),
            'fecha_termino' => fake()->dateTimeBetween('+4 months', '+1 year'),
            'id_programa' => Programa::factory(),
        ];
    }
}
