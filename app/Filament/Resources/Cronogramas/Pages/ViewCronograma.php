<?php

namespace App\Filament\Resources\Cronogramas\Pages;

use App\Filament\Resources\Cronogramas\CronogramaResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Cronogramas\RelationManagers\PagosRelationManager;

use Illuminate\Contracts\Support\Htmlable;

class ViewCronograma extends ViewRecord
{
    protected static string $resource = CronogramaResource::class;

    // Tal como en tu ejemplo, definimos qué relation managers mostrar aquí
    public function getRelationManagers(): array
    {
        return [
            PagosRelationManager::class,
        ];
    }

    // Título grande de la página (Como tu "Agregar cursos")
    public function getHeading(): string|Htmlable
    {
        return 'Gestión de Pagos y Sección';
    }
}