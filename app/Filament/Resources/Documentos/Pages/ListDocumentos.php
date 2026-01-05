<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use Filament\Resources\Pages\ListRecords;

class ListDocumentos extends ListRecords
{
    protected static string $resource = DocumentoResource::class;

    protected ?string $heading = 'Documentos de Estudiantes';

    protected ?string $subheading = 'Gestiona los documentos de cada estudiante';

    protected function getHeaderActions(): array
    {
        return []; // Sin acción de crear
    }
}
