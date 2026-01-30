<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Enums\TipoCertificado;
use App\Models\Matricula;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->label('DNI')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                    ->sortable(['apellido_paterno']),

                TextColumn::make('matriculas_con_documento')
                    ->label('Documentos')
                    ->getStateUsing(fn ($record) => self::contarDocumentos($record))
                    ->badge()
                    ->color(fn (string $state) => $state === '0' ? 'gray' : 'success'),
            ])
            ->filters([
                Filter::make('con_documento')
                    ->label('Con documento')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('matriculas', fn ($q) => $q->whereNotNull('documento_path'))
                    )
                    ->toggle(),
                    
                Filter::make('sin_documento')
                    ->label('Sin documento')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('matriculas', fn ($q) => $q->whereNotNull('documento_path'))
                    )
                    ->toggle(),
            ])
            ->actions([
                // Acción SUBIR: Abre formulario para elegir matrícula y subir documento
                \Filament\Actions\Action::make('subir_documento')
                    ->label('Subir')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form(function ($record) {
                        $matriculas = $record->matriculas()
                            ->with(['horario.programa', 'curso'])
                            ->get()
                            ->mapWithKeys(function ($matricula) {
                                $descripcion = $matricula->codigo_inscripcion . ' | ';
                                $descripcion .= $matricula->curso?->nombre_curso 
                                    ?? $matricula->horario?->programa?->nombre_programa 
                                    ?? 'Sin programa';
                                $descripcion .= ' | ' . $matricula->tipo_matricula->value;
                                return [$matricula->id => $descripcion];
                            });
                        
                        return [
                            Select::make('matricula_id')
                                ->label('Seleccionar Matrícula')
                                ->options($matriculas)
                                ->required()
                                ->searchable()
                                ->helperText('Seleccione la matrícula a la que pertenece este certificado'),
                            
                            Select::make('tipo_certificado')
                                ->label('Tipo de Certificado')
                                ->options(collect(TipoCertificado::cases())->mapWithKeys(
                                    fn ($case) => [$case->value => $case->getLabel()]
                                ))
                                ->default(TipoCertificado::CERTIFICADO_ESTUDIOS->value)
                                ->required(),
                            
                            FileUpload::make('documento')
                                ->label('Documento PDF')
                                ->acceptedFileTypes(['application/pdf'])
                                ->required()
                                ->disk('public')
                                ->directory('certificados')
                                ->preserveFilenames(false)
                                ->maxSize(5120) // 5MB
                                ->helperText('Formato: PDF. Tamaño máximo: 5MB'),
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        $matricula = Matricula::find($data['matricula_id']);
                        
                        if (!$matricula) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error: Matrícula no encontrada')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Generar nombre de archivo único
                        $nombreArchivo = self::generarNombreArchivo($record, $matricula);
                        $rutaActual = $data['documento'];
                        $nuevaRuta = 'certificados/' . $nombreArchivo;
                        
                        // Eliminar documento anterior si existe
                        if ($matricula->documento_path && Storage::disk('public')->exists($matricula->documento_path)) {
                            Storage::disk('public')->delete($matricula->documento_path);
                        }
                        
                        // Mover archivo con nombre correcto
                        if ($rutaActual !== $nuevaRuta) {
                            Storage::disk('public')->move($rutaActual, $nuevaRuta);
                        }
                        
                        // Actualizar matrícula
                        $matricula->update([
                            'documento_path' => $nuevaRuta,
                            'tipo_certificado' => $data['tipo_certificado'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Documento subido correctamente')
                            ->body("Certificado vinculado a: {$matricula->codigo_inscripcion}")
                            ->success()
                            ->send();
                    })
                    ->modalHeading(fn ($record) => "Subir certificado para {$record->nombre_completo}")
                    ->modalSubmitActionLabel('Subir documento'),
                
                // Acción VER: Muestra modal con tabla de todas las matrículas
                \Filament\Actions\Action::make('ver_documentos')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Certificados de {$record->nombre_completo}")
                    ->modalContent(fn ($record) => view('filament.documentos.ver-matriculas-modal', [
                        'estudiante' => $record,
                        'matriculas' => $record->matriculas()
                            ->with(['horario.programa', 'curso'])
                            ->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('5xl'),
            ])
            ->bulkActions([])
            ->defaultSort('apellido_paterno', 'asc');
    }

    /**
     * Genera el nombre del archivo basado en el estudiante y matrícula
     */
    private static function generarNombreArchivo($estudiante, $matricula): string
    {
        $nombre = $estudiante->apellido_paterno . '_' . 
                  $estudiante->apellido_materno . '_' . 
                  $estudiante->nombres . '_' .
                  $matricula->codigo_inscripcion;
        $nombre = Str::ascii($nombre);
        $nombre = Str::slug($nombre, '_');
        return $nombre . '.pdf';
    }

    /**
     * Cuenta cuántas matrículas tienen documento
     */
    private static function contarDocumentos($record): string
    {
        $total = $record->matriculas()->count();
        $conDocumento = $record->matriculas()->whereNotNull('documento_path')->count();
        return "{$conDocumento}/{$total}";
    }
}
