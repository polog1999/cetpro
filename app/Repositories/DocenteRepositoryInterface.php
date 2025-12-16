<?php

namespace App\Repositories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Docentes.
 * 
 * Encapsula operaciones de acceso a datos para el modelo Docente.
 */
interface DocenteRepositoryInterface
{
    /**
     * Obtiene todos los docentes.
     *
     * @return Collection<Docente>
     */
    public function all(): Collection;

    /**
     * Busca un docente por su ID.
     *
     * @param int $id
     * @return Docente|null
     */
    public function find(int $id): ?Docente;

    /**
     * Crea un nuevo docente.
     *
     * @param array $data
     * @return Docente
     */
    public function create(array $data): Docente;

    /**
     * Actualiza un docente existente.
     *
     * @param Docente $docente
     * @param array $data
     * @return Docente
     */
    public function update(Docente $docente, array $data): Docente;

    /**
     * Elimina un docente.
     *
     * @param Docente $docente
     * @return void
     */
    public function delete(Docente $docente): void;

    /**
     * Busca un docente por tipo y número de documento.
     *
     * @param string $tipoDocumento
     * @param string $nroDocumento
     * @return Docente|null
     */
    public function findByDocumento(string $tipoDocumento, string $nroDocumento): ?Docente;

    /**
     * Verifica si el docente tiene dependencias (horarios asignados).
     *
     * @param int $id
     * @return bool
     */
    public function hasDependencies(int $id): bool;
}
