<?php

namespace App\Repositories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Usuarios.
 * 
 * Encapsula operaciones de acceso a datos para el modelo Usuario.
 */
interface UsuarioRepositoryInterface
{
    /**
     * Obtiene todos los usuarios.
     *
     * @return Collection<Usuario>
     */
    public function all(): Collection;

    /**
     * Busca un usuario por su ID.
     *
     * @param int $id
     * @return Usuario|null
     */
    public function find(int $id): ?Usuario;

    /**
     * Crea un nuevo usuario.
     *
     * @param array $data
     * @return Usuario
     */
    public function create(array $data): Usuario;

    /**
     * Actualiza un usuario existente.
     *
     * @param Usuario $usuario
     * @param array $data
     * @return Usuario
     */
    public function update(Usuario $usuario, array $data): Usuario;

    /**
     * Elimina un usuario.
     *
     * @param Usuario $usuario
     * @return void
     */
    public function delete(Usuario $usuario): void;

    /**
     * Busca un usuario por su username.
     *
     * @param string $username
     * @return Usuario|null
     */
    public function findByUsername(string $username): ?Usuario;

    /**
     * Busca un usuario con su role cargado.
     *
     * @param int $id
     * @return Usuario|null
     */
    public function findWithRole(int $id): ?Usuario;

    /**
     * Obtiene usuarios activos.
     *
     * @return Collection<Usuario>
     */
    public function getActivos(): Collection;
}
