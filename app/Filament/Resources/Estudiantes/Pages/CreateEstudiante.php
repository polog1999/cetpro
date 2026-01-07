<?php

namespace App\Filament\Resources\Estudiantes\Pages;

use App\Filament\Resources\Estudiantes\EstudianteResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Estudiante;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Services\OracleTusneService;
use Illuminate\Support\Facades\Log;

class CreateEstudiante extends CreateRecord
{
    protected static string $resource = EstudianteResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe un estudiante idéntico
        $exists = Estudiante::where('tipo_documento', $this->data['tipo_documento'])
            ->where('nro_documento', $this->data['nro_documento'])
            ->where('nombres', $this->data['nombres'])
            ->where('apellido_paterno', $this->data['apellido_paterno'])
            ->where('apellido_materno', $this->data['apellido_materno'])
            ->where('genero', $this->data['genero'])
            ->where('estado_civil', $this->data['estado_civil'])
            ->where('fecha_nacimiento', $this->data['fecha_nacimiento'])
            ->where('telefono', $this->data['telefono'])
            ->where('direccion', $this->data['direccion'])
            ->where('email', $this->data['email'])
            ->where('grado_instruccion', $this->data['grado_instruccion'])
            ->where('provincia', $this->data['provincia'])
            ->where('distrito', $this->data['distrito'])
            ->where('apoderado_id', $this->data['apoderado_id'] ?? null)
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Estudiante')
                ->body('No se admiten registros idénticos.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        // Crear contribuyente en Oracle
        try {
            $oracle = app(OracleTusneService::class);
            $codigo = $oracle->crearContribuyente($this->record);
            
            if ($codigo) {
                $this->record->codigo_contribuyente = $codigo;
                $this->record->save();
                
                Notification::make()
                    ->success()
                    ->title('Contribuyente creado')
                    ->body("Código: {$codigo}")
                    ->send();
                    
                Log::info('Contribuyente creado desde Filament', [
                    'estudiante_id' => $this->record->id,
                    'codigo' => $codigo,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al crear contribuyente desde Filament', [
                'estudiante_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->warning()
                ->title('Estudiante creado sin código de contribuyente')
                ->body('No se pudo conectar con Oracle')
                ->send();
        }
    }
}

