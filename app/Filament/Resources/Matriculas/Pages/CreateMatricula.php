<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Matricula;
use Filament\Notifications\Notification;

class CreateMatricula extends CreateRecord
{
    protected static string $resource = MatriculaResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si el estudiante ya está matriculado en el mismo horario
        $estudianteId = $this->data['estudiante_id'];
        $horarioId = $this->data['horario_id'] ?? null;

        // Si es un curso libre (sin horario), verificar por curso
        if (!$horarioId && isset($this->data['id_curso'])) {
            $cursoId = $this->data['id_curso'];
            
            $exists = Matricula::where('estudiante_id', $estudianteId)
                ->where('id_curso', $cursoId)
                ->whereNull('horario_id')
                ->exists();

            if ($exists) {
                Notification::make()
                    ->danger()
                    ->title('Error al crear nueva Matrícula')
                    ->body('El estudiante ya está matriculado en este curso.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
        // Si tiene horario (programa de estudio o formación continua)
        elseif ($horarioId) {
            $exists = Matricula::where('estudiante_id', $estudianteId)
                ->where('horario_id', $horarioId)
                ->exists();

            if ($exists) {
                Notification::make()
                    ->danger()
                    ->title('Error al crear nueva Matrícula')
                    ->body('El estudiante ya está matriculado en este horario.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }
}

