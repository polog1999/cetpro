<?php

namespace App\Filament\Resources\Programas\Pages;

use App\Filament\Resources\Programas\ProgramaResource;
use App\Filament\Resources\Programas\RelationManagers\CursosRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Enums\TipoPrograma;

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

    // Título grande de la página - dinámico según tipo de programa
    public function getHeading(): string|Htmlable
    {
        $record = $this->getRecord();
        $isPrograma = $record->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO;
        
        return $isPrograma ? 'Ver Módulos' : 'Ver Cursos';
    }
}

