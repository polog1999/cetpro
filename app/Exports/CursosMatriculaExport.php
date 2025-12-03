<?php

namespace App\Exports;

use App\Models\Matricula;
use App\Enums\TipoMatricula;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CursosMatriculaExport implements FromCollection, WithStyles, WithTitle, WithEvents
{
    protected $matricula;

    public function __construct(Matricula $matricula)
    {
        $this->matricula = $matricula;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Retornamos una colección vacía ya que usaremos AfterSheet para construir el documento
        return collect([]);
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Cursos Matrícula';
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $est = $this->matricula->estudiante;
                $horario = $this->matricula->horario;
                $prog = $horario?->programa;
                $curso = $this->matricula->curso;
                $cursos = $prog?->cursos ?? collect();
                
                $esTipoModulo = in_array($this->matricula->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::MODULO]);
                $labelCursos = $esTipoModulo ? 'MÓDULOS' : 'CURSOS';
                $labelPrograma = $esTipoModulo ? 'PROGRAMA' : 'FORMACIÓN CONTINUA';
                $labelCursoSingular = $esTipoModulo ? 'Módulo' : 'Curso';
                
                $row = 1;
                
                // TÍTULO
                $sheet->setCellValue('A' . $row, $labelCursos . ' DE MATRÍCULA');
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
                
                // SUBTÍTULO
                $sheet->setCellValue('A' . $row, $labelPrograma . ': ' . ($prog?->nombre_programa ?? ''));
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row += 2;
                
                // INFORMACIÓN DEL ESTUDIANTE
                $sheet->setCellValue('A' . $row, 'Estudiante:');
                $sheet->setCellValue('B' . $row, ($est?->nombres ?? '') . ' ' . ($est?->apellido_paterno ?? '') . ' ' . ($est?->apellido_materno ?? ''));
                $sheet->mergeCells('B' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'DNI:');
                $sheet->setCellValue('B' . $row, $est?->nro_documento);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Código Inscripción:');
                $sheet->setCellValue('B' . $row, $this->matricula->codigo_inscripcion);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Tipo de Matrícula:');
                $sheet->setCellValue('B' . $row, $this->matricula->tipo_matricula?->value ?? $this->matricula->tipo_matricula);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row += 2;
                
                // INFORMACIÓN DEL HORARIO (como en el PDF)
                $sheet->setCellValue('A' . $row, 'INFORMACIÓN DEL HORARIO');
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $this->applySectionStyle($sheet, 'A' . $row . ':D' . $row);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Turno:');
                $sheet->setCellValue('B' . $row, $horario?->turno?->value ?? $horario?->turno);
                $sheet->setCellValue('C' . $row, 'Modalidad:');
                $sheet->setCellValue('D' . $row, $horario?->modalidad?->value ?? $horario?->modalidad);
                $this->applyBorders($sheet, 'A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('C' . $row)->getFont()->setBold(true);
                $row++;
                
                $dias = is_array($horario?->dias) ? implode(', ', $horario->dias) : $horario?->dias;
                $sheet->setCellValue('A' . $row, 'Días:');
                $sheet->setCellValue('B' . $row, $dias);
                $sheet->setCellValue('C' . $row, 'Horario:');
                $sheet->setCellValue('D' . $row, $horario?->horario);
                $this->applyBorders($sheet, 'A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('C' . $row)->getFont()->setBold(true);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Duración:');
                $sheet->setCellValue('B' . $row, ($prog?->duracion ?? '') . ' meses');
                $sheet->mergeCells('B' . $row . ':D' . $row);
                $this->applyBorders($sheet, 'A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row += 2;
                
                // TABLA DE CURSOS/MÓDULOS
                $sheet->setCellValue('A' . $row, $labelCursos . ' DEL ' . $labelPrograma);
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $this->applySectionStyle($sheet, 'A' . $row . ':D' . $row);
                $row++;
                
                // Encabezados de la tabla
                $sheet->setCellValue('A' . $row, '#');
                $sheet->setCellValue('B' . $row, 'Nombre del ' . $labelCursoSingular);
                $sheet->setCellValue('C' . $row, 'Duración');
                $sheet->setCellValue('D' . $row, 'Fecha Inicio');
                $this->applySectionStyle($sheet, 'A' . $row . ':D' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
                
                // Filas de cursos
                if ($cursos->isEmpty()) {
                    $sheet->setCellValue('A' . $row, 'Este ' . strtolower($labelPrograma) . ' no tiene ' . strtolower($labelCursos) . ' registrados.');
                    $sheet->mergeCells('A' . $row . ':D' . $row);
                    $this->applyBorders($sheet, 'A' . $row . ':D' . $row);
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $index = 1;
                    foreach ($cursos as $c) {
                        $esMatriculado = $curso && $curso->id_curso === $c->id_curso;
                        $fechaInicio = $c->fecha_inicio ? \Carbon\Carbon::parse($c->fecha_inicio)->format('d/m/Y') : '-';
                        
                        $sheet->setCellValue('A' . $row, $index);
                        $nombreCurso = $c->nombre_curso;
                        if ($esMatriculado) {
                            $nombreCurso .= ' ✓ (Matriculado)';
                        }
                        $sheet->setCellValue('B' . $row, $nombreCurso);
                        $sheet->setCellValue('C' . $row, $c->duracion ?? '-');
                        $sheet->setCellValue('D' . $row, $fechaInicio);
                        
                        $this->applyBorders($sheet, 'A' . $row . ':D' . $row);
                        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        
                        // Resaltar si está matriculado
                        if ($esMatriculado) {
                            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => [
                                        'rgb' => 'FFFFCC',
                                    ],
                                ],
                                'font' => [
                                    'bold' => true,
                                ],
                            ]);
                        }
                        
                        $row++;
                        $index++;
                    }
                }
                
                // Ajustar anchos de columnas
                $sheet->getColumnDimension('A')->setWidth(8);   // #
                $sheet->getColumnDimension('B')->setWidth(40);  // Nombre
                $sheet->getColumnDimension('C')->setWidth(15);  // Duración
                $sheet->getColumnDimension('D')->setWidth(15);  // Fecha Inicio
            },
        ];
    }

    private function applyBorders(Worksheet $sheet, string $range)
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function applySectionStyle(Worksheet $sheet, string $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'EAEAEA',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }
}
