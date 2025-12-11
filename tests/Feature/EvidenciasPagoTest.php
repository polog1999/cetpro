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
use App\Services\EvidenciaPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EvidenciasPagoTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenciaPagoService $service;
    protected Usuario $admin;
    protected Usuario $usuarioRegular;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('private');
        $this->service = new EvidenciaPagoService();

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
     * Crea un pago de prueba.
     */
    protected function crearPago(): Pago
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
        return $matricula->cronograma->pagos()->first();
    }

    /**
     * Test que se puede subir evidencia con tipo de archivo válido.
     */
    public function test_se_puede_subir_evidencia_con_tipo_de_archivo_valido()
    {
        $pago = $this->crearPago();

        // Tipos permitidos: pdf, jpg, jpeg, png, webp
        $tiposValidos = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        foreach ($tiposValidos as $tipo) {
            $archivo = UploadedFile::fake()->create("evidencia.{$tipo}", 100); // 100KB

            $path = $this->service->guardarEvidencia($archivo, $pago->id);

            // Verificar que se guardó el archivo
            $this->assertNotNull($path);
            Storage::disk('private')->assertExists($path);

            // Limpiar para siguiente iteración
            Storage::disk('private')->delete($path);
        }
    }

    /**
     * Test que no permite evidencias con tamaño no permitido.
     */
    public function test_no_permite_evidencias_con_tamano_no_permitido()
    {
        $this->expectException(ValidationException::class);

        $pago = $this->crearPago();

        // Crear archivo de 6MB (excede el límite de 5MB)
        $archivo = UploadedFile::fake()->create('evidencia.pdf', 6144); // 6MB en KB

        $this->service->guardarEvidencia($archivo, $pago->id);
    }

    /**
     * Test que no permite evidencias con tipo no permitido.
     */
    public function test_no_permite_evidencias_con_tipo_no_permitido()
    {
        $this->expectException(ValidationException::class);

        $pago = $this->crearPago();

        // Crear archivo con extensión no permitida
        $archivo = UploadedFile::fake()->create('evidencia.exe', 100);

        $this->service->guardarEvidencia($archivo, $pago->id);
    }

    /**
     * Test que solo roles autorizados pueden ver evidencias.
     */
    public function test_solo_roles_autorizados_pueden_ver_evidencias()
    {
        $pago = $this->crearPago();

        // Subir evidencia
        $archivo = UploadedFile::fake()->create('evidencia.pdf', 100);
        $path = $this->service->guardarEvidencia($archivo, $pago->id);

        // Actualizar el pago con la evidencia
        $pago->update(['evidencia_path' => $path]);

        // El admin debe poder ver la evidencia
        $url = $this->service->obtenerUrlVisualizacion($pago->id, $this->admin->id);
        $this->assertNotNull($url);
        $this->assertStringContainsString('evidencias-pagos', $url);

        // Un usuario no autorizado no debe poder ver la evidencia
        $this->expectException(ValidationException::class);
        $this->service->obtenerUrlVisualizacion($pago->id, $this->usuarioRegular->id);
    }

    /**
     * Test de validación de archivo.
     */
    public function test_valida_archivo_correctamente()
    {
        // Archivo válido
        $archivoValido = UploadedFile::fake()->create('evidencia.pdf', 100);
        $validacion = $this->service->validarArchivo($archivoValido);
        
        $this->assertTrue($validacion['valido']);
        $this->assertEmpty($validacion['errores']);

        // Archivo con tamaño excesivo
        $archivoGrande = UploadedFile::fake()->create('evidencia.pdf', 6144); // 6MB
        $validacion = $this->service->validarArchivo($archivoGrande);
        
        $this->assertFalse($validacion['valido']);
        $this->assertNotEmpty($validacion['errores']);
        $this->assertStringContainsString('tamaño máximo', $validacion['errores'][0]);

        // Archivo con tipo no permitido
        $archivoInvalido = UploadedFile::fake()->create('evidencia.txt', 100);
        $validacion = $this->service->validarArchivo($archivoInvalido);
        
        $this->assertFalse($validacion['valido']);
        $this->assertNotEmpty($validacion['errores']);
        $this->assertStringContainsString('Tipo de archivo no permitido', $validacion['errores'][0]);
    }

    /**
     * Test que se reemplaza evidencia anterior al subir nueva.
     */
    public function test_reemplaza_evidencia_anterior_al_subir_nueva()
    {
        $pago = $this->crearPago();

        // Subir primera evidencia
        $archivo1 = UploadedFile:: fake()->create('evidencia1.pdf', 100);
        $path1 = $this->service->guardarEvidencia($archivo1, $pago->id);
        $pago->update(['evidencia_path' => $path1]);

        // Verificar que existe
        Storage::disk('private')->assertExists($path1);

        // Subir segunda evidencia
        $archivo2 = UploadedFile::fake()->create('evidencia2.jpg', 100);
        $path2 = $this->service->guardarEvidencia($archivo2, $pago->id);

        // La primera debe haber sido eliminada
        Storage::disk('private')->assertMissing($path1);
        
        // La segunda debe existir
        Storage::disk('private')->assertExists($path2);
    }

    /**
     * Test de descarga de evidencia.
     */
    public function test_descarga_evidencia_correctamente()
    {
        $pago = $this->crearPago();

        // Subir evidencia
        $archivo = UploadedFile::fake()->create('evidencia.pdf', 100);
        $path = $this->service->guardarEvidencia($archivo, $pago->id);
        $pago->update(['evidencia_path' => $path]);

        // Descargar evidencia como admin
        $response = $this->service->descargarEvidencia($pago->id, $this->admin->id);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /**
     * Test que no permite descargar evidencia inexistente.
     */
    public function test_no_permite_descargar_evidencia_inexistente()
    {
        $this->expectException(ValidationException::class);

        $pago = $this->crearPago();

        // Pago sin evidencia
        $this->service->descargarEvidencia($pago->id, $this->admin->id);
    }

    /**
     * Test de estadísticas de evidencias.
     */
    public function test_obtiene_estadisticas_de_evidencias()
    {
        $pago1 = $this->crearPago();
        $pago2 = $this->crearPago();

        // Subir evidencia solo al primer pago
        $archivo = UploadedFile::fake()->create('evidencia.pdf', 100);
        $path = $this->service->guardarEvidencia($archivo, $pago1->id);
        $pago1->update(['evidencia_path' => $path]);

        // Obtener estadísticas
        $estadisticas = $this->service->obtenerEstadisticas();

        $this->assertIsArray($estadisticas);
        $this->assertEquals(2, $estadisticas['total_pagos']); // 2 pagos x 2 matrículas
        $this->assertEquals(1, $estadisticas['con_evidencia']);
    }

    /**
     * Test de limpieza de evidencias huérfanas.
     */
    public function test_limpia_evidencias_huerfanas()
    {
        $pago = $this->crearPago();

        // Crear archivo huérfano (sin registro en BD)
        Storage::disk('private')->put(
            'evidencias-pagos/huerfano.pdf',
            'contenido de prueba'
        );

        // Subir evidencia legítima
        $archivo = UploadedFile::fake()->create('evidencia.pdf', 100);
        $path = $this->service->guardarEvidencia($archivo, $pago->id);
        $pago->update(['evidencia_path' => $path]);

        // Limpiar huérfanas
        $eliminados = $this->service->limpiarEvidenciasHuerfanas();

        // Debe haber eliminado 1 archivo (el huérfano)
        $this->assertEquals(1, $eliminados);

        // El archivo legítimo debe seguir existiendo
        Storage::disk('private')->assertExists($path);

        // El huérfano debe haber sido eliminado
        Storage::disk('private')->assertMissing('evidencias-pagos/huerfano.pdf');
    }
}
