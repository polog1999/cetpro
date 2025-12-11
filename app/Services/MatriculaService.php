<?php

namespace App\Services;

use App\Models\Matricula;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Curso;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de dominio para la lógica de negocio de matrículas.
 * 
 * Este servicio centraliza todas las operaciones relacionadas con matrículas,
 * asegurando consistencia y reutilización de la lógica de negocio.
 */
class MatriculaService
{
    /**
     * Valida si un estudiante puede matricularse en un horario específico.
     *
     * @param int $estudianteId
     * @param int $horarioId
     * @param string|TipoMatricula $tipoMatricula
     * @param int|null $cursoId
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarMatricula(
        int $estudianteId,
        int $horarioId,
        string|TipoMatricula $tipoMatricula,
        ?int $cursoId = null
    ): array {
        $errores = [];

        // 1. Validar que el estudiante existe
        $estudiante = Estudiante::find($estudianteId);
        if (!$estudiante) {
            $errores[] = 'El estudiante no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // 2. Validar que el horario existe
        $horario = Horario::find($horarioId);
        if (!$horario) {
            $errores[] = 'El horario no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // 3. Validar vacantes disponibles
        if (!$this->tieneVacantesDisponibles($horarioId)) {
            $errores[] = 'No hay vacantes disponibles en este horario.';
        }

        // 4. Validar matrícula duplicada
        if ($this->tieneMatriculaDuplicada($estudianteId, $horarioId)) {
            $errores[] = 'El estudiante ya está matriculado en este horario.';
        }

        // 5. Validar requisitos de módulos (si es tipo MODULO)
        if ($tipoMatricula === TipoMatricula::MODULO && $cursoId) {
            $validacionModulo = $this->validarRequisitosModulo($estudianteId, $cursoId, $horario->id_programa);
            if (!$validacionModulo['valido']) {
                $errores = array_merge($errores, $validacionModulo['errores']);
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Verifica si hay vacantes disponibles en un horario.
     *
     * @param int $horarioId
     * @return bool
     */
    public function tieneVacantesDisponibles(int $horarioId): bool
    {
        $horario = Horario::find($horarioId);
        
        if (!$horario) {
            return false;
        }

        $matriculados = Matricula::where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->count();

        return $matriculados < $horario->vacantes;
    }

    /**
     * Verifica si un estudiante ya tiene una matrícula activa en un horario.
     *
     * @param int $estudianteId
     * @param int $horarioId
     * @return bool
     */
    public function tieneMatriculaDuplicada(int $estudianteId, int $horarioId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->exists();
    }

