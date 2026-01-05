<?php

namespace App\Repositories;

use App\Models\Apoderado;
use Illuminate\Database\Eloquent\Collection;

interface ApoderadoRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Apoderado;
    public function create(array $data): Apoderado;
    public function update(Apoderado $apoderado, array $data): Apoderado;
    public function delete(Apoderado $apoderado): void;
    
    public function findByDocumento(string|\App\Enums\TipoDocumento $tipoDocumento, string $nroDocumento): ?Apoderado;
    public function findWithEstudiantes(int $id): ?Apoderado;
}
