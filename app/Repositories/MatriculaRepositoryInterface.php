<?php

namespace App\Repositories;

use App\Models\Matricula;
use App\Enums\EstadoMatricula;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Matrículas.
 */
interface MatriculaRepositoryInterface
{
    public function all(): Collection;
    
    public function find(int $id): ?Matricula;
    
    public function create(array $data): Matricula;
    
    public function update(Matricula $matricula, array $data): Matricula;
    
    public function delete(Matricula $matricula): void;
    
    /**
     * Busca una matrícula con todas sus relaciones cargadas.
     */
    public function findWithRelations(int $id): ?Matricula;
    
    /**
     * Busca matrícula activa por estudiante y horario.
     */
    public function findActivaPorEstudianteYHorario(int $estudianteId, int $horarioId): ?Matricula;
    
    /**
     * Busca matrícula activa por estudiante y programa.
     */
    public function findActivaPorEstudianteYPrograma(int $estudianteId, int $programaId): ?Matricula;
    
    /**
     * Cuenta matrículas en un horario.
     */
    public function countMatriculadosPorHorario(int $horarioId, ?EstadoMatricula $excludeEstado = null): int;
    
    /**
     * Obtiene todas las matrículas de un estudiante.
     */
    public function getMatriculasPorEstudiante(int $estudianteId): Collection;
    
    /**
     * Obtiene matrículas activas en un horario.
     */
    public function getMatriculasActivasPorHorario(int $horarioId): Collection;
    
    /**
     * Verifica si existe matrícula activa.
     */
    public function existsMatriculaActiva(int $estudianteId, int $horarioId,?int $ignorar = null): bool;
    
    /**
     * Cuenta matrículas por prefijo de código (para generar códigos secuenciales).
     */
    public function contarPorPrefijoCodigo(string $prefijo): int;
    
    /**
     * Cuenta matrículas activas en un horario (excluye anuladas).
     */
    public function contarActivos(int $horarioId): int;
}
