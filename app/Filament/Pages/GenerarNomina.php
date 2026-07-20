<?php

namespace App\Filament\Pages;

use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Programa;
use App\Enums\TipoPrograma;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class GenerarNomina extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|UnitEnum|null $navigationGroup = 'Reportes y Documentos';
    protected static ?string $title = 'Generar Nómina de Matrícula';
    protected string $view = 'filament.pages.generar-nomina';

    public ?string $anio = null;
    public ?string $tipo_programa = null;
    public ?int $programa_id = null;
    public ?int $curso_id = null;
    public ?int $horario_id = null;

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function getAniosProperty(): array
    {
        $aniosBD = Matricula::selectRaw('SUBSTRING(codigo_inscripcion, 1, 4) as anio')
            ->whereNotNull('codigo_inscripcion')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio', 'anio')
            ->toArray();

        $anioActual = date('Y');
        if (!isset($aniosBD[$anioActual])) {
            $aniosBD[$anioActual] = $anioActual;
        }

        return $aniosBD;
    }

    public function getTiposProgramaProperty(): array
    {
        return TipoPrograma::cases();
    }

    public function getProgramasProperty()
    {
        if (!$this->tipo_programa) return [];

        $enumValue = class_exists(TipoPrograma::class) ? $this->tipo_programa : $this->tipo_programa;

        return Programa::where('tipo_programa', $enumValue)
            ->orderBy('nombre_programa')
            ->pluck('nombre_programa', 'id_programa');
    }

    public function getCursosProperty()
    {
        if (!$this->programa_id) return [];
        return \App\Models\Curso::where('id_programa', $this->programa_id)
            ->orderBy('fecha_inicio', 'asc')
            ->pluck('nombre_curso', 'id_curso');
    }

    public function getHorariosProperty()
    {
        if (!$this->programa_id || !$this->anio) return [];

        $horarios = Horario::where('id_programa', $this->programa_id)
            ->whereHas('matriculas', function ($query) {
                $query->where('codigo_inscripcion', 'like', $this->anio . '%');
            })
            ->get();

        return $horarios->mapWithKeys(function ($horario) {
            $turno = $horario->turno?->value ?? 'Sin turno';
            $docente = $horario->docente ? $horario->docente->nombre_completo : 'Sin docente';
            return [$horario->id_horario => "{$turno} - [Prof. {$docente}]"];
        });
    }

    // Resets en cascada para evitar datos viejos
    public function updatedAnio()
    {
        $this->horario_id = null;
    }
    public function updatedTipoPrograma()
    {
        $this->programa_id = null;
        $this->curso_id = null;
        $this->horario_id = null;
    }
    public function updatedProgramaId()
    {
        $this->curso_id = null;
        $this->horario_id = null;
    }
    public function updatedCursoId()
    {
        $this->horario_id = null;
    }
    
}