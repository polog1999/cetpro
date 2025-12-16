<?php

namespace App\Repositories;

use App\Models\Horario;
use Illuminate\Database\Eloquent\Collection;

interface HorarioRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Horario;
    public function create(array $data): Horario;
    public function update(Horario $horario, array $data): Horario;
    public function delete(Horario $horario): void;
    public function findByPrograma(int $programaId): Collection;
    public function findActivos(): Collection;
    public function hasDependencies(int $id): bool;
    
    /**
     * Busca conflictos de horario para un docente.
     */
    public function findConflictosHorario(
        int $docenteId,
        array $dias,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarId = null
    ): Collection;
    
    /**
     * Busca conflictos de horario para un aula.
     */
    public function findConflictosAula(
        string $aula,
        array $dias,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarId = null
    ): Collection;
}
