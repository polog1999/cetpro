<?php

namespace App\Filament\Pages;

use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Programa;
use App\Enums\TipoPrograma;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
use Illuminate\Support\Collection;

class GenerarNomina extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|UnitEnum|null $navigationGroup = 'Reportes y Documentos';
    protected static ?string $title = 'Generar Nómina y Acta de Matrícula';
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
/**
     * Verifica si el usuario tiene rol de Administrador o Directora.
     */
    public function puedeEditarTodo(): bool
    {
        $user = auth()->user();
        return $user && ($user->esAdmin() || $user->esDirectora());
    }
    public function getProgramasProperty()
    {
         if (!$this->tipo_programa) {
            return collect();
        }

        $query = Programa::query();

        if (class_exists(TipoPrograma::class)) {
            $enumValue = $this->tipo_programa;
            $query->where('tipo_programa', $enumValue);
        } else {
            $query->where('tipo_programa', $this->tipo_programa);
        }

        $user = auth()->user();

        // Admin y Directora ven todos los programas de ese tipo
        if ($this->puedeEditarTodo()) {
            return $query->orderBy('nombre_programa')->pluck('nombre_programa', 'id_programa');
        }

        // El docente solo ve los programas donde tiene horarios asignados activamente
        if ($user?->docente_id) {
            return $query->whereHas('horarios', function ($q) use ($user) {
                $q->where('id_docente', $user->docente_id)
                    ->where('activo', true);
            })
                ->orderBy('nombre_programa')
                ->pluck('nombre_programa', 'id_programa');
        }

        return collect();
    }

    public function getCursosProperty()
    {
        if (!$this->programa_id) return [];
        return \App\Models\Curso::where('id_programa', $this->programa_id)
            ->orderBy('fecha_inicio', 'asc')
            ->pluck('nombre_curso', 'id_curso');
    }

     /**
     * Obtener horarios filtrados de forma estricta por rol
     */
    public function getHorariosProperty(): Collection
    {
        if (!$this->programa_id) {
            return collect();
        }

        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $query = Horario::where('id_programa', $this->programa_id)
            ->where('activo', true);

        // Filtro estricto por Rol
        if ($user->esAdmin() || $user->esDirectora()) {
            // Admin y Directora ven todos los horarios activos del programa
            $horarios = $query->get();
        } elseif ($user->docente_id) {
            // El docente solo ve sus propios horarios asignados
            $horarios = $query->where('id_docente', $user->docente_id)->get();
        } else {
            return collect(); // Bloqueado si no cumple condiciones
        }

        return $horarios->mapWithKeys(function ($horario) {
            $turno = $horario->turno?->value ?? 'Sin turno';
            $dias = is_array($horario->dias) ? implode(', ', $horario->dias) : ($horario->dias ?? '');
            $horaInicio = $horario->hora_inicio?->format('H:i') ?? '';
            $horaFin = $horario->hora_fin?->format('H:i') ?? '';

            $profesorInfo = ($this->puedeEditarTodo() && $horario->docente)
                ? " [Prof. " . $horario->docente->nombre_completo . "]"
                : "";

            return [$horario->id_horario => "{$turno} - {$dias} ({$horaInicio} - {$horaFin}){$profesorInfo}"];
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