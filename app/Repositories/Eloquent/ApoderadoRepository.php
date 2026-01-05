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

    public function findByDocumento(string|\App\Enums\TipoDocumento $tipoDocumento, string $nroDocumento): ?Apoderado
    {
        // Convertir enum a string si es necesario
        $tipoDocumentoValue = $tipoDocumento instanceof \App\Enums\TipoDocumento 
            ? $tipoDocumento->value 
            : $tipoDocumento;
            
        return Apoderado::where('tipo_documento', $tipoDocumentoValue)
            ->where('nro_documento', $nroDocumento)
            ->first();
    }

    public function findWithEstudiantes(int $id): ?Apoderado
    {
        return Apoderado::with('estudiantes')->find($id);
    }
}
