<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EstudianteController;
use App\Http\Controllers\Api\V1\ApoderadoController;
use App\Http\Controllers\Api\V1\MatriculaController;
use App\Http\Controllers\Api\V1\CronogramaController;
use App\Http\Controllers\Api\V1\PagoController;
use App\Http\Controllers\Api\V1\ProgramaController;
use App\Http\Controllers\Api\V1\CursoController;
use App\Http\Controllers\Api\V1\EspecialidadController;
use App\Http\Controllers\Api\V1\HorarioController;
use App\Http\Controllers\Api\V1\DocenteController;
use App\Http\Controllers\Api\V1\EmpleadoController;
use App\Http\Controllers\Api\V1\UsuarioController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermisoController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Rutas de la API REST versionada para CETPRO-MDLM.
| Todas las rutas están bajo el prefijo /api/v1
| 
| Arquitectura: Controladores → Services → Repositories → Modelos
|
*/

Route::prefix('v1')->group(function () {

    // ============================================
    // Módulo Estudiantes
    // ============================================
    
    Route::apiResource('estudiantes', EstudianteController::class);
    Route::apiResource('apoderados', ApoderadoController::class);

    // ============================================
    // Módulo Matrículas
    // ============================================
    
    Route::apiResource('matriculas', MatriculaController::class)
        ->except(['update']); // No se permite edición completa de matrícula
    
    // Acciones especiales de matrícula
    Route::post('matriculas/{id}/anular', [MatriculaController::class, 'anular']);
    
    // Cronogramas
    Route::get('cronogramas/{id}', [CronogramaController::class, 'show']);
    Route::get('cronogramas/{id}/pagos', [CronogramaController::class, 'pagos']);
    Route::post('cronogramas/{id}/actualizar-estados-vencidos', [CronogramaController::class, 'actualizarEstadosVencidos']);
    
    // Pagos
    Route::get('pagos/{id}', [PagoController::class, 'show']);
    Route::post('pagos/{id}/registrar', [PagoController::class, 'registrar']);
    Route::post('pagos/{id}/anular', [PagoController::class, 'anular']);
    Route::post('pagos/{id}/revertir', [PagoController::class, 'revertir']);

    // ============================================
    // Módulo Académico
    // ============================================
    
    Route::apiResource('programas', ProgramaController::class);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('especialidades', EspecialidadController::class);
    Route::apiResource('horarios', HorarioController::class);
    
    // Acción especial de horario
    Route::get('horarios/{id}/vacantes', [HorarioController::class, 'vacantes']);

    // ============================================
    // Módulo RRHH
    // ============================================
    
    Route::apiResource('docentes', DocenteController::class);
    Route::apiResource('empleados', EmpleadoController::class);

    // ============================================
    // Módulo Autenticación / Roles
    // ============================================
    
    Route::apiResource('usuarios', UsuarioController::class);
    Route::patch('usuarios/{id}/password', [UsuarioController::class, 'cambiarPassword']);
    Route::patch('usuarios/{id}/activar', [UsuarioController::class, 'activar']);
    Route::patch('usuarios/{id}/desactivar', [UsuarioController::class, 'desactivar']);
    
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/permisos', [RoleController::class, 'asignarPermisos']);
    
    Route::apiResource('permisos', PermisoController::class)
        ->only(['index', 'show']);
});
