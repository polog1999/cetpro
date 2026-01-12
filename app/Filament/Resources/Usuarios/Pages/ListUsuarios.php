<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListUsuarios extends ListRecords
{
    protected static string $resource = UsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ver_alumnos')
                ->label('Ver Usuarios Alumnos')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->url(UsuarioResource::getUrl('alumnos')),
            CreateAction::make(),
        ];
    }
}
