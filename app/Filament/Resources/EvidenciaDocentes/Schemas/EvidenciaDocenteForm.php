<?php

namespace App\Filament\Resources\EvidenciaDocentes\Schemas;

use App\Enums\TipoEvidencia;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EvidenciaDocenteForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $esAdministrativo = $user->esAdmin() || $user->esDirectora();
        return $schema
            ->components([
                // Docente: Auto-asignado si es docente, seleccionable si es administrativo
                Select::make('docente_id')
                    ->relationship('docente', 'nombres')
                    ->default($user->docente_id)
                    ->disabled(!$esAdministrativo)
                    ->dehydrated(true)
                    ->required(),

                // Horarios del docente
                Select::make('horario_id')
                    ->label('Horario / Aula')
                    ->options(function () use ($user, $esAdministrativo) {
                        $query = \App\Models\Horario::where('activo', true);

                        // Si es docente, solo mostrar sus horarios asignados
                        if (!$esAdministrativo && $user->docente_id) {
                            $query->where('id_docente', $user->docente_id);
                        }

                        return $query->get()->mapWithKeys(function ($horario) {
                            $programa = $horario->programa?->nombre_programa ?? 'Sin programa';
                            $turno = $horario->turno?->value ?? 'Sin turno';
                            return [$horario->id_horario => "{$programa} - {$turno}"];
                        });
                    })
                    ->required(),

                Select::make('tipo_documento')
                    ->label('Tipo de Documento')
                    ->options(fn() => TipoEvidencia::class)
                    ->required(),

                FileUpload::make('archivo_path')
                    ->label('Subir Archivo')
                    ->disk('public') // <--- Esto le indica a Filament que use el disco público
                    ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-zip-compressed'])
                    ->directory('evidencias-cetpro')
                    ->maxSize(20480) // Limite de 20MB para portafolios grandes
                    ->required()
                    ->downloadable()
                    ->openable(),

                // Sección exclusiva de revisión para la Directora y el Admin
                Textarea::make('observaciones')
                    ->label('Observaciones de Revisión')
                    ->placeholder('Escriba aquí los motivos si el documento es observado...')
                    ->visible($esAdministrativo),

                Select::make('estado')
                    ->label('Estado de Revisión')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Aprobado' => 'Aprobado',
                        'Observado' => 'Observado',
                    ])
                    ->default('Pendiente')
                    ->disabled(!$esAdministrativo)
                    ->visible($esAdministrativo),
            ]);
    }
}
