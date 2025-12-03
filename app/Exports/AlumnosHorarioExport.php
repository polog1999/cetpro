<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AlumnosHorarioExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $horario;
    protected $alumnos;
    protected $diasEstudio;

    public function __construct($horario, $alumnos, $diasEstudio)
    {
        $this->horario = $horario;
        $this->alumnos = $alumnos;
        $this->diasEstudio = $diasEstudio;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->alumnos;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            '#',
            'Nombre',
            'Apellidos',
            'DNI',
            'Celular',
        ];
    }

    /**
     * @param mixed $alumno
     * @return array
     */
    public function map($alumno): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $alumno->nombres ?? '',
            trim(($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '')),
            $alumno->nro_documento ?? '',
            $alumno->telefono ?? '',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Insertar información del programa en las primeras filas
        $nombrePrograma = $this->horario->programa->nombre_programa ?? 'Sin programa';
        
        $sheet->insertNewRowBefore(1, 3);
        $sheet->setCellValue('A1', 'Lista de alumnos');
        $sheet->setCellValue('A2', $nombrePrograma);
        $sheet->setCellValue('A3', 'Días de estudio: ' . $this->diasEstudio);

        // Estilos para el encabezado de información
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Obtener el número de filas con datos (incluyendo encabezados)
        $highestRow = $sheet->getHighestRow();
        
        // Aplicar bordes a toda la tabla (desde la fila 4 que es donde están los encabezados)
        $sheet->getStyle('A4:E' . $highestRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Estilo para los encabezados de columna (fila 4)
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F5F5F5',
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Ajustar anchos de columnas
        $sheet->getColumnDimension('A')->setWidth(8);   // #
        $sheet->getColumnDimension('B')->setWidth(25);  // Nombre
        $sheet->getColumnDimension('C')->setWidth(30);  // Apellidos
        $sheet->getColumnDimension('D')->setWidth(15);  // DNI
        $sheet->getColumnDimension('E')->setWidth(15);  // Celular

        // Centrar la columna de número
        $sheet->getStyle('A5:A' . $highestRow)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Centrar las columnas de DNI y Celular
        $sheet->getStyle('D5:E' . $highestRow)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Lista de Alumnos';
    }
}
