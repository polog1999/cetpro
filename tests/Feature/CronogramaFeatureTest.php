<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cronograma;
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
use Carbon\Carbon;

class CronogramaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected CronogramaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CronogramaService();
    }

    /**
     * Test que se crea cronograma al matricular estudiante.
     */
    public function test_se_crea_cronograma_al_matricular_estudiante()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 250]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '6 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 20,
        ]);

        // Crear matrícula
        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        // Verificar que se creó automáticamente un cronograma
        $matricula->refresh();
        $this->assertNotNull($matricula->cronograma);
        
        // Verificar datos del cronograma
        $this->assertDatabaseHas('cronogramas', [
            'matricula_id' => $matricula->id,
            'num_cuotas' => 6,
            'monto_total' => 1500, // 6 * 250
        ]);

        // Verificar que se generaron las cuotas
        $this->assertDatabaseCount('pagos', 6);
    }

    /**
     * Test de actualización de pagos vencidos.
     */
    public function test_actualiza_pagos_vencidos()
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

        // Modificar Manual fechas de vencimiento para que estén en el pasado
        $pagos = $cronograma->pagos()->get();
        $pagos[0]->update(['fecha_vencimiento' => now()->subDays(30)]);
        $pagos[1]->update(['fecha_vencimiento' => now()->subDays(15)]);
        $pagos[2]->update(['fecha_vencimiento' => now()->addDays(15)]); // Futura

        // Actualizar pagos vencidos
        $actualizados = $this->service->actualizarPagosVencidos();

        // Deben actualizarse 2 pagos
        $this->assertEquals(2, $actualizados);

        // Verificar estados
        $pagos[0]->refresh();
        $pagos[1]->refresh();
        $pagos[2]->refresh();

        $this->assertEquals(EstadoPago::VENCIDO, $pagos[0]->estado);
        $this->assertEquals(EstadoPago::VENCIDO, $pagos[1]->estado);
        $this->assertEquals(EstadoPago::PENDIENTE, $pagos[2]->estado);
    }

    /**
     * Test de registro de pago.
     */
    public function test_registra_pago_correctamente()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 150]);
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
        $cronograma = $matricula->cronograma;
        $pago = $cronograma->pagos()->first();

        // Registrar pago
        $pagoActualizado = $this->service->registrarPago(
            $pago->id,
            'Efectivo',
            null,
            'LIQ-001',
            now()->format('Y-m-d')
        );

        // Verificar que el pago se marcó como PAGADO
        $this->assertEquals(EstadoPago::PAGADO, $pagoActualizado->estado);
        $this->assertEquals('Efectivo', $pagoActualizado->metodo_pago);
        $this->assertNotNull($pagoActualizado->fecha_pago);
        $this->assertEquals('LIQ-001', $pagoActualizado->num_liquidacion);
    }

    /**
     * Test de estadísticas de pagos.
     */
    public function test_obtiene_estadisticas_de_pagos()
    {
        // Crear varias matrículas con pagos en diferentes estados
        $estudiante1 = Estudiante::factory()->create();
        $estudiante2 = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '2 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        // Matrícula 1
        $matricula1 = Matricula::create([
            'estudiante_id' => $estudiante1->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        // Matrícula 2
        $matricula2 = Matricula::create([
            'estudiante_id' => $estudiante2->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula1->refresh();
        $matricula2->refresh();

        // Pagar un pago de matrícula 1
        $pago1 = $matricula1->cronograma->pagos()->first();
        $pago1->update([
            'estado' => EstadoPago::PAGADO,
            'fecha_pago' => now(),
            'metodo_pago' => 'Efectivo',
        ]);

        // Marcar un pago como vencido en matrícula 2
        $pago2 = $matricula2->cronograma->pagos()->first();
        $pago2->update([
            'estado' => EstadoPago::VENCIDO,
            'fecha_vencimiento' => now()->subDays(10),
        ]);

        // Obtener estadísticas
        $estadisticas = $this->service->obtenerEstadisticasPagos();

        $this->assertIsArray($estadisticas);
        $this->assertArrayHasKey('cantidad', $estadisticas);
        $this->assertArrayHasKey('montos', $estadisticas);
        $this->assertArrayHasKey('porcentajes', $estadisticas);

        // Verificar cantidades
        $this->assertEquals(4, $estadisticas['cantidad']['total']); // 2 matrículas * 2 cuotas
        $this->assertEquals(1, $estadisticas['cantidad']['pagados']);
        $this->assertEquals(1, $estadisticas['cantidad']['vencidos']);
        $this->assertEquals(2, $estadisticas['cantidad']['pendientes']);
    }

    /**
     * Test de obtención de cronogramas con cuotas vencidas.
     */
    public function test_obtiene_cronogramas_con_cuotas_vencidas()
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

        // Marcar un pago como vencido
        $pago = $matricula->cronograma->pagos()->first();
        $pago->update([
            'estado' => EstadoPago::VENCIDO,
            'fecha_vencimiento' => now()->subDays(5),
        ]);

        // Obtener cronogramas con cuotas vencidas
        $cronogramas = $this->service->obtenerCronogramasConCuotasVencidas();

        $this->assertCount(1, $cronogramas);
        $this->assertEquals($matricula->cronograma->id, $cronogramas->first()->id);
    }

    /**
     * Test de verificación de consistencia de cronograma.
     */
    public function test_verifica_consistencia_de_cronograma()
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

        // Verificar consistencia
        $resultado = $this->service->verificarConsistenciaCronograma($matricula->cronograma->id);

        $this->assertTrue($resultado['consistente']);
        $this->assertEquals(0, $resultado['diferencia']);
        $this->assertEquals(300, $resultado['monto_total']); // 3 * 100
        $this->assertEquals(300, $resultado['suma_cuotas']);
        $this->assertEquals(3, $resultado['num_cuotas_esperadas']);
        $this->assertEquals(3, $resultado['num_cuotas_reales']);
    }
}
