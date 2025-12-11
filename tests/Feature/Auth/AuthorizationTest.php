<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_sin_sesion_no_puede_acceder_a_recursos_protegidos()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_usuario_con_rol_incorrecto_no_puede_acceder_a_ciertos_modulos()
    {
        // Crear rol limitado
        $rolSecretaria = Role::create(['nombre' => 'Secretaría', 'es_admin' => false]);
        
        // Crear permiso solo para matrículas
        $permisoMatricula = Permiso::create(['recurso' => 'MatriculaResource', 'nombre' => 'Ver Matrículas']);
        $rolSecretaria->permisos()->attach($permisoMatricula);

        $usuario = Usuario::factory()->create(['role_id' => $rolSecretaria->id, 'activo' => true]);

        $this->actingAs($usuario);

        // Intentar acceder a Usuarios (no tiene permiso)
        // Nota: Filament devuelve 403 Forbidden si no pasa el Policy
        // Asumiendo que la ruta es /admin/usuarios
        $response = $this->get('/admin/usuarios');
        $response->assertStatus(403);
    }

    public function test_admin_puede_acceder_a_todos_los_modulos_clave()
    {
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $admin = Usuario::factory()->create(['role_id' => $rolAdmin->id, 'activo' => true]);

        $this->actingAs($admin);

        // Acceso a Usuarios
        $this->get('/admin/usuarios')->assertStatus(200);
        
        // Acceso a Roles (asumiendo ruta)
        $this->get('/admin/roles')->assertStatus(200);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion()
    {
        $rol = Role::create(['nombre' => 'Docente', 'es_admin' => false]);
        $usuario = Usuario::factory()->create([
            'role_id' => $rol->id,
            'usuario' => 'docente_inactivo',
            'password' => bcrypt('password'),
            'activo' => false
        ]);

        // Intentar login mediante el formulario de Filament
        // Esto ya lo probamos en LoginTest, pero aquí reforzamos la integración
        $response = $this->post('/admin/login', [
            'data' => [
                'usuario' => 'docente_inactivo',
                'password' => 'password',
            ]
        ]);

        $this->assertGuest();
        // Filament suele devolver errores de validación en el componente Livewire, 
        // pero en una petición POST estándar a veces redirige o muestra errores.
        // Lo importante es assertGuest().
    }
}
