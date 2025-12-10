<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario' => $this->faker->unique()->userName(),
            'password' => bcrypt('password'), // password por defecto
            'activo' => true,
            'empleado_id' => \App\Models\Empleado::factory(),
            // role_id se puede asignar al crear el factory o dejar null si es nullable
        ];
    }
}