    /**
     * Valida los requisitos previos para matricularse en un módulo.
     * 
     * Por ejemplo, verifica que el estudiante haya completado módulos anteriores
     * si el programa tiene una secuencia obligatoria.
     *
     * @param int $estudianteId
     * @param int $cursoId (módulo actual)
     * @param int $programaId
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarRequisitosModulo(int $estudianteId, int $cursoId, int $programaId): array
    {
        $errores = [];

        // Obtener el curso/módulo actual
        $cursoActual = Curso::find($cursoId);
        if (!$cursoActual) {
            $errores[] = 'El módulo seleccionado no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // Obtener todos los cursos/módulos del programa ordenados
        $cursos = Curso::where('id_programa', $programaId)
            ->orderBy('nombre_curso')
            ->get();

        // Encontrar la posición del curso actual
        $posicionActual = $cursos->search(function ($curso) use ($cursoId) {
            return $curso->id_curso === $cursoId;
        });

        // Si es el primer módulo, no hay requisitos
        if ($posicionActual === 0) {
            return ['valido' => true, 'errores' => []];
        }

        // Verificar que haya completado todos los módulos anteriores
        for ($i = 0; $i < $posicionActual; $i++) {
            $cursoAnterior = $cursos[$i];
            
            $matriculaAnterior = Matricula::where('estudiante_id', $estudianteId)
                ->where('id_curso', $cursoAnterior->id_curso)
                ->where('tipo_matricula', TipoMatricula::MODULO)
                ->where('estado', EstadoMatricula::CULMINADO)
                ->exists();

            if (!$matriculaAnterior) {
                $errores[] = "Debe completar el módulo '{$cursoAnterior->nombre_curso}' antes de matricularse en '{$cursoActual->nombre_curso}'.";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Anula una matrícula con validaciones de negocio.
     *
     * @param int $matriculaId
     * @param string $motivo
     * @param int|null $usuarioId Usuario que realiza la anulación
     * @return Matricula
     * @throws ValidationException
     */
    public function anularMatricula(int $matriculaId, string $motivo, ?int $usuarioId = null): Matricula
    {
        $matricula = Matricula::find($matriculaId);

        if (!$matricula) {
            throw ValidationException::withMessages([
                'matricula' => 'La matrícula no existe.',
            ]);
        }

        // No permitir anular una matrícula ya anulada
        if ($matricula->estado === EstadoMatricula::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'Esta matrícula ya está anulada.',
            ]);
        }

        DB::beginTransaction();
        try {
            // Actualizar la matrícula
            $matricula->update([
                'estado' => EstadoMatricula::ANULADO,
                'motivo_anulacion' => $motivo,
                'fecha_anulacion' => now(),
            ]);

            // Anular los pagos pendientes del cronograma
            if ($matricula->cronograma) {
                $matricula->cronograma->pagos()
                    ->where('estado', \App\Enums\EstadoPago::PENDIENTE)
                    ->update(['estado' => \App\Enums\EstadoPago::ANULADO]);
            }

            DB::commit();

            return $matricula->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambia un estudiante de una sección/horario a otra.
     *
     * @param int $matriculaId
     * @param int $nuevoHorarioId
     * @param string $motivo
     * @return Matricula
     * @throws ValidationException
     */
    public function cambiarSeccion(int $matriculaId, int $nuevoHorarioId, string $motivo): Matricula
    {
        $matricula = Matricula::with('cronograma')->find($matriculaId);

        if (!$matricula) {
            throw ValidationException::withMessages([
                'matricula' => 'La matrícula no existe.',
            ]);
        }

        // No permitir cambio si está anulada o culminada
        if ($matricula->estado === EstadoMatricula::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cambiar de sección una matrícula anulada.',
            ]);
        }

        if ($matricula->estado === EstadoMatricula::CULMINADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cambiar de sección una matrícula culminada.',
            ]);
        }

        // Validar que haya vacantes en el nuevo horario
        if (!$this->tieneVacantesDisponibles($nuevoHorarioId)) {
            throw ValidationException::withMessages([
                'horario' => 'No hay vacantes disponibles en el horario destino.',
            ]);
        }

        // Validar que el nuevo horario sea del mismo programa
        $nuevoHorario = Horario::find($nuevoHorarioId);
        $horarioActual = Horario::find($matricula->horario_id);

        if ($nuevoHorario->id_programa !== $horarioActual->id_programa) {
            throw ValidationException::withMessages([
                'horario' => 'El nuevo horario debe ser del mismo programa.',
            ]);
        }

        DB::beginTransaction();
        try {
            // Cambiar el horario
            $matricula->update([
                'horario_id' => $nuevoHorarioId,
            ]);

            // Nota: El cronograma de pagos se mantiene igual ya que es el mismo programa
            // Si se requiere ajustar el cronograma, hacerlo aquí

            DB::commit();

            return $matricula->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene las vacantes disponibles de un horario.
     *
     * @param int $horarioId
     * @return array ['total' => int, 'ocupadas' => int, 'disponibles' => int]
     */
    public function obtenerVacantes(int $horarioId): array
    {
        $horario = Horario::find($horarioId);

        if (!$horario) {
            return ['total' => 0, 'ocupadas' => 0, 'disponibles' => 0];
        }

        $ocupadas = Matricula::where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->count();

        return [
            'total' => $horario->vacantes,
            'ocupadas' => $ocupadas,
            'disponibles' => max(0, $horario->vacantes - $ocupadas),
        ];
    }

    /**
     * Obtiene el historial de matrículas de un estudiante.
     *
     * @param int $estudianteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorialMatriculas(int $estudianteId)
    {
        return Matricula::with(['horario.programa', 'curso', 'cronograma.pagos'])
            ->where('estudiante_id', $estudianteId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verifica si un estudiante está matriculado en algún programa activo.
     *
     * @param int $estudianteId
     * @return bool
     */
    public function tieneMatriculaActiva(int $estudianteId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->whereIn('estado', [EstadoMatricula::ENPROCESO])
            ->exists();
    }
}
