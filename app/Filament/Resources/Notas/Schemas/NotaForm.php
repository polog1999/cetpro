<?php

namespace App\Filament\Resources\Notas\Schemas;

use App\Enums\CalificacionLetra;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Matricula;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class NotaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Nota')
                    ->schema([
                        // Matrícula - mostrar código de inscripción
                        Select::make('matricula_id')
                            ->label('Matrícula')
                            ->relationship('matricula', 'codigo_inscripcion')
                            ->getOptionLabelFromRecordUsing(fn (Matricula $record) => 
                                "{$record->codigo_inscripcion} - {$record->estudiante?->nombre_completo}"
                            )
                            ->searchable(['codigo_inscripcion'])
                            ->preload()
                            ->required(),
                        
                        // Curso - mostrar nombre del curso
                        Select::make('curso_id')
                            ->label('Curso')
                            ->relationship('curso', 'nombre_curso')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        // Docente que registra la nota
                        Select::make('docente_id')
                            ->label('Docente')
                            ->relationship('docente', 'nombres')
                            ->getOptionLabelFromRecordUsing(fn (Docente $record) => 
                                "{$record->nombres} {$record->apellido_paterno}"
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Calificación')
                    ->schema([
                        // Nota numérica (0-20)
                        TextInput::make('nota_numerica')
                            ->label('Nota numérica')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->step(0.01)
                            ->helperText('Ingrese una nota entre 0 y 20'),
                        
                        // Nota en letras (AD, A, B, C)
                        Select::make('nota_letra')
                            ->label('Nota en letras')
                            ->options(CalificacionLetra::class)
                            ->helperText('Seleccione AD, A, B o C'),
                    ])
                    ->columns(2)
                    ->description('Complete al menos uno de los dos campos de calificación'),

                Section::make('Documentos y Observaciones')
                    ->schema([
                        // PDF de calificación
                        FileUpload::make('pdf_calificacion')
                            ->label('Documento de Calificación (PDF)')
                            ->directory('notas')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Suba un documento PDF (máx. 5MB)'),
                        
                        // Observaciones
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Observaciones adicionales sobre la calificación...'),
                    ])
                    ->collapsed(),
            ]);
    }
}
