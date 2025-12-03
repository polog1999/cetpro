<?php

namespace App\Filament\Resources\Programas\Pages;

use App\Filament\Resources\Programas\ProgramaResource;
use App\Filament\Resources\Programas\RelationManagers\CursosRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;



class ViewPrograma extends ViewRecord
{
    protected static string $resource = ProgramaResource::class;

    // Solo aquí mostramos el relation manager de cursos
    protected function getAllRelationManagers(): array
    {
        return [
            CursosRelationManager::class,
        ];
    }

    // Título grande de la página
    public function getHeading(): string|Htmlable
    {
        return 'Agregar cursos';
    }

    

}
