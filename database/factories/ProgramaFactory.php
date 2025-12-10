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
            'duracion' => '2 años',
            'num_cursos' => 10,
            'id_especialidad' => \App\Models\Especialidad::factory(),
            'tipo_programa' => \App\Enums\TipoPrograma::PROGRAMA_ESTUDIO,
        ];
    }
}
