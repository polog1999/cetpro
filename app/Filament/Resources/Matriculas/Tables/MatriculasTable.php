<?php

namespace App\Filament\Resources\Matriculas\Tables;

use App\Models\Matricula;
use App\Models\Programa;
use App\Models\Curso;
use App\Enums\TipoMatricula;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FichaMatriculaExport;
use App\Exports\CursosMatriculaExport;

class MatriculasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo_inscripcion')
                    ->label('Código de matrícula')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    // ->sortable(['estudiante.nombres', 'estudiante.apellido_paterno', 'estudiante.apellido_materno'])
                    ->searchable(['estudiante.nombres', 'estudiante.apellido_paterno', 'estudiante.apellido_materno']),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tipo_matricula')
                    ->label('Tipo de matrícula')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grado_academico')
                    ->label('Grado Académico')
                    ->state(function (Matricula $record): string {
                        return match ($record->tipo_matricula) {
                            TipoMatricula::FORMACION_CONTINUA => 'Certificado de Estudio',
                            TipoMatricula::PROGRAMA => 'Título Auxiliar Técnico',
                            TipoMatricula::CURSO => 'Certificado',
                            TipoMatricula::MODULO => 'Certificado del Módulo',
                            default => 'N/A',
                        };
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('horario.programa.nombre_programa')
                    ->label('Programa')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por Nombre de Estudiante
                Filter::make('estudiante')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('nombre_estudiante')
                            ->label('Buscar estudiante')
                            ->placeholder('Escriba nombre o apellidos...')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['nombre_estudiante'] ?? null,
                            fn (Builder $query, $search): Builder => $query->whereHas('estudiante', function (Builder $query) use ($search) {
                                $query->where(function (Builder $query) use ($search) {
                                    $query->where('nombres', 'ilike', "%{$search}%")
                                        ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                        ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                                });
                            })
                        );
                    }),

                // Filtro por DNI del Estudiante
                Filter::make('dni')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('dni_estudiante')
                            ->label('DNI')
                            ->placeholder('Buscar por DNI...')
                            ->numeric()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['dni_estudiante'] ?? null,
                            fn (Builder $query, $dni): Builder => $query->whereHas('estudiante', function (Builder $query) use ($dni) {
                                $query->where('nro_documento', 'ilike', "%{$dni}%");
                            })
                        );
                    }),

                // Filtro por Código del Programa
                SelectFilter::make('codigo_programa')
                    ->label('Código Programa')
                    ->options(function (): array {
                        return \App\Models\Programa::query()
                            ->orderBy('id_programa')
                            ->get()
                            ->mapWithKeys(function ($programa) {
                                $codigo = str_pad($programa->id_programa, 3, '0', STR_PAD_LEFT);
                                return [$programa->id_programa => "{$codigo} - {$programa->nombre_programa}"];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('Todos los códigos')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('horario', function (Builder $q) use ($data) {
                            $q->where('id_programa', $data['value']);
                        });
                    }),

                // Filtro por Tipo de Matrícula (Programa, Formación Continua, Curso, Módulo)
                SelectFilter::make('tipo_matricula')
                    ->label('Tipo de Matrícula')
                    ->options([
                        TipoMatricula::PROGRAMA->value => 'Programa',
                        TipoMatricula::FORMACION_CONTINUA->value => 'Formación Continua',
                        TipoMatricula::CURSO->value => 'Curso',
                        TipoMatricula::MODULO->value => 'Módulo',
                    ])
                    ->placeholder('Todos los tipos'),

                // Filtro por Curso
                SelectFilter::make('curso')
                    ->label('Curso/Módulo')
                    ->relationship('curso', 'nombre_curso')
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos los cursos'),
            ])
            ->recordActions([

                // 👉 Botón para visualizar/descargar PDF de la ficha
                Action::make('visualizar_ficha_pdf')
                    ->label('Visualizar Ficha PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Vista previa - Ficha de Matrícula')
                    ->modalContent(function (Matricula $record) {
                        // Cargamos relaciones necesarias
                        $record->load(['estudiante', 'horario.programa.cursos', 'curso']);

                        $pdf = Pdf::loadView('matriculas.pdf', [
                                'matricula' => $record,
                            ])
                            ->setPaper('A4', 'portrait');
                        
                        // Convertir PDF a base64
                        $pdfBase64 = base64_encode($pdf->output());
                        
                        return view('components.pdf-preview', [
                            'pdfBase64' => $pdfBase64,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalFooterActions(function (Matricula $record) {
                        return [
                            Action::make('descargar_ficha')
                                ->label('Descargar archivo PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function () use ($record) {
                                    // Cargamos relaciones necesarias
                                    $record->load(['estudiante', 'horario.programa.cursos', 'curso']);

                                    $pdf = Pdf::loadView('matriculas.pdf', [
                                            'matricula' => $record,
                                        ])
                                        ->setPaper('A4', 'portrait');

                                    $fileName = 'ficha-matricula-' . ($record->codigo_inscripcion ?? $record->id) . '.pdf';

                                    return response()->streamDownload(function () use ($pdf) {
                                        echo $pdf->output();
                                    }, $fileName);
                                }),
                        ];
                    })
                    ->modalWidth('7xl'),

                // 👉 Botón para visualizar/descargar PDF de cursos/módulos
                Action::make('visualizar_cursos_pdf')
                    ->label('Visualizar Cursos/Módulos PDF')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->modalHeading('Vista previa - Cursos/Módulos del Programa')
                    ->modalContent(function (Matricula $record) {
                        // Cargamos relaciones necesarias
                        $record->load(['estudiante', 'horario.programa.cursos', 'curso']);

                        $pdf = Pdf::loadView('matriculas.cursos-pdf', [
                                'matricula' => $record,
                            ])
                            ->setPaper('A4', 'portrait');
                        
                        // Convertir PDF a base64
                        $pdfBase64 = base64_encode($pdf->output());
                        
                        return view('components.pdf-preview', [
                            'pdfBase64' => $pdfBase64,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalFooterActions(function (Matricula $record) {
                        return [
                            Action::make('descargar_cursos')
                                ->label('Descargar archivo PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function () use ($record) {
                                    // Cargamos relaciones necesarias
                                    $record->load(['estudiante', 'horario.programa.cursos', 'curso']);

                                    $pdf = Pdf::loadView('matriculas.cursos-pdf', [
                                            'matricula' => $record,
                                        ])
                                        ->setPaper('A4', 'portrait');

                                    $fileName = 'cursos-matricula-' . ($record->codigo_inscripcion ?? $record->id) . '.pdf';

                                    return response()->streamDownload(function () use ($pdf) {
                                        echo $pdf->output();
                                    }, $fileName);
                                }),
                        ];
                    })
                    ->modalWidth('7xl'),

                // 👉 Botón para exportar directamente a Excel (sin modal)
                Action::make('exportar_excel')
                    ->label('Exportar excel (cursos)')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function (Matricula $record) {
                        // Cargamos relaciones necesarias
                        $record->load(['estudiante', 'horario.programa.cursos', 'curso']);
                        
                        $fileName = 'cursos-matricula-' . ($record->codigo_inscripcion ?? $record->id) . '.xlsx';
                        
                        return Excel::download(
                            new CursosMatriculaExport($record),
                            $fileName
                        );
                    }),
                    
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
