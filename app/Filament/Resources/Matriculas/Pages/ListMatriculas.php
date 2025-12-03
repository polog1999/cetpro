<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMatriculas extends ListRecords
{
    protected static string $resource = MatriculaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matricula_masiva')
                ->label('Matrícula masiva')
                ->icon('heroicon-o-user-group')
                ->color('warning')
                ->url(MatriculaResource::getUrl('matricula-masiva')),
                
            CreateAction::make(),
        ];
    }
}
