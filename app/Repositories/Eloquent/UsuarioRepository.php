<?php

namespace App\Repositories\Eloquent;

use App\Models\Usuario;
use App\Repositories\UsuarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Usuarios.
 */
class UsuarioRepository implements UsuarioRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return Usuario::all();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Usuario $usuario, array $data): Usuario
    {
        $usuario->update($data);
        return $usuario->fresh();
    }

    /**
     * @inheritDoc
     */
    public function delete(Usuario $usuario): void
    {
        $usuario->delete();
    }

    /**
     * @inheritDoc
     */
    public function findByUsername(string $username): ?Usuario
    {
        return Usuario::where('usuario', $username)->first();
    }

    /**
     * @inheritDoc
     */
    public function findWithRole(int $id): ?Usuario
    {
        return Usuario::with('role')->find($id);
    }

    /**
     * @inheritDoc
     */
    public function getActivos(): Collection
    {
        return Usuario::where('activo', true)->get();
    }
}
