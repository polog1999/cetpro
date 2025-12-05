<?php

use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Role;
use Livewire\Livewire;
use App\Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('usuario puede iniciar sesion con credenciales correctas', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '12345678'
    ]);
    $usuario = Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'testuser',
        'password' => 'password',
        'activo' => true,
    ]);

    Livewire::test(Login::class)
        ->set('data.usuario', 'testuser')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($usuario);
});

test('no permite login con usuario o contrasena invalidos y muestra mensaje generico', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test2@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '87654321'
    ]);
    Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'testuser',
        'password' => 'password',
        'activo' => true,
    ]);

    Livewire::test(Login::class)
        ->set('data.usuario', 'testuser')
        ->set('data.password', 'wrongpassword')
        ->call('authenticate')
        ->assertHasErrors(['data.usuario']);
        
    $this->assertGuest();
});

test('no permite login de usuario inactivo', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test3@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '11223344'
    ]);
    Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'inactiveuser',
        'password' => 'password',
        'activo' => false,
    ]);

    Livewire::test(Login::class)
        ->set('data.usuario', 'inactiveuser')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['data.usuario']);

    $this->assertGuest();
});

test('trim inputs login', function () {
    $role = Role::create(['nombre' => 'Admin', 'es_admin' => true]);
    $empleado = Empleado::create([
        'nombre' => 'Test',
        'apellido_paterno' => 'User',
        'correo' => 'test4@example.com',
        'tipo_documento' => 'DNI',
        'num_documento' => '55667788'
    ]);
    $usuario = Usuario::create([
        'empleado_id' => $empleado->id,
        'role_id' => $role->id,
        'usuario' => 'trimuser',
        'password' => 'password',
        'activo' => true,
    ]);

    Livewire::test(Login::class)
        ->set('data.usuario', ' trimuser ')
        ->set('data.password', ' password ')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($usuario);
});
