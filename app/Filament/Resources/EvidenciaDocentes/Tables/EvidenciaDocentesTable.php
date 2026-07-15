<?php

namespace App\Filament\Resources\EvidenciaDocentes\Tables;

use App\Models\EvidenciaDocente;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class EvidenciaDocentesTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        $esAdministrativo = $user?->esAdmin() || $user?->esDirectora();

        return $table
            ->columns([
                // 1. Docente: Solo se muestra a administrativos (para el docente es redundante)
                TextColumn::make('docente.nombre_completo')
                    ->label('Docente')
                    ->searchable()
                    ->sortable()
                    ->visible($esAdministrativo),

                // 2. Horario / Aula del CETPRO
                TextColumn::make('horario.programa.nombre_programa')
                    ->label('Programa / Módulo')
                    ->searchable()
                    ->sortable(),

                // 3. Tipo de Documento mapeado a nombres amigables
                TextColumn::make('tipo_documento')
                    ->label('Documento')
                    // ->formatStateUsing(fn (string $state): string => match ($state) {
                    //     'ACTA_EVALUACION' => 'Acta de Evaluación',
                    //     'NOMINA_MATRICULA' => 'Nómina de Matrícula',
                    //     'PORTAFOLIO_DOCENTE' => 'Portafolio de Evidencias',
                    //     'INFORME_FINAL' => 'Informe Final',
                    //     default => $state,
                    // })
                    ->badge()
                    // ->color('gray')
                    ->sortable(),

                // 4. Estado de Revisión con colores condicionales (Badges)
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aprobado' => 'success',   // Verde
                        'Observado' => 'danger',   // Rojo
                        'Pendiente' => 'warning',  // Amarillo/Naranja
                        default => 'gray',
                    })
                    ->sortable(),

                // 5. Fecha de subida del archivo
                TextColumn::make('created_at')
                    ->label('Fecha de Subida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por Estado de Revisión
                SelectFilter::make('estado')
                    ->label('Filtrar por Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Aprobado' => 'Aprobado',
                        'Observado' => 'Observado',
                    ])
                    ->placeholder('Todos los estados'),

                // Filtro por Tipo de Documento
                SelectFilter::make('tipo_documento')
                    ->label('Filtrar por Documento')
                    ->options([
                        'ACTA_EVALUACION' => 'Actas de Evaluación',
                        'NOMINA_MATRICULA' => 'Nóminas de Matrícula',
                        'PORTAFOLIO_DOCENTE' => 'Portafolios Docentes',
                        'INFORME_FINAL' => 'Informes Finales',
                    ])
                    ->placeholder('Todos los documentos'),

                // Filtro por Docente (Exclusivo para Directora y Admin)
                SelectFilter::make('docente_id')
                    ->label('Filtrar por Docente')
                    ->relationship('docente', 'nombres')
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos los docentes')
                    ->visible($esAdministrativo),
            ])
            ->recordActions([
                // 👉 1. Acción para descargar directamente el archivo
                Action::make('descargar')
                    ->label('')
                    ->tooltip('Descargar archivo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (EvidenciaDocente $record) => Storage::url($record->archivo_path))
                    ->openUrlInNewTab(),

                // 👉 2. Acción rápida de revisión (Exclusiva para Directora y Admin)
                //      Les permite Aprobar u Observar directamente desde la fila
                Action::make('revisar')
                    ->label('')
                    ->tooltip('Revisar y Calificar Documento')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible($esAdministrativo)
                    ->form([
                        Select::make('estado')
                            ->label('Decisión de Revisión')
                            ->options([
                                'Aprobado' => 'Aprobado (Conforme)',
                                'Observado' => 'Observado (Requiere corregir)',
                            ])
                            ->required(),
                        Textarea::make('observaciones')
                            ->label('Observaciones / Retroalimentación')
                            ->placeholder('Escriba detalladamente si el archivo tiene observaciones...')
                            ->rows(3),
                    ])
                    ->action(function (EvidenciaDocente $record, array $data) {
                        $record->update([
                            'estado' => $data['estado'],
                            'observaciones' => $data['observaciones'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Evaluación registrada')
                            ->body("El documento de " . $record->docente?->nombre_completo . " ha sido marcado como " . $data['estado'] . ".")
                            ->success()
                            ->send();
                    }),

                // El docente puede editar su registro si está observado o pendiente
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar registro')
                    ->visible(fn (EvidenciaDocente $record) => 
                        !$esAdministrativo && $record->estado !== 'Aprobado'
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($user?->esAdmin()), // Solo el Administrador técnico puede borrar
                ]),
            ]);
    }
}