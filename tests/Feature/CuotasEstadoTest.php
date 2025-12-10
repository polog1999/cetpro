<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pago;
use App\Models\Matricula;
use App\Models\Horario;
use App\Models\Estudiante;
use App\Models\Programa;
use App\Models\Especialidad;
use App\Enums\EstadoPago;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Services\CronogramaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CuotasEstadoTest extends TestCase
{
    use RefreshDatabase;

    protected CronogramaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CronogramaService();
    }

    /**
     * Test que cuota pasa a vencida después de fecha de vencimiento.
     */
    public function test_cuota_pasa_a_vencida_despues_de_fecha_de_vencimiento()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '3 meses',
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
        $cronograma = $matricula->cronograma;
        $pago = $cronograma->pagos()->first();

        // Inicialmente debe estar PENDIENTE
        $this->assertEquals(EstadoPago::PENDIENTE, $pago->estado);

        // Cambiar fecha de vencimiento a una fecha pasada
        $pago->update(['fecha_vencimiento' => now()->subDays(10)]);

        // Actualizar pagos vencidos
        $actualizados = $this->service->actualizarPagosVencidos();

        // Debe actualizar 1 pago
        $this->assertEquals(1, $actualizados);

        // Verificar que ahora está VENCIDO
        $pago->refresh();
        $this->assertEquals(EstadoPago::VENCIDO, $pago->estado);
    }

    /**
     * Test que no se puede volver a pendiente una cuota pagada sin proceso de anulación.
     */
    public function test_no_se_puede_volver_a_pendiente_una_cuota_pagada_sin_proceso_de_anulacion()
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

        // Pagar la cuota
        $pago->registrarPago('Efectivo');

        // Verificar que está PAGADO
        $pago->refresh();
        $this->assertEquals(EstadoPago::PAGADO, $pago->estado);

        // Intentar cambiar directamente a PENDIENTE (no debe permitirse)
        $this->expectException(\Exception::class);
        $pago->update(['estado' => EstadoPago::PENDIENTE]);

        // El estado debe seguir siendo PAGADO
        $pago->refresh();
        $this->assertEquals(EstadoPago::PAGADO, $pago->estado);
    }

    /**
     * Test que se puede revertir un pago pagado mediante proceso formal.
     */
    public function test_se_puede_revertir_pago_pagado_mediante_proceso_formal()
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

        // Pagar la cuota
        $pago->registrarPago('Efectivo');

        // Verificar que está PAGADO
        $pago->refresh();
        $this->assertEquals(EstadoPago::PAGADO, $pago->estado);

        // Revertir el pago mediante proceso formal
        $pagoRevertido = $this->service->revertirPago($pago->id, 'Error en el registro');

        // Verificar que volvió a PENDIENTE o VENCIDO según la fecha
        $this->assertContains($pagoRevertido->estado, [EstadoPago::PENDIENTE, EstadoPago::VENCIDO]);
        $this->assertNull($pagoRevertido->fecha_pago);
        $this->assertNull($pagoRevertido->metodo_pago);
    }

    /**
     * Test que no se puede pagar una cuota ANULADA.
     */
    public function test_no_se_puede_pagar_una_cuota_anulada()
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

        // Anular el pago
        $pago->anular();

        // Intentar pagar (debe lanzar excepción)
        $pago->registrarPago('Efectivo');
    }

    /**
     * Test que se puede pagar una cuota VENCIDA.
     */
    public function test_se_puede_pagar_una_cuota_vencida()
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

        // Cambiar a VENCIDO
        $pago->update([
            'estado' => EstadoPago::VENCIDO,
            'fecha_vencimiento' => now()->subDays(5),
        ]);

        // Pagar la cuota vencida
        $pago->registrarPago('Efectivo');

        // Verificar que ahora está PAGADO
        $pago->refresh();
        $this->assertEquals(EstadoPago::PAGADO, $pago->estado);
    }

    /**
     * Test de cálculo de días de retraso.
     */
    public function test_calcula_dias_de_retraso_correctamente()
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

        // Pago con vencimiento hace 10 días
        $pago->update([
            'fecha_vencimiento' => now()->subDays(10),
            'estado' => EstadoPago::VENCIDO,
        ]);

        // Debe tener 10 días de retraso
        $this->assertEquals(10, $pago->diasRetraso());

        // Si está pagado, no debe tener retraso
        $pago->update(['estado' => EstadoPago::PAGADO]);
        $this->assertEquals(0, $pago->diasRetraso());

        // Si aún no vence, no debe tener retraso
        $pago->update([
            'estado' => EstadoPago::PENDIENTE,
            'fecha_vencimiento' => now()->addDays(5),
        ]);
        $this->assertEquals(0, $pago->diasRetraso());
    }

    /**
     * Test de transiciones de estados válidas.
     */
    public function test_transiciones_de_estados_validas()
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

        // Estado inicial: PENDIENTE
        $this->assertEquals(EstadoPago::PENDIENTE, $pago->estado);
        $this->assertTrue($pago->puedeSerPagado());
        $this->assertFalse($pago->estadoEsFinal());

        // PENDIENTE -> VENCIDO
        $pago->update(['estado' => EstadoPago::VENCIDO]);
        $this->assertEquals(EstadoPago::VENCIDO, $pago->estado);
        $this->assertTrue($pago->puedeSerPagado());

        // VENCIDO -> PAGADO
        $pago->registrarPago('Efectivo');
        $pago->refresh();
        $this->assertEquals(EstadoPago::PAGADO, $pago->estado);
        $this->assertFalse($pago->puedeSerPagado());
        $this->assertTrue($pago->estadoEsFinal());

        // PAGADO -> ANULADO (solo para casos especiales, no es común)
        // No implementaremos esta transición en este test ya que no es estándar
    }

    /**
     * Test que no se puede anular un pago ya anulado.
     */
    public function test_no_se_puede_anular_pago_ya_anulado()
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

        // Anular el pago
        $pago->anular();

        // Intentar anular de nuevo (debe lanzar excepción)
        $pago->anular();
    }
}
