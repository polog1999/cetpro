<?php

namespace App\Filament\Resources\Notas\Pages;

use App\Filament\Resources\Notas\NotaResource;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Programa;
use App\Models\Unidad; // 👈 Importado
use App\Enums\TipoPrograma;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ListNotas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = NotaResource::class;
    protected string $view = 'filament.resources.notas.pages.list-notas';

    // Propiedades para los selectores
    public ?string $tipo_programa = null;
    public ?int $programa_id = null;
    public ?int $curso_id = null;
    public ?int $unidad_id = null; // 👈 Añadido
    public ?int $horario_id = null;

    // Array de notas
    public array $notas = [];

    // Modal de confirmación
    public bool $showConfirmModal = false;

    /**
     * Ayudante seguro para verificar si se seleccionó Programa de Estudio
     */
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

    /**
     * Verifica si el usuario tiene rol de Administrador o Directora.
     */
    public function puedeEditarTodo(): bool
    {
        $user = auth()->user();
        return $user && ($user->esAdmin() || $user->esDirectora());
    }

    /**
     * Control estricto de permisos de escritura.
     * - Admin y Docentes: Pueden registrar y editar.
     * - Directora: Solo Lectura (Falso).
     */
    public function puedeGuardarNotas(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->esDirectora()) {
            return false; // Directora solo puede visualizar
        }

        // Admin y Docentes sí pueden registrar y editar
        return $user->esAdmin() || $user->esProfesor();
    }

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
     * Obtener tipos de programa para el primer selector
     */
    public function getTiposProgramaProperty(): array
    {
        return TipoPrograma::cases();
    }

    /**
     * Obtener programas correspondientes al tipo seleccionado y filtrados por Rol
     */
    public function getProgramasProperty(): Collection
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

    /**
     * Obtener cursos del programa seleccionado
     */
    public function getCursosProperty(): Collection
    {
        if (!$this->programa_id) {
            return collect();
        }

        return Curso::where('id_programa', $this->programa_id)
            ->orderBy('fecha_inicio', 'asc')
            ->pluck('nombre_curso', 'id_curso');
    }

    /**
     * NUEVO: Obtener unidades didácticas si es Programa de Estudio
     */
    public function getUnidadesProperty(): Collection
    {
        if (!$this->curso_id || !$this->esProgramaEstudio()) {
            return collect();
        }

        $query = Unidad::where('id_curso', $this->curso_id);

        if (method_exists(Unidad::class, 'scopeOrdenado')) {
            $query->ordenado();
        } else {
            $query->orderBy('id_unidad', 'asc');
        }

        return $query->pluck('nombre_unidad', 'id_unidad');
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

    /**
     * Verificar si hay horarios asignados
     */
    public function getTieneHorariosProperty(): bool
    {
        return $this->horarios->isNotEmpty();
    }

    /**
     * Obtener estudiantes matriculados
     */
    /**
     * Obtener estudiantes cruzando curso Y unidad de forma estricta
     */
    public function getEstudiantesProperty(): Collection
    {
        if (!$this->horario_id || !$this->curso_id) {
            return collect();
        }

        // Si es Programa de Estudio, exigimos que se seleccione una unidad antes de cargar alumnos
        if ($this->esProgramaEstudio() && !$this->unidad_id) {
            return collect();
        }

        $matriculas = Matricula::with(['estudiante'])
            ->where('horario_id', $this->horario_id)
            ->whereIn('estado', [
                \App\Enums\EstadoMatricula::ENPROCESO->value,
                \App\Enums\EstadoMatricula::CULMINADO->value,
            ])
            ->where(function ($query) {
                if ($this->esProgramaEstudio()) {
                    // LÓGICA PARA PROGRAMA DE ESTUDIO
                    
                    // 1. Alumno matriculado específicamente en la Unidad seleccionada
                    $query->where(function ($q) {
                        $q->where('id_curso', $this->curso_id)
                          ->where('id_unidad', $this->unidad_id);
                    })
                    // 2. O alumno matriculado en TODO el Módulo (unidad es null)
                    ->orWhere(function ($q) {
                        $q->where('id_curso', $this->curso_id)
                          ->whereNull('id_unidad');
                    })
                    // 3. O alumno matriculado en TODO el Programa (curso y unidad null)
                    ->orWhere(function ($q) {
                        $q->whereNull('id_curso')
                          ->whereNull('id_unidad');
                    });
                    
                } else {
                    // LÓGICA PARA FORMACIÓN CONTINUA
                    $query->where('id_curso', $this->curso_id)
                          ->orWhereNull('id_curso');
                }
            })
            ->get();

        // NUEVO: Agrupamos por ID de estudiante para asegurar que no se dupliquen
        // en la lista si tuvieran múltiples matrículas activas por algún error administrativo
        $matriculasUnicas = $matriculas->unique('estudiante_id');

        // Ordenamos la colección en memoria por apellido paterno
        $matriculasOrdenadas = $matriculasUnicas->sortBy('estudiante.apellido_paterno');

        // Mapeamos el resultado final
        return $matriculasOrdenadas->map(function ($matricula) {
            $queryNota = Nota::where('matricula_id', $matricula->id)
                ->where('curso_id', $this->curso_id);

            // Condicional: Si es programa filtra por unidad_id, si es formación continua por nulo
            if ($this->esProgramaEstudio()) {
                $queryNota->where('unidad_id', $this->unidad_id);
            } else {
                $queryNota->whereNull('unidad_id');
            }

            $notaExistente = $queryNota->first();

            return [
                'matricula_id' => $matricula->id,
                'nombre_completo' => $matricula->estudiante?->nombre_completo ?? 'N/A',
                'dni' => $matricula->estudiante?->nro_documento ?? 'N/A',
                'nota_actual' => $notaExistente?->nota_numerica,
                'ya_tiene_nota' => $notaExistente !== null,
            ];
        })->values(); // Resetea las llaves para evitar desorden de índices
    }

    /**
     * Resets en cascada ante cambios de selectores
     */
    public function updatedTipoPrograma(): void
    {
        $this->programa_id = null;
        $this->curso_id = null;
        $this->unidad_id = null; // 👈 Añadido reset
        $this->horario_id = null;
        $this->notas = [];
    }

    public function updatedProgramaId(): void
    {
        $this->curso_id = null;
        $this->unidad_id = null; // 👈 Añadido reset
        $this->horario_id = null;
        $this->notas = [];
    }

    public function updatedCursoId(): void
    {
        $this->unidad_id = null; // 👈 Añadido reset
        $this->horario_id = null;
        $this->notas = [];
    }

    public function updatedUnidadId(): void
    {
        $this->updatedHorarioId(); // Al cambiar unidad, cargar notas de esa unidad
    }

    /**
     * Cargar notas de los estudiantes en el array del formulario
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
     * Guardar y actualizar las notas de forma segura
     */
    public function guardarNotas(): void
    {
        $user = auth()->user();

        // 1. Validar permisos de escritura en servidor
        if (!$this->puedeGuardarNotas()) {
            Notification::make()
                ->danger()
                ->title('Acceso Denegado')
                ->body('Su usuario no tiene permisos para registrar o editar notas.')
                ->send();
            return;
        }

        if (!$this->curso_id || !$this->horario_id) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Debe seleccionar programa, curso y horario.')
                ->send();
            return;
        }

        // Obtener el ID del docente del horario seleccionado
        $horario = Horario::find($this->horario_id);
        $docenteId = $horario?->id_docente;

        // Si es un docente ordinario, validar que el horario seleccionado realmente le pertenece
        if (!$user->esAdmin() && !$user->esDirectora()) {
            if ($docenteId !== $user->docente_id) {
                Notification::make()
                    ->danger()
                    ->title('Acceso Denegado')
                    ->body('No tiene autorización para modificar notas en un horario ajeno.')
                    ->send();
                return;
            }
        }

        if (!$docenteId) {
            Notification::make()
                ->danger()
                ->title('Error de asignación')
                ->body('El horario seleccionado no tiene un docente asignado.')
                ->send();
            return;
        }

        $guardadas = 0;
        $actualizadas = 0;
        $errores = 0;

        // 👉 NUEVO: Obtenemos estrictamente los IDs de matrícula que pertenecen al horario actual
        $matriculasValidas = $this->estudiantes->pluck('matricula_id')->toArray();

        foreach ($this->notas as $matriculaId => $nota) {

            // 👉 NUEVO: Si por algún motivo quedó un ID de un horario viejo en memoria, lo saltamos
            if (!in_array($matriculaId, $matriculasValidas)) {
                continue;
            }

            if ($nota === '' || $nota === null) {
                continue;
            }

            $notaNumerica = (int) $nota;
            if ($notaNumerica < 0 || $notaNumerica > 20) {
                $errores++;
                continue;
            }

            // Filtrado condicional para buscar la nota existente
            $queryExistente = Nota::where('matricula_id', $matriculaId)
                ->where('curso_id', $this->curso_id);

            if ($this->esProgramaEstudio()) {
                $queryExistente->where('unidad_id', $this->unidad_id);
            } else {
                $queryExistente->whereNull('unidad_id');
            }

            $existente = $queryExistente->first();

            if ($existente) {
                // Tanto administradores como docentes autorizados pueden editar y sobrescribir las notas
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

            // Crear registro nuevo
            try {
                Nota::create([
                    'matricula_id' => $matriculaId,
                    'curso_id' => $this->curso_id,
                    'unidad_id' => $this->esProgramaEstudio() ? $this->unidad_id : null, // 👈 Condicional según tipo
                    'nota_numerica' => $notaNumerica,
                    'docente_id' => $docenteId,
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
                ->body("Se registraron {$guardadas} notas correctamente.")
                ->send();
        }

        if ($actualizadas > 0) {
            Notification::make()
                ->success()
                ->title('Notas actualizadas')
                ->body("Se modificaron {$actualizadas} notas correctamente.")
                ->send();
        }

        if ($errores > 0) {
            Notification::make()
                ->danger()
                ->title('Errores')
                ->body("No se pudieron guardar {$errores} notas.")
                ->send();
        }

        $this->showConfirmModal = false;
        // $this->updatedHorarioId();
    }

    /**
     * Cancelar y limpiar selección
     */
    public function cancelar(): void
    {
        $this->tipo_programa = null;
        $this->programa_id = null;
        $this->curso_id = null;
        $this->unidad_id = null; // 👈 Añadido
        $this->horario_id = null;
        $this->notas = [];
    }
}
