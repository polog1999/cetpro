<?php

namespace App\Repositories\Eloquent;

use App\Models\Matricula;
use App\Enums\EstadoMatricula;
use App\Repositories\MatriculaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MatriculaRepository implements MatriculaRepositoryInterface
{
    public function all(): Collection
    {
        return Matricula::all();
    }

    public function find(int $id): ?Matricula
    {
        return Matricula::find($id);
    }

    public function create(array $data): Matricula
    {
        return Matricula::create($data);
    }

    public function update(Matricula $matricula, array $data): Matricula
    {
        $matricula->update($data);
        return $matricula->fresh();
    }

    public function delete(Matricula $matricula): void
    {
        $matricula->delete();
    }

    public function findWithRelations(int $id): ?Matricula
    {
        return Matricula::with(['estudiante', 'horario', 'curso', 'cronograma.pagos'])->find($id);
    }

    public function findActivaPorEstudianteYHorario(int $estudianteId, int $horarioId): ?Matricula
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->first();
    }

    public function findActivaPorEstudianteYPrograma(int $estudianteId, int $programaId): ?Matricula
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->whereHas('horario', function ($query) use ($programaId) {
                $query->where('id_programa', $programaId);
            })
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->first();
    }

    public function countMatriculadosPorHorario(int $horarioId, ?EstadoMatricula $excludeEstado = null): int
    {
        $query = Matricula::where('horario_id', $horarioId);
        
        if ($excludeEstado) {
            $query->where('estado', '!=', $excludeEstado);
        }
        
        return $query->count();
    }

    public function getMatriculasPorEstudiante(int $estudianteId): Collection
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->with(['horario.programa', 'cronograma.pagos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getMatriculasActivasPorHorario(int $horarioId): Collection
    {
        return Matricula::where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->with('estudiante')
            ->get();
    }

    public function existsMatriculaActiva(int $estudianteId, int $horarioId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->exists();
    }
}
