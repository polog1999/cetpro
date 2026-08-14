<?php

namespace App\Filament\Resources\Notas\Pages;

use App\Filament\Resources\Notas\NotaResource;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Programa;
use App\Models\UnidadDidacticaUgel;
use App\Enums\EstadoMatricula;
use App\Enums\TipoPrograma;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use \Illuminate\Support\Str;

class ListNotas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = NotaResource::class;
    protected string $view = 'filament.resources.notas.pages.list-notas';

    public ?string $tipo_programa = null;
    public ?int $programa_id = null;
    public ?int $curso_id = null;
    public ?int $unidad_id = null;
    public ?int $horario_id = null;

    public array $notas = [];
    public bool $showConfirmModal = false;

    public function esProgramaEstudio(): bool
    {
        if (!$this->tipo_programa) {
            return false;
        }

        $val = is_object($this->tipo_programa) && method_exists($this->tipo_programa, 'value')
            ? $this->tipo_programa->value
            : (string) $this->tipo_programa;

        return $val === 'PROGRAMA_ESTUDIO' || (defined('\App\Enums\TipoPrograma::PROGRAMA_ESTUDIO') && $val === TipoPrograma::PROGRAMA_ESTUDIO->value);
    }

    public function puedeEditarTodo(): bool
    {
        $user = auth()->user();
        return $user && ($user->esAdmin() || $user->esDirectora());
    }

    public function puedeGuardarNotas(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->esDirectora()) return false;
        return $user->esAdmin() || $user->esProfesor();
    }

    public function confirmarGuardar(): void
    {
        $this->showConfirmModal = true;
    }
    public function cancelarConfirmacion(): void
    {
        $this->showConfirmModal = false;
    }

    public function getTiposProgramaProperty(): array
    {
        return TipoPrograma::cases();
    }

    public function getProgramasProperty(): Collection
    {
        if (!$this->tipo_programa) return collect();

        $query = Programa::query()
        ->where('activo', true);
        if (class_exists(TipoPrograma::class)) {
            $enumValue = $this->tipo_programa;
            $query->where('tipo_programa', $enumValue);
        } else {
            $query->where('tipo_programa', $this->tipo_programa);
        }

        $user = auth()->user();
        if ($this->puedeEditarTodo()) {
            return $query->orderBy('nombre_programa')->pluck('nombre_programa', 'id_programa');
        }

        if ($user?->docente_id) {
            return $query->whereHas('horarios', function ($q) use ($user) {
                $q->where('id_docente', $user->docente_id)->where('activo', true);
            })->orderBy('nombre_programa')->pluck('nombre_programa', 'id_programa');
        }

        return collect();
    }

    public function getCursosProperty(): Collection
    {
        if (!$this->programa_id) return collect();

        return Curso::where('id_programa', $this->programa_id)
            ->orderBy('fecha_inicio', 'asc')
            ->pluck('nombre_curso', 'id_curso');
    }

    /**
     * UNIDADES DIDÁCTICAS DESDE LA NUEVA TABLA unidades_didacticas_ugel
     */
    public function getUnidadesProperty(): Collection
    {
        if (!$this->programa_id) return collect();

        $query = UnidadDidacticaUgel::where('programa_id', $this->programa_id);

        // Si es Programa de Estudio, filtramos estrictamente por el módulo (curso_id) seleccionado
        if ($this->esProgramaEstudio()) {
            if (!$this->curso_id) return collect();
            $query->where('curso_id', $this->curso_id);
        }

        return $query->orderBy('orden', 'asc')->pluck('nombre', 'id');
    }

    public function getHorariosProperty(): Collection
    {
        if (!$this->programa_id) return collect();

        $user = auth()->user();
        if (!$user) return collect();

        $query = Horario::where('id_programa', $this->programa_id)->where('activo', true);

        if ($user->esAdmin() || $user->esDirectora()) {
            $horarios = $query->get();
        } elseif ($user->docente_id) {
            $horarios = $query->where('id_docente', $user->docente_id)->get();
        } else {
            return collect();
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

    public function getTieneHorariosProperty(): bool
    {
        return $this->horarios->isNotEmpty();
    }

    public function getEstudiantesProperty(): Collection
    {
        if (!$this->horario_id || !$this->unidad_id) {
            return collect();
        }

        $matriculas = Matricula::with(['estudiante'])
            ->where('id', '!=', 42)
            ->where('horario_id', $this->horario_id)
            ->whereIn('estado', [
                \App\Enums\EstadoMatricula::ENPROCESO->value,
                \App\Enums\EstadoMatricula::CULMINADO->value,
            ])
            ->whereHas('cronograma.pagos', function ($q) {
                $q->where('estado', 'Cancelado');
            }) //FILTRA SOLO LAS MATRICULAS QUE PAGARON ALMENOS UNA VEZ
            ->where(function ($query) {
                if ($this->esProgramaEstudio()) {
                    // Programa de estudio: filtra si se matriculó en este módulo específico o en todo el programa
                    $query->where('id_curso', $this->curso_id)
                        ->orWhereNull('id_curso');
                } else {
                    // Formación continua: muestra en todas las unidades si está en el curso o paquete completo
                    $query->whereNotNull('id_curso')
                        ->orWhereNull('id_curso');
                }
            })
            ->get()
            ->unique('estudiante_id')
            ->sortBy('estudiante.apellido_paterno')
            ->values();

        return $matriculas->map(function ($matricula) {
            $queryNota = Nota::where('matricula_id', $matricula->id)
                ->where('unidad_id', $this->unidad_id);

            if ($this->curso_id) {
                $queryNota->where('curso_id', $this->curso_id);
            }

            $notaExistente = $queryNota->first();

            return [
                'matricula_id' => $matricula->id,
                'nombre_completo' => Str::upper("{$matricula->estudiante->apellido_paterno} {$matricula->estudiante->apellido_materno}, ") . trim(Str::title(Str::lower($matricula->estudiante->nombres))),
                'dni' => $matricula->estudiante?->nro_documento ?? 'N/A',
                'nota_actual' => $notaExistente?->nota_numerica,
                'ya_tiene_nota' => $notaExistente !== null,
            ];
        });
    }

    public function updatedTipoPrograma(): void
    {
        $this->programa_id = null;
        $this->curso_id = null;
        $this->unidad_id = null;
        $this->horario_id = null;
        $this->notas = [];
    }

    public function updatedProgramaId(): void
    {
        $this->curso_id = null;
        $this->unidad_id = null;
        $this->horario_id = null;
        $this->notas = [];
    }

    public function updatedCursoId(): void
    {
        $this->unidad_id = null;
        $this->notas = [];
    }

    public function updatedUnidadId(): void
    {
        $this->updatedHorarioId();
    }

    public function updatedHorarioId(): void
    {
        $this->notas = [];
        foreach ($this->estudiantes as $estudiante) {
            $this->notas[$estudiante['matricula_id']] = $estudiante['nota_actual'] !== null
                ? (string) intval($estudiante['nota_actual'])
                : '';
        }
    }

   public function guardarNotas(): void
    {
        $user = auth()->user();
        if (!$this->puedeGuardarNotas()) {
            Notification::make()->danger()->title('Acceso Denegado')->body('Sin permisos de escritura.')->send();
            return;
        }

        if (!$this->unidad_id || !$this->horario_id) {
            Notification::make()->danger()->title('Error')->body('Debe seleccionar la unidad didáctica y horario.')->send();
            return;
        }

        $horario = Horario::find($this->horario_id);
        $docenteId = $user->docente_id ?? $horario?->id_docente;

        if (!$docenteId) {
            Notification::make()->danger()->title('Error')->body('El horario no tiene docente asignado.')->send();
            return;
        }

        $guardadas = 0;
        $actualizadas = 0;
        $eliminadas = 0; // 👈 Para llevar conteo de notas borradas
        $errores = 0;
        $matriculasValidas = $this->estudiantes->pluck('matricula_id')->toArray();

        foreach ($this->notas as $matriculaId => $nota) {
            if (!in_array($matriculaId, $matriculasValidas)) continue;

            // Buscamos si ya existe la nota en la BD para esta matrícula y unidad
            $queryExistente = Nota::where('matricula_id', $matriculaId)
                ->where('unidad_id', $this->unidad_id);

            if ($this->curso_id) {
                $queryExistente->where('curso_id', $this->curso_id);
            }

            $existente = $queryExistente->first();

            // CASO A: EL USUARIO BORRÓ LA NOTA (DEJÓ EL INPUT VACÍO)
            if (($nota === '' || $nota === null)) {
                if ($existente) {
                    try {
                        $existente->delete(); // 👈 Si existía en la BD, la eliminamos
                        $eliminadas++;
                    } catch (\Exception $e) {
                        $errores++;
                    }
                }
                continue; // Pasamos al siguiente alumno
            }

            // CASO B: EL USUARIO ESCRIBIÓ UNA NOTA VÁLIDA
            $notaNumerica = (int) $nota;
            if ($notaNumerica < 0 || $notaNumerica > 20) {
                $errores++;
                continue;
            }

            if ($existente) {
                try {
                    $existente->update([
                        'nota_numerica' => $notaNumerica,
                        'docente_id' => $docenteId,
                    ]);
                    $actualizadas++;
                } catch (\Exception $e) {
                    $errores++;
                }
                continue;
            }

            // Crear registro nuevo si no existía
            try {
                Nota::create([
                    'matricula_id' => $matriculaId,
                    'curso_id' => $this->curso_id,
                    'unidad_id' => $this->unidad_id,
                    'nota_numerica' => $notaNumerica,
                    'docente_id' => $docenteId,
                ]);
                $guardadas++;
            } catch (\Exception $e) {
                $errores++;
            }
        }

        // Notificación detallada de lo que ocurrió
        $mensajeBody = "Se procesaron los cambios correctamente.";
        if ($eliminadas > 0) {
            $mensajeBody .= " ({$eliminadas} nota(s) vaciada(s)).";
        }

        Notification::make()->success()->title('Notas procesadas correctamente')->body($mensajeBody)->send();
        $this->showConfirmModal = false;
    }
    public function cancelar(): void
    {
        $this->tipo_programa = null;
        $this->programa_id = null;
        $this->curso_id = null;
        $this->unidad_id = null;
        $this->horario_id = null;
        $this->notas = [];
    }
}
