<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Ejecutar seeders en orden correcto
        $this->call([
            PermisosSeeder::class,  // Primero los permisos
            RoleSeeder::class,      // Luego los roles
            RolProfesorSeeder::class,
            AdminSetupSeeder::class, // Finalmente el usuario admin
        ]);
    }

    
}
