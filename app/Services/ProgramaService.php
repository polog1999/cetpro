<?php

namespace App\Services;

use App\Repositories\ProgramaRepositoryInterface;
use App\Repositories\CursoRepositoryInterface;
use App\Models\Programa;
use Illuminate\Database\Eloquent\Collection;

class ProgramaService
{
    public function __construct(
        private ProgramaRepositoryInterface $programas,
        private CursoRepositoryInterface $cursos
    ) {}
    
    /**
     * Obtiene todos los programas
     *
     * @return Collection
     */
    public function obtenerTodos(): Collection
    {
        return $this->programas->all();
    }
    
    /**
     * Busca un programa por ID
     *
     * @param int $id
     * @return Programa|null
     */
    public function buscar(int $id): ?Programa
    {
        return $this->programas->find($id);
    }
    
    /**
     * Obtiene los cursos de un programa formateados de manera simple
     * (solo nombres con viñetas)
     *
     * @param int $programaId
     * @return string
     */
    public function obtenerCursosFormateadosSimple(int $programaId): string
    {
        $cursos = $this->cursos->findByPrograma($programaId);
        
        if ($cursos->isEmpty()) {
            return 'No hay cursos asignados a este programa.';
        }
        
        return $cursos
            ->map(fn($curso) => '- ' . ($curso->nombre_curso ?? 'Sin nombre'))
            ->implode(PHP_EOL);
    }
}
