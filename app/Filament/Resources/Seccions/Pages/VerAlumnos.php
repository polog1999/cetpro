<?php

namespace App\Filament\Resources\Seccions\Pages;

use App\Filament\Resources\Seccions\SeccionResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

use App\Models\Matricula;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Filament\Resources\Estudiantes\EstudianteResource;
use App\Filament\Resources\Matriculas\MatriculaResource;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;

class VerAlumnos extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithRecord;

    protected static string $resource = SeccionResource::class;

    protected string $view = 'filament.resources.seccions.pages.ver-alumnos';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        $nombre_programa = $this->record->programa->nombre_programa ?? 'Sin programa';
        
        // Formatear los días
        $dias = '';
        if ($this->record->dias) {
            $dias = is_array($this->record->dias) 
                ? implode(', ', $this->record->dias) 
                : $this->record->dias;
            $dias = ' - ' . $dias;
        }
        
        return 'Alumnos de ' . $nombre_programa . $dias;
    }

    public function table(Table $table): Table
    {
        return $table
            // 6. Define la consulta:
            // "Dame todas las matrículas (con sus estudiantes) 
            // donde 'seccion_id' sea el ID de la sección que estamos viendo"
            ->query(
                Matricula::query()
                    ->with('estudiante')
                    ->where('seccion_id', $this->record->id_seccion),
            )
            ->columns([
                // 7. Esta es la columna clickeable (Paso 3 de tu plan)
                TextColumn::make('estudiante.nombres')
                    ->label('Nombre del Alumno')
                    ->searchable()
                    ->url(fn (Matricula $record): string => 
                        // Redirige a la página de Edición de la Matrícula
                        EstudianteResource::getUrl('edit', ['record' => $record->estudiante_id])
                    ),
                
                TextColumn::make('estudiante.nro_documento')
                    ->label('Documento'),

                TextColumn::make('codigo_inscripcion')
                    ->label('Cód. Matrícula')
                    ->searchable()
                    ->url(fn (Matricula $record): string => 
                        // Redirige a la página de Edición de la Matrícula
                        MatriculaResource::getUrl('edit', ['record' => $record->id])
                    ),
            ])
            // 8. Como pediste, sin botones de acción
            ->actions([])
            ->headerActions([])
            ->bulkActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('descargar_lista_pdf')
                ->label('Descargar Lista PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Obtener todas las matrículas de esta sección con sus estudiantes
                    $matriculas = Matricula::query()
                        ->where('seccion_id', $this->record->id_seccion)
                        ->with('estudiante')
                        ->get();
                    
                    // Extraer solo los estudiantes
                    $alumnos = $matriculas->map(fn($m) => $m->estudiante)->filter();
                    
                    // Formatear días de estudio
                    $dias_estudio = '';
                    if ($this->record->dias) {
                        $dias_estudio = is_array($this->record->dias) 
                            ? implode(', ', $this->record->dias) 
                            : $this->record->dias;
                    }
                    
                    // Obtener código de matrícula (usamos el de la primera matrícula como ejemplo)
                    $codigo_matricula = $matriculas->first()?->codigo_inscripcion ?? 'N/A';
                    
                    // Generar PDF
                    $pdf = Pdf::loadView('pdf.lista-alumnos', [
                        'seccion' => $this->record,
                        'alumnos' => $alumnos,
                        'dias_estudio' => $dias_estudio,
                        'codigo_matricula' => $codigo_matricula,
                    ]);
                    
                    // Nombre del archivo
                    $filename = 'lista-alumnos-' . $this->record->id_seccion . '.pdf';
                    
                    // Retornar PDF como descarga
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),
        ];
    }
}
