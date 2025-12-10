<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matricula>
 */
class MatriculaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estudiante_id' => \App\Models\Estudiante::factory(),
            'horario_id' => \App\Models\Horario::factory(),
            'estado' => \App\Enums\EstadoMatricula::ENPROCESO,
            'tipo_matricula' => \App\Enums\TipoMatricula::PROGRAMA,
            'id_curso' => null,
            // codigo_inscripcion se genera automáticamente
        ];
    }
}
