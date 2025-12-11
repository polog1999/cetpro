<?php

namespace App\Repositories\Eloquent;

use App\Models\Apoderado;
use App\Repositories\ApoderadoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ApoderadoRepository implements ApoderadoRepositoryInterface
{
    public function all(): Collection
    {
        return Apoderado::all();
    }

    public function find(int $id): ?Apoderado
    {
        return Apoderado::find($id);
    }

    public function create(array $data): Apoderado
    {
        return Apoderado::create($data);
    }

    public function update(Apoderado $apoderado, array $data): Apoderado
    {
        $apoderado->update($data);
        return $apoderado->fresh();
    }

    public function delete(Apoderado $apoderado): void
    {
        $apoderado->delete();
    }

    public function findByDocumento(string $tipoDocumento, string $nroDocumento): ?Apoderado
    {
        return Apoderado::where('tipo_documento', $tipoDocumento)
            ->where('nro_documento', $nroDocumento)
            ->first();
    }

    public function findWithEstudiantes(int $id): ?Apoderado
    {
        return Apoderado::with('estudiantes')->find($id);
    }
}
