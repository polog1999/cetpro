<?php

namespace App\Filament\Resources\Notas\Pages;

use App\Filament\Resources\Notas\NotaResource;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Programa;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ListNotas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = NotaResource::class;
    protected string $view = 'filament.resources.notas.pages.list-notas';

    // Propiedades para los selectores
    public ?int $programa_id = null;
    public ?int $curso_id = null;
    public ?int $horario_id = null;
    
    // Array de notas
    public array $notas = [];
    
    // Modal de confirmación
    public bool $showConfirmModal = false;
    
    /**
     * Mostrar modal de confirmación
     */
    public function confirmarGuardar(): void
    {
        $this->showConfirmModal = true;
    }
    
    /**
     * Cancelar modal de confirmación
     */
    public function cancelarConfirmacion(): void
    {
        $this->showConfirmModal = false;
    }

    /**
     * Obtener programas para el select
     */
    public function getProgramasProperty(): Collection
    {
        $user = Filament::auth()->user();

        // Si el usuario es docente, filtrar programas donde tenga horarios asignados
        if ($user?->docente_id) {
            return Programa::whereHas('horarios', function ($query) use ($user) {
                $query->where('id_docente', $user->docente_id)
                      ->where('activo', true);
            })
            ->orderBy('nombre_programa')
            ->pluck('nombre_programa', 'id_programa');
        }

        // Si es admin u otro rol sin docente asociado, mostrar todos
        return Programa::orderBy('nombre_programa')->pluck('nombre_programa', 'id_programa');
    }

    /**
     * Obtener cursos del programa seleccionado
     */
    public function getCursosProperty(): Collection
    {
        if (!$this->programa_id) {
            return collect();
        }

        // Mostrar todos los cursos del programa
        // El filtrado de estudiantes se hace en getEstudiantesProperty por id_curso
        return Curso::where('id_programa', $this->programa_id)
            ->orderBy('nombre_curso')
            ->pluck('nombre_curso', 'id_curso');
    }

    /**
     * Obtener horarios del profesor para el programa
     */
    public function getHorariosProperty(): Collection
    {
        $user = Filament::auth()->user();
        
        if (!$this->programa_id || !$user?->docente_id) {
            return collect();
        }

        $horarios = Horario::where('id_programa', $this->programa_id)
            ->where('id_docente', $user->docente_id)
            ->where('activo', true)
            ->get();

        return $horarios->mapWithKeys(function ($horario) {
            $turno = $horario->turno?->value ?? 'Sin turno';
            $dias = is_array($horario->dias) ? implode(', ', $horario->dias) : ($horario->dias ?? '');
            $horaInicio = $horario->hora_inicio?->format('H:i') ?? '';
            $horaFin = $horario->hora_fin?->format('H:i') ?? '';
            
            return [$horario->id_horario => "{$turno} - {$dias} ({$horaInicio} - {$horaFin})"];
        });
    }

    /**
     * Verificar si hay horarios asignados
     */
    public function getTieneHorariosProperty(): bool
    {
        return $this->horarios->isNotEmpty();
    }

    /**
     * Verificar si todos los estudiantes ya tienen notas
     */
    public function getTodosConNotaProperty(): bool
    {
        if ($this->estudiantes->isEmpty()) {
            return true; // Si no hay estudiantes, considerar como "todos tienen nota"
        }
        
        // Verificar si todos ya tienen nota
        foreach ($this->estudiantes as $estudiante) {
            if (!$estudiante['ya_tiene_nota']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtener estudiantes matriculados en el curso específico
     */
    public function getEstudiantesProperty(): Collection
    {
        if (!$this->horario_id || !$this->curso_id) {
            return collect();
        }

        // Filtrar por horario_id Y por id_curso (curso específico)
        return Matricula::with(['estudiante'])
            ->where('horario_id', $this->horario_id)
            // Permitir matrículas por curso específico O por programa completo (id_curso null)
            ->where(function ($query) {
                $query->where('id_curso', $this->curso_id)
                      ->orWhereNull('id_curso');
            })
            ->whereIn('estado', [
                \App\Enums\EstadoMatricula::ENPROCESO->value,
                \App\Enums\EstadoMatricula::CULMINADO->value,
            ])
            ->get()
            ->map(function ($matricula) {
                $notaExistente = Nota::where('matricula_id', $matricula->id)
                    ->where('curso_id', $this->curso_id)
                    ->first();

                return [
                    'matricula_id' => $matricula->id,
                    'nombre_completo' => $matricula->estudiante?->nombre_completo ?? 'N/A',
                    'dni' => $matricula->estudiante?->nro_documento ?? 'N/A',
                    'nota_actual' => $notaExistente?->nota_numerica,
                    'ya_tiene_nota' => $notaExistente !== null,
                ];
            });
    }

    /**
     * Cuando cambia el programa
     */
    public function updatedProgramaId(): void
    {
        $this->curso_id = null;
        $this->horario_id = null;
        $this->notas = [];
    }

    /**
     * Cuando cambia el curso
     */
    public function updatedCursoId(): void
    {
        $this->horario_id = null;
        $this->notas = [];
    }

    /**
     * Cuando cambia el horario - cargar notas existentes
     */
    public function updatedHorarioId(): void
    {
        $this->notas = [];
        
        foreach ($this->estudiantes as $estudiante) {
            $this->notas[$estudiante['matricula_id']] = $estudiante['nota_actual'] !== null 
                ? (string) intval($estudiante['nota_actual']) 
                : '';
        }
    }

    /**
     * Guardar las notas
     */
    public function guardarNotas(): void
    {
        $user = Filament::auth()->user();
        
        if (!$this->curso_id || !$this->horario_id || !$user?->docente_id) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Debe seleccionar programa, curso y horario.')
                ->send();
            return;
        }

        $guardadas = 0;
        $omitidas = 0;
        $errores = 0;

        foreach ($this->notas as $matriculaId => $nota) {
            if ($nota === '' || $nota === null) {
                continue;
            }

            $notaNumerica = (int) $nota;
            if ($notaNumerica < 0 || $notaNumerica > 20) {
                $errores++;
                continue;
            }

            // No sobrescribir notas existentes
            $existente = Nota::where('matricula_id', $matriculaId)
                ->where('curso_id', $this->curso_id)
                ->first();
            
            if ($existente) {
                $omitidas++;
                continue;
            }

            try {
                Nota::create([
                    'matricula_id' => $matriculaId,
                    'curso_id' => $this->curso_id,
                    'nota_numerica' => $notaNumerica,
                    'docente_id' => $user->docente_id,
                ]);
                $guardadas++;
            } catch (\Exception $e) {
                $errores++;
            }
        }

        if ($guardadas > 0) {
            Notification::make()
                ->success()
                ->title('Notas guardadas')
                ->body("Se guardaron {$guardadas} nota(s) correctamente.")
                ->send();
        }

        if ($omitidas > 0) {
            Notification::make()
                ->info()
                ->title('Notas omitidas')
                ->body("{$omitidas} nota(s) ya existían y no fueron modificadas.")
                ->send();
        }

        if ($errores > 0) {
            Notification::make()
                ->danger()
                ->title('Errores')
                ->body("Hubo {$errores} error(es).")
                ->send();
        }

        // Cerrar modal y recargar
        $this->showConfirmModal = false;
        $this->updatedHorarioId();
    }

    /**
     * Cancelar y limpiar selección
     */
    public function cancelar(): void
    {
        $this->programa_id = null;
        $this->curso_id = null;
        $this->horario_id = null;
        $this->notas = [];
    }
}
