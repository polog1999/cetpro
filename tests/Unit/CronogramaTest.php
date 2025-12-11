<?php

namespace Tests\Unit;

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
use Illuminate\Foundation\Testing\RefreshDatabase;

class CronogramaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica que se generen cuotas correctas dado un monto total y número de cuotas.
     */
    public function test_se_generan_cuotas_correctas_dado_monto_total_y_numero_de_cuotas()
    {
        // Crear datos necesarios
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 200]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '6 meses',
        ]);
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
        ]);

        // Crear matrícula
        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();

        // Verificar que se creó el cronograma
        $this->assertNotNull($matricula->cronograma);

        $cronograma = $matricula->cronograma;

        // Verificar número de cuotas
        $this->assertEquals(6, $cronograma->num_cuotas);

        // Verificar monto total (6 meses * 200 por mes)
        $this->assertEquals(1200, $cronograma->monto_total);

        // Verificar que se generaron 6 pagos
        $this->assertEquals(6, $cronograma->pagos()->count());

        // Verificar que la suma de los montos de las cuotas sea igual al monto total
        $sumaCuotas = $cronograma->pagos()->sum('monto');
        $this->assertEquals($cronograma->monto_total, $sumaCuotas);

        // Verificar que todos los pagos tengan estado PENDIENTE
        $esperados = $cronograma->pagos()->where('estado', EstadoPago::PENDIENTE)->count();
        $this->assertEquals(6, $esperados);

        // Verificar que cada cuota tenga un monto correcto (200 por mes)
        $pagos = $cronograma->pagos()->orderBy('nro_cuota')->get();
        foreach ($pagos as $pago) {
            $this->assertEquals(200, $pago->monto);
            $this->assertEquals(EstadoPago::PENDIENTE, $pago->estado);
        }
    }

    /**
     * Test de ajuste de última cuota por redondeo.
     */
    public function test_ajusta_ultima_cuota_por_redondeo()
    {
        // Crear cronograma manualmente para tener control total
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

        // Verificar que la suma sea exacta
        $sumaCuotas = $cronograma->pagos()->sum('monto');
        $this->assertEquals($cronograma->monto_total, $sumaCuotas);
    }

    /**
     * Test de métodos auxiliares del cronograma.
     */
    public function test_metodos_auxiliares_del_cronograma()
    {
       
 // Crear cronograma con algunos pagos pagados
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 150]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '4 meses',
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

        // Marcar 2 cuotas como pagadas
        $pagos = $cronograma->pagos()->orderBy('nro_cuota')->take(2)->get();
        foreach ($pagos as $pago) {
            $pago->update([
                'estado' => EstadoPago::PAGADO,
                'fecha_pago' => now(),
                'metodo_pago' => 'Efectivo',
            ]);
        }

        // Verificar totalPagado()
        $this->assertEquals(300, $cronograma->totalPagado()); // 2 * 150

        // Verificar totalPendiente()
        $this->assertEquals(300, $cronograma->totalPendiente()); // 2 * 150

        // Verificar cuotasPagadas()
        $this->assertEquals(2, $cronograma->cuotasPagadas());

        // Verificar porcentajeCumplimiento()
        $this->assertEquals(50.0, $cronograma->porcentajeCumplimiento());

        // Verificar estaCompletamentePagado()
        $this->assertFalse($cronograma->estaCompletamentePagado());

        // Pagar las demás cuotas
        $pagosRestantes = $cronograma->pagos()->where('estado', EstadoPago::PENDIENTE)->get();
        foreach ($pagosRestantes as $pago) {
            $pago->update([
                'estado' => EstadoPago::PAGADO,
                'fecha_pago' => now(),
                'metodo_pago' => 'Efectivo',
            ]);
        }

        // Ahora debe estar completamente pagado
        $cronograma->refresh();
        $this->assertTrue($cronograma->estaCompletamentePagado());
        $this->assertEquals(100.0, $cronograma->porcentajeCumplimiento());
    }

    /**
     * Test de resumen del cronograma.
     */
    public function test_resumen_del_cronograma()
    {
        $estudiante = Estudiante::factory()->create();
        $especialidad = Especialidad::factory()->create(['costo_mensual' => 100]);
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
            'duracion' => '5 meses',
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

        $resumen = $cronograma->resumen();

        $this->assertIsArray($resumen);
        $this->assertArrayHasKey('num_cuotas', $resumen);
        $this->assertArrayHasKey('monto_total', $resumen);
        $this->assertArrayHasKey('total_pagado', $resumen);
        $this->assertArrayHasKey('total_pendiente', $resumen);
        $this->assertArrayHasKey('cuotas_pagadas', $resumen);
        $this->assertArrayHasKey('cuotas_pendientes', $resumen);
        $this->assertArrayHasKey('cuotas_vencidas', $resumen);
        $this->assertArrayHasKey('porcentaje_cumplimiento', $resumen);
        $this->assertArrayHasKey('esta_completo', $resumen);
        $this->assertArrayHasKey('tiene_deuda', $resumen);

        $this->assertEquals(5, $resumen['num_cuotas']);
        $this->assertEquals(500, $resumen['monto_total']);
        $this->assertEquals(0, $resumen['cuotas_pagadas']);
        $this->assertEquals(5, $resumen['cuotas_pendientes']);
    }
}
