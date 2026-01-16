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
            RoleSeeder::class,     
            PermisosSeeder::class,  
            AdminSetupSeeder::class, 
            AlumnoRoleSeeder::class,
            RolProfesorSeeder::class,
            RolDirectoraSeeder::class,
        ]);
    }

    
}
