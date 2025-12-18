<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de Autenticación API con Sanctum
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(): Usuario
    {
        $role = Role::factory()->create(['nombre' => 'Admin']);
        
        return Usuario::factory()->create([
            'usuario' => 'testuser',
            'password' => bcrypt('password123'),
            'activo' => true,
            'role_id' => $role->id,
        ]);
    }

    public function test_login_exitoso_retorna_token(): void
    {
        $usuario = $this->createUserWithRole();

        $response = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'usuario', 'nombre', 'role'],
                    'token',
                    'token_type'
                ]
            ]);
    }

    public function test_login_fallido_con_credenciales_incorrectas(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'noexiste',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario']);
    }

    public function test_login_fallido_con_usuario_inactivo(): void
    {
        $role = Role::factory()->create(['nombre' => 'Admin']);
        $usuario = Usuario::factory()->create([
            'usuario' => 'inactivo',
            'password' => bcrypt('password123'),
            'activo' => false,
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'inactivo',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.usuario.0', 'El usuario está desactivado. Contacte al administrador.');
    }

    public function test_ruta_protegida_sin_token_retorna_401(): void
    {
        $response = $this->getJson('/api/v1/estudiantes');
        
        $response->assertUnauthorized();
    }

    public function test_ruta_protegida_con_token_valido(): void
    {
        $usuario = $this->createUserWithRole();
        
        // Usar Sanctum::actingAs para simular autenticación
        Sanctum::actingAs($usuario);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.usuario', 'testuser');
    }

    public function test_logout_revoca_token(): void
    {
        $usuario = $this->createUserWithRole();
        $token = $usuario->createToken('test-token')->plainTextToken;

        // Logout con token
        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('data.message', 'Sesión cerrada exitosamente');

        // El token ya no debería funcionar - verificar en BD
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $usuario->id,
            'name' => 'test-token',
        ]);
    }

    public function test_logout_all_revoca_todos_los_tokens(): void
    {
        $usuario = $this->createUserWithRole();
        
        // Crear múltiples tokens
        $token1 = $usuario->createToken('token-1')->plainTextToken;
        $token2 = $usuario->createToken('token-2')->plainTextToken;

        // Verificar que existen ambos tokens
        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Logout-all usando token1
        $response = $this->withToken($token1)->postJson('/api/v1/auth/logout-all');

        $response->assertOk();

        // Ningún token debería existir
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
