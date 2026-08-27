<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Matricula;
use BackedEnum;
use UnitEnum;

class GenerarCenso extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';
    protected static string|UnitEnum|null $navigationGroup = 'Reportes y Documentos';
    
    protected static ?string $title = 'Censo Estadístico (Excel)';
    protected string $view = 'filament.pages.generar-censo';

    public ?string $anio = null;

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function getAniosProperty(): array
    {
        $aniosBD = Matricula::selectRaw('SUBSTRING(codigo_inscripcion, 1, 4) as anio')
            ->whereNotNull('codigo_inscripcion')->distinct()->orderBy('anio', 'desc')->pluck('anio', 'anio')->toArray();
        
        $anioActual = date('Y');
        if (!isset($aniosBD[$anioActual])) $aniosBD[$anioActual] = $anioActual;

        return $aniosBD;
    }
}