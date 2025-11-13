<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Resources\Pages\CreateRecord;

use App\Models\Pago;
use Carbon\CarbonPeriod;

class CreateMatricula extends CreateRecord
{
    protected static string $resource = MatriculaResource::class;

    
}
