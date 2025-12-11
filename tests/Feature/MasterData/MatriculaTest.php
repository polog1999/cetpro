<?php

namespace Tests\Feature\MasterData;

use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Enums\TipoPrograma;
use App\Filament\Resources\Matriculas\Pages\CreateMatricula;
use App\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Programa;
use App\Models\Curso;
use App\Models\Usuario;
use App\Models\Role;
use App\Models\Especialidad;
use App\Services\MatriculaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
use Filament\Actions\Action;

class MatriculaTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected MatriculaService $matriculaService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $rolAdmin = Role::create(['nombre' => 'Administrador', 'es_admin' => true]);
        $this->admin = Usuario::factory()->create(['role_id' => $rolAdmin->id, 'activo' => true]);
        $this->matriculaService = new MatriculaService();
    }

    // ========================================
    // TESTS BÁSICOS DE MATRÍCULA
    // ========================================

    public function test_puede_matricular_estudiante_en_programa_con_seccion_valida()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create([
            'vacantes' => 20,
            'activo' => true,
        ]);
        $horario->refresh();

        // Simular selección en el formulario
        Livewire::actingAs($this->admin)
            ->test(CreateMatricula::class)
            ->set('data.estudiante_id', $estudiante->id)
            ->set('data.tipo_matricula', TipoMatricula::PROGRAMA->value)
            ->set('data.programa_intermediario', $horario->id_programa) // Para filtrar horarios
            ->set('data.horario_id', $horario->id_horario)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id,
            'tipo_matricula' => TipoMatricula::PROGRAMA->value,
        ]);
    }

    public function test_no_permite_matricula_si_seccion_no_tiene_cupo()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create([
            'vacantes' => 0, // Sin vacantes
            'activo' => true,
        ]);
        $horario->refresh();

        Livewire::actingAs($this->admin)
            ->test(CreateMatricula::class)
            ->set('data.estudiante_id', $estudiante->id)
            ->set('data.tipo_matricula', TipoMatricula::PROGRAMA->value)
            ->set('data.programa_intermediario', $horario->id_programa)
            ->set('data.horario_id', $horario->id_horario)
            ->call('create')
            ->assertHasErrors(); // Esperamos error de validación
    }

    public function test_no_permite_matricula_duplicada_para_mismo_programa_y_periodo()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create([
            'vacantes' => 20,
            'activo' => true,
        ]);
        $horario->refresh();

        // Crear una matrícula existente
        Matricula::factory()->create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id,
            'tipo_matricula' => TipoMatricula::PROGRAMA->value,
        ]);

        // Intentar matricular de nuevo
        Livewire::actingAs($this->admin)
            ->test(CreateMatricula::class)
            ->set('data.estudiante_id', $estudiante->id)
            ->set('data.tipo_matricula', TipoMatricula::PROGRAMA->value)
            ->set('data.programa_intermediario', $horario->id_programa)
            ->set('data.horario_id', $horario->id_horario)
            ->call('create')
            ->assertHasErrors(); // Debería fallar
    }

    public function test_puede_anular_matricula_guardando_motivo()
    {
        $matricula = Matricula::factory()->create([
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListMatriculas::class)
            ->callTableAction('anular', $matricula, data: [
                'motivo_anulacion' => 'Estudiante se retiró',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('matriculas', [
            'id' => $matricula->id,
            'estado' => EstadoMatricula::ANULADO->value,
            'motivo_anulacion' => 'Estudiante se retiró',
        ]);
    }

    // ========================================
    // TESTS DEL SERVICIO DE MATRÍCULA
    // ========================================

    public function test_servicio_valida_correctamente_matricula_valida()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create(['vacantes' => 10]);

        $resultado = $this->matriculaService->validarMatricula(
            $estudiante->id,
            $horario->id_horario,
            TipoMatricula::PROGRAMA
        );

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    public function test_servicio_detecta_falta_de_vacantes()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create(['vacantes' => 1]);
        
        // Llenar el horario
        Matricula::factory()->create([
            'horario_id' => $horario->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $resultado = $this->matriculaService->validarMatricula(
            $estudiante->id,
            $horario->id_horario,
            TipoMatricula::PROGRAMA
        );

        $this->assertFalse($resultado['valido']);
        $this->assertContains('No hay vacantes disponibles en este horario.', $resultado['errores']);
    }

    public function test_servicio_detecta_matricula_duplicada()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create(['vacantes' => 10]);
        
        // Crear matrícula existente
        Matricula::factory()->create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $resultado = $this->matriculaService->validarMatricula(
            $estudiante->id,
            $horario->id_horario,
            TipoMatricula::PROGRAMA
        );

        $this->assertFalse($resultado['valido']);
        $this->assertContains('El estudiante ya está matriculado en este horario.', $resultado['errores']);
    }

    public function test_servicio_anula_matricula_correctamente()
    {
        $matricula = Matricula::factory()->create([
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $resultado = $this->matriculaService->anularMatricula(
            $matricula->id,
            'Retiro voluntario del estudiante'
        );

        $this->assertEquals(EstadoMatricula::ANULADO, $resultado->estado);
        $this->assertEquals('Retiro voluntario del estudiante', $resultado->motivo_anulacion);
        $this->assertNotNull($resultado->fecha_anulacion);
    }

    public function test_servicio_no_permite_anular_matricula_ya_anulada()
    {
        $this->expectException(ValidationException::class);

        $matricula = Matricula::factory()->create([
            'estado' => EstadoMatricula::ANULADO,
            'motivo_anulacion' => 'Ya anulada',
        ]);

        $this->matriculaService->anularMatricula(
            $matricula->id,
            'Intento de anular de nuevo'
        );
    }

    public function test_servicio_obtiene_vacantes_correctamente()
    {
        $horario = Horario::factory()->create(['vacantes' => 10]);
        
        // Crear 3 matrículas activas
        Matricula::factory()->count(3)->create([
            'horario_id' => $horario->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        // Crear 1 matrícula anulada (no debería contar)
        Matricula::factory()->create([
            'horario_id' => $horario->id_horario,
            'estado' => EstadoMatricula::ANULADO,
        ]);

        $vacantes = $this->matriculaService->obtenerVacantes($horario->id_horario);

        $this->assertEquals(10, $vacantes['total']);
        $this->assertEquals(3, $vacantes['ocupadas']);
        $this->assertEquals(7, $vacantes['disponibles']);
    }

    // ========================================
    // TESTS DE CAMBIO DE SECCIÓN
    // ========================================

    public function test_servicio_puede_cambiar_de_seccion_con_vacantes()
    {
        $especialidad = Especialidad::factory()->create();
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
        ]);
        
        $horario1 = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 10,
        ]);
        
        $horario2 = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 10,
        ]);

        $matricula = Matricula::factory()->create([
            'horario_id' => $horario1->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $resultado = $this->matriculaService->cambiarSeccion(
            $matricula->id,
            $horario2->id_horario,
            'Cambio por conflicto de horario'
        );

        $this->assertEquals($horario2->id_horario, $resultado->horario_id);
    }

    public function test_servicio_no_permite_cambio_de_seccion_sin_vacantes()
    {
        $this->expectException(ValidationException::class);

        $especialidad = Especialidad::factory()->create();
        $programa = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
        ]);
        
        $horario1 = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 10,
        ]);
        
        $horario2 = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 1,
        ]);

        // Llenar el horario destino
        Matricula::factory()->create([
            'horario_id' => $horario2->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula = Matricula::factory()->create([
            'horario_id' => $horario1->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $this->matriculaService->cambiarSeccion(
            $matricula->id,
            $horario2->id_horario,
            'Cambio de horario'
        );
    }

    public function test_servicio_no_permite_cambio_de_seccion_a_otro_programa()
    {
        $this->expectException(ValidationException::class);

        $especialidad = Especialidad::factory()->create();
        $programa1 = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
        ]);
        $programa2 = Programa::factory()->create([
            'id_especialidad' => $especialidad->id,
        ]);
        
        $horario1 = Horario::factory()->create([
            'id_programa' => $programa1->id_programa,
            'vacantes' => 10,
        ]);
        
        $horario2 = Horario::factory()->create([
            'id_programa' => $programa2->id_programa,
            'vacantes' => 10,
        ]);

        $matricula = Matricula::factory()->create([
            'horario_id' => $horario1->id_horario,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $this->matriculaService->cambiarSeccion(
            $matricula->id,
            $horario2->id_horario,
            'Cambio de programa'
        );
    }

    // ========================================
    // TESTS DE GENERACIÓN DE CRONOGRAMA
    // ========================================

    public function test_genera_cronograma_automaticamente_al_crear_matricula()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create(['vacantes' => 10]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $matricula->refresh();

        // Verificar que se creó un cronograma
        $this->assertNotNull($matricula->cronograma);
    }

    public function test_genera_codigo_inscripcion_unico_automaticamente()
    {
        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create(['vacantes' => 10]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'tipo_matricula' => TipoMatricula::PROGRAMA,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $this->assertNotNull($matricula->codigo_inscripcion);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{3}-\d{3}$/', $matricula->codigo_inscripcion);
    }

    // ========================================
    // TESTS DE REQUISITOS DE MÓDULOS
    // ========================================

    public function test_servicio_valida_requisitos_de_modulo_primer_modulo()
    {
        $especialidad = Especialidad::factory()->create();
        $programa = Programa::factory()->create([
            'tipo_programa' => TipoPrograma::PROGRAMA_ESTUDIO,
            'id_especialidad' => $especialidad->id,
        ]);

        // Crear módulos en orden
        $modulo1 = Curso::factory()->create([
            'id_programa' => $programa->id_programa,
            'nombre_curso' => 'Módulo 1',
        ]);

        $estudiante = Estudiante::factory()->create();

        // El primer módulo no debe tener requisitos
        $resultado = $this->matriculaService->validarRequisitosModulo(
            $estudiante->id,
            $modulo1->id_curso,
            $programa->id_programa
        );

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    public function test_servicio_valida_requisitos_de_modulo_con_prerequisito_completado()
    {
        $especialidad = Especialidad::factory()->create();
        $programa = Programa::factory()->create([
            'tipo_programa' => TipoPrograma::PROGRAMA_ESTUDIO,
            'id_especialidad' => $especialidad->id,
        ]);

        // Crear módulos en orden
        $modulo1 = Curso::factory()->create([
            'id_programa' => $programa->id_programa,
            'nombre_curso' => 'Módulo 1',
        ]);

        $modulo2 = Curso::factory()->create([
            'id_programa' => $programa->id_programa,
            'nombre_curso' => 'Módulo 2',
        ]);

        $estudiante = Estudiante::factory()->create();
        $horario = Horario::factory()->create([
            'id_programa' => $programa->id_programa,
            'vacantes' => 10,
        ]);

        // Crear matrícula culminada en módulo 1
        Matricula::factory()->create([
            'estudiante_id' => $estudiante->id,
            'horario_id' => $horario->id_horario,
            'id_curso' => $modulo1->id_curso,
            'tipo_matricula' => TipoMatricula::MODULO,
            'estado' => EstadoMatricula::CULMINADO,
        ]);

        // Validar que puede matricularse en módulo 2
        $resultado = $this->matriculaService->validarRequisitosModulo(
            $estudiante->id,
            $modulo2->id_curso,
            $programa->id_programa
        );

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    public function test_servicio_valida_requisitos_de_modulo_sin_prerequisito_completado()
    {
        $especialidad = Especialidad::factory()->create();
        $programa = Programa::factory()->create([
            'tipo_programa' => TipoPrograma::PROGRAMA_ESTUDIO,
            'id_especialidad' => $especialidad->id,
        ]);

        // Crear módulos en orden
        $modulo1 = Curso::factory()->create([
            'id_programa' => $programa->id_programa,
            'nombre_curso' => 'Módulo 1',
        ]);

        $modulo2 = Curso::factory()->create([
            'id_programa' => $programa->id_programa,
            'nombre_curso' => 'Módulo 2',
        ]);

        $estudiante = Estudiante::factory()->create();

        // Intentar validar módulo 2 sin haber completado módulo 1
        $resultado = $this->matriculaService->validarRequisitosModulo(
            $estudiante->id,
            $modulo2->id_curso,
            $programa->id_programa
        );

        $this->assertFalse($resultado['valido']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertStringContainsString('Módulo 1', $resultado['errores'][0]);
    }

    // ========================================
    // TESTS DE HISTORIAL
    // ========================================

    public function test_servicio_obtiene_historial_de_matriculas()
    {
        $estudiante = Estudiante::factory()->create();
        
        // Crear varias matrículas
        Matricula::factory()->count(3)->create([
            'estudiante_id' => $estudiante->id,
        ]);

        $historial = $this->matriculaService->obtenerHistorialMatriculas($estudiante->id);

        $this->assertCount(3, $historial);
    }

    public function test_servicio_detecta_matricula_activa()
    {
        $estudiante = Estudiante::factory()->create();
        
        Matricula::factory()->create([
            'estudiante_id' => $estudiante->id,
            'estado' => EstadoMatricula::ENPROCESO,
        ]);

        $tieneActiva = $this->matriculaService->tieneMatriculaActiva($estudiante->id);

        $this->assertTrue($tieneActiva);
    }

    public function test_servicio_no_detecta_matricula_activa_si_solo_hay_anuladas()
    {
        $estudiante = Estudiante::factory()->create();
        
        Matricula::factory()->create([
            'estudiante_id' => $estudiante->id,
            'estado' => EstadoMatricula::ANULADO,
        ]);

        $tieneActiva = $this->matriculaService->tieneMatriculaActiva($estudiante->id);

        $this->assertFalse($tieneActiva);
    }
}
