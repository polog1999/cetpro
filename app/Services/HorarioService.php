<?php

namespace App\Services;

use App\Repositories\HorarioRepositoryInterface;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\CursoRepositoryInterface;
use App\Models\Horario;
use App\Enums\TipoMatricula;
use Illuminate\Support\Collection;

class HorarioService
{
    public function __construct(
        private HorarioRepositoryInterface $horarios,
        private DocenteRepositoryInterface $docentes,
        private CursoRepositoryInterface $cursos
    ) {}
    
    /**
     * Valida si existe conflicto de horarios para un docente o aula
     *
     * @param int $docenteId
     * @param array $dias
     * @param string $horaInicio
     * @param string $horaFin
     * @param string|null $aula
     * @param int|null $horarioIdIgnorar
     * @return array ['valido' => bool, 'mensaje' => string, 'conflictos' => Collection]
     */
    public function validarConflictoHorario(
        int $docenteId,
        array $dias,
        string $horaInicio,
        string $horaFin,
        ?string $aula = null,
        ?int $horarioIdIgnorar = null
    ): array {
        // Validar entrada
        if (empty($dias)) {
            return ['valido' => true];
        }
        
        // Buscar conflictos para el docente
        $conflictosDocente = $this->horarios->findConflictosHorario(
            $docenteId,
            $dias,
            $horaInicio,
            $horaFin,
            $horarioIdIgnorar
        );
        
        if ($conflictosDocente->isNotEmpty()) {
            $docente = $this->docentes->find($docenteId);
            $nombreDocente = $docente?->nombre_completo ?? 'el docente';
            
            return [
                'valido' => false,
                'mensaje' => "Existe un cruce de horarios para {$nombreDocente}.",
                'conflictos' => $conflictosDocente
            ];
        }
        
        // Buscar conflictos para el aula (si hay aula especificada)
        if ($aula) {
            $conflictosAula = $this->horarios->findConflictosAula(
                $aula,
                $dias,
                $horaInicio,
                $horaFin,
                $horarioIdIgnorar
            );
            
            if ($conflictosAula->isNotEmpty()) {
                return [
                    'valido' => false,
                    'mensaje' => "El aula {$aula} ya está ocupada en ese horario.",
                    'conflictos' => $conflictosAula
                ];
            }
        }
        
        return ['valido' => true];
    }
    
    /**
     * Obtiene los cursos formateados de un horario para mostrar en UI
     *
     * @param int $horarioId
     * @param TipoMatricula $tipoMatricula
     * @return array ['success' => bool, 'texto' => string, 'cursos' => Collection|null]
     */
    public function obtenerCursosFormateados(int $horarioId, TipoMatricula $tipoMatricula): array
    {
        $horario = $this->horarios->find($horarioId);
        
        if (!$horario) {
            return [
                'success' => false,
                'texto' => 'Horario no encontrado.',
                'cursos' => null
            ];
        }
        
        $cursos = $this->cursos->findByPrograma($horario->id_programa);
        
        if ($cursos->isEmpty()) {
            $mensaje = ($tipoMatricula === TipoMatricula::PROGRAMA || 
                        $tipoMatricula === TipoMatricula::MODULO)
                ? 'Este programa no tiene módulos registrados.'
                : 'Esta formación continua no tiene cursos registrados.';
                
            return [
                'success' => false,
                'texto' => $mensaje,
                'cursos' => collect()
            ];
        }
        
        $texto = $cursos->values()->map(function ($curso, $index) {
            $n = $index + 1;
            $nombre = $curso->nombre_curso;
            
            $fechaInicio = $curso->fecha_inicio 
                ? \Carbon\Carbon::parse($curso->fecha_inicio)->format('d/m/Y')
                : 'Sin fecha';
                
            $fechaFin = $curso->fecha_termino 
                ? \Carbon\Carbon::parse($curso->fecha_termino)->format('d/m/Y')
                : 'Sin fecha';
            
            return "{$n}. {$nombre} | Inicio: {$fechaInicio} | Fin: {$fechaFin}";
        })->implode(PHP_EOL);
        
        return [
            'success' => true,
            'texto' => $texto,
            'cursos' => $cursos
        ];
    }
}
