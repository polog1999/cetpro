<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Horario>
 */
class HorarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_programa' => \App\Models\Programa::factory(),
            'id_docente' => \App\Models\Docente::factory(),
            'turno' => \App\Enums\Turno::MAÑANA,
            'modalidad' => \App\Enums\Modalidad::PRESENCIAL,
            'dias' => ['LUN', 'MIE', 'VIE'],
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'aula' => 'A-101',
            'vacantes' => 20,
            'activo' => true,
        ];
    }
}
