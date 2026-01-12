<?php

namespace App\Filament\Resources\Horarios\Pages;

use App\Filament\Resources\Horarios\HorarioResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Contracts\HasForm;

use Filament\Notifications\Notification;

use App\Enums\CalificacionLetra;
use App\Enums\TipoMatricula;

use App\Models\Matricula;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Nota;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AlumnosHorarioExport;

class VerAlumnos extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithRecord;

    protected static string $resource = HorarioResource::class;

    protected string $view = 'filament.resources.horarios.pages.ver-alumnos';

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
            // "Dame todas las matrículas (con sus estudiantes) 
            // donde 'horario_id' sea el ID del horario que estamos viendo"
            ->query(
                Matricula::query()
                    ->with('estudiante')
                    ->where('horario_id', $this->record->id_horario),
            )
            ->columns([
                TextColumn::make('estudiante.nombres')
                    ->label('Nombre del Alumno')
                    ->searchable(),
                
                TextColumn::make('estudiante.nro_documento')
                    ->label('Documento'),

                TextColumn::make('codigo_inscripcion')
                    ->label('Cód. Matrícula')
                    ->searchable(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('visualizar_lista_pdf')
                ->label('Listado de alumnos')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Vista previa del PDF - Lista de Alumnos')
                ->modalContent(function () {
                    // Obtener todas las matrículas de este horario con sus estudiantes
                    $matriculas = Matricula::query()
                        ->where('horario_id', $this->record->id_horario)
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
                    
                    // Obtener código de matrícula
                    $codigo_matricula = $matriculas->first()?->codigo_inscripcion ?? 'N/A';
                    
                    // Generar PDF
                    $pdf = Pdf::loadView('pdf.lista-alumnos', [
                        'horario'          => $this->record,
                        'alumnos'          => $alumnos,
                        'dias_estudio'     => $dias_estudio,
                        'codigo_matricula' => $codigo_matricula,
                    ]);
                    
                    // Convertir PDF a base64
                    $pdfBase64 = base64_encode($pdf->output());
                    
                    return view('components.pdf-preview', [
                        'pdfBase64' => $pdfBase64,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalFooterActions(function () {
                    return [
                        Action::make('descargar')
                            ->label('Descargar archivo PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->action(function () {
                                // Obtener todas las matrículas de este horario con sus estudiantes
                                $matriculas = Matricula::query()
                                    ->where('horario_id', $this->record->id_horario)
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
                                
                                // Obtener código de matrícula
                                $codigo_matricula = $matriculas->first()?->codigo_inscripcion ?? 'N/A';
                                
                                // Generar PDF
                                $pdf = Pdf::loadView('pdf.lista-alumnos', [
                                    'horario'          => $this->record,
                                    'alumnos'          => $alumnos,
                                    'dias_estudio'     => $dias_estudio,
                                    'codigo_matricula' => $codigo_matricula,
                                ]);
                                
                                // Nombre del archivo
                                $filename = 'lista-alumnos-' . $this->record->id_horario . '.pdf';
                                
                                // Retornar PDF como descarga
                                return response()->streamDownload(function () use ($pdf) {
                                    echo $pdf->output();
                                }, $filename);
                            }),
                        Action::make('exportar_excel')
                            ->label('Exportar a Excel')
                            ->icon('heroicon-o-table-cells')
                            ->color('success')
                            ->action(function () {
                                // Obtener todas las matrículas de este horario con sus estudiantes
                                $matriculas = Matricula::query()
                                    ->where('horario_id', $this->record->id_horario)
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
                                
                                // Nombre del archivo
                                $filename = 'lista-alumnos-' . $this->record->id_horario . '.xlsx';
                                
                                // Exportar a Excel
                                return Excel::download(
                                    new AlumnosHorarioExport($this->record, $alumnos, $dias_estudio),
                                    $filename
                                );
                            }),
                    ];
                })
                ->modalWidth('7xl'),
        ];
    }
}
