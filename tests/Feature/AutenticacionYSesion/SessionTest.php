<?php

use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('no se puede acceder a rutas protegidas sin autenticacion', function () {
    $response = $this->get('/admin');
    $response->assertRedirect('/admin/login');
});

test('logout cierra sesion y redirige a login', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test_session@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '99887766'
    ]);
    $usuario = Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'testuser',
        'password' => 'password',
        'activo' => true,
    ]);

    $this->actingAs($usuario);

    // Logout en Filament es un POST a /admin/logout
    $response = $this->post('/admin/logout');

    $response->assertRedirect('/admin/login');
    $this->assertGuest();
});

test('no se puede acceder a rutas protegidas despues de logout', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test_session2@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '44332211'
    ]);
    $usuario = Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'testuser',
        'password' => 'password',
        'activo' => true,
    ]);

    $this->actingAs($usuario);
    $this->post('/admin/logout');

    $response = $this->get('/admin');
    $response->assertRedirect('/admin/login');
});

test('middleware prevent back history agrega headers', function () {
    $response = $this->get('/admin/login');
    
    $response->assertHeader('Cache-Control');
    $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    $response->assertHeader('Pragma', 'no-cache');
});
