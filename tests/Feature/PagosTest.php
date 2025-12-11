<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pago;
use App\Models\Matricula;
use App\Models\Horario;
use App\Models\Estudiante;
use App\Models\Programa;
use App\Models\Especialidad;
use App\Models\Usuario;
use App\Models\Role;
use App\Enums\EstadoPago;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Services\CronogramaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class PagosTest extends TestCase
{
    use RefreshDatabase;

    protected CronogramaService $service;
    protected Usuario $admin;
    protected Usuario $usuarioRegular;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CronogramaService();

        // Crear roles
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $rolUsuario = Role::create(['nombre' => 'Usuario', 'es_admin' => false]);

        // Crear usuarios
        $this->admin = Usuario::factory()->create([
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        $this->usuarioRegular = Usuario::factory()->create([
            'role_id' => $rolUsuario->id,
            'activo' => true,
        ]);
    }

    /**
     * Test que usuario autorizado puede registrar pago de cuota pendiente.
     */
    public function test_usuario_autorizado_puede_registrar_pago_de_cuota_pendiente()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_ matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();
        $pago = $matricula->cronograma->pagos()->first();

        // Verificar estado inicial
        $this->assertEquals(EstadoPago::PENDIENTE, $pago->estado);

        // Registrar pago
        $pagoActualizado = $this->service->registrarPago(
            $pago->id,
            'Efectivo',
            null,
            null,
            null,
            $this->admin->id
        );

        // Verificaciones
        $this->assertEquals(EstadoPago::PAGADO, $pagoActualizado->estado);
        $this->assertEquals('Efectivo', $pagoActualizado->metodo_pago);
        $this->assertEquals($this->admin->id, $pagoActualizado->usuario_id);
        $this->assertNotNull($pagoActualizado->fecha_pago);
    }

    /**
     * Test que no se puede registrar pago sobre cuota ya pagada.
     */
    public function test_no_se_puede_registrar_pago_sobre_cuota_ya_pagada()
    {
        $this->expectException(ValidationException::class);

        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();
        $pago = $matricula->cronograma->pagos()->first();

        // Pagar la cuota por primera vez
        $pago->registrarPago('Efectivo', null, $this->admin->id);

        // Intentar pagar de nuevo (debe lanzar excepción)
        $pago->fresh()->registrarPago('Tarjeta', null, $this->admin->id);
    }

    /**
     * Test que se guarda el usuario que registró el pago.
     */
    public function test_se_guarda_usuario_que_registro_el_pago()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();
        $pago = $matricula->cronograma->pagos()->first();

        // Registrar pago con usuario específico
        $pago->registrarPago('Efectivo', null, $this->usuarioRegular->id);

        $pago->refresh();

        // Verificar que se guardó el usuario
        $this->assertEquals($this->usuarioRegular->id, $pago->usuario_id);
        $this->assertNotNull($pago->usuario);
        $this->assertEquals($this->usuarioRegular->nombre, $pago->usuario->nombre);
    }

    /**
     * Test de validación de monto en registro de pago.
     */
    public function test_valida_monto_en_registro_de_pago()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();
        $pago = $matricula->cronograma->pagos()->first();

        // El monto del pago debe ser 100 (configurado en la especialidad)
        $this->assertEquals(100.00, floatval($pago->monto));

        // Registrar pago
        $pago->registrarPago('Efectivo', null, $this->admin->id);

        // El monto no debe cambiar después del pago
        $pago->refresh();
        $this->assertEquals(100.00, floatval($pago->monto));
    }

    /**
     * Test de registro de pago con método de pago y fecha.
     */
    public function test_registro_de_pago_guarda_metodo_y_fecha()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();
        $pago = $matricula->cronograma->pagos()->first();

        // Registrar pago
        $pagoActualizado = $this->service->registrarPago(
            $pago->id,
            'Transferencia Bancaria',
            null,
            'LIQ-12345',
            now()->format('Y-m-d'),
            $this->admin->id
        );

        // Verificaciones
        $this->assertEquals('Transferencia Bancaria', $pagoActualizado->metodo_pago);
        $this->assertNotNull($pagoActualizado->fecha_pago);
        $this->assertEquals('LIQ-12345', $pagoActualizado->num_liquidacion);
        $this->assertNotNull($pagoActualizado->fecha_liquidacion);
    }

    /**
     * Test que el estado de la matrícula se actualiza después del pago.
     */
    public function test_actualiza_estado_matricula_despues_de_pago()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();

        // Pagar todas las cuotas
        foreach ($matricula->cronograma->pagos as $pago) {
            $pago->registrarPago('Efectivo', null, $this->admin->id);
        }

        // El estado de la matrícula debe actualizarse
        $matricula->refresh();
        
        // Como todas las cuotas están pagadas, la matrícula debe estar EN PROCESO
        $this->assertEquals(EstadoMatricula::ENPROCESO, $matricula->estado);
    }
}
