<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\FileUpload;
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
                
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                IconColumn::make('tiene_documento')
                    ->label('Documento')
                    ->getStateUsing(fn ($record) => self::documentoExiste($record))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Filter::make('con_documento')
                    ->label('Con documento')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('1=1'))
                    ->toggle(),
                    
                Filter::make('sin_documento')
                    ->label('Sin documento')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('1=1'))
                    ->toggle(),
            ])
            ->actions([
                \Filament\Actions\Action::make('subir_documento')
                    ->label('Subir')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('documento')
                            ->label('Documento PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->disk('public')
                            ->directory('documentos')
                            ->preserveFilenames(false)
                            ->getUploadedFileNameForStorageUsing(fn ($record) => self::generarNombreArchivo($record))
                            ->maxSize(5120) // 5MB
                            ->helperText('Formato: PDF. Tamaño máximo: 5MB'),
                    ])
                    ->action(function ($record, array $data): void {
                        // El archivo ya fue subido por FileUpload
                        // Solo necesitamos renombrarlo si es necesario
                        $nombreArchivo = self::generarNombreArchivo($record);
                        $rutaActual = $data['documento'];
                        $nuevaRuta = 'documentos/' . $nombreArchivo;
                        
                        // Si el archivo ya existe con otro nombre, eliminarlo primero
                        if (Storage::disk('public')->exists($nuevaRuta) && $rutaActual !== $nuevaRuta) {
                            Storage::disk('public')->delete($nuevaRuta);
                        }
                        
                        // Mover el archivo al nombre correcto
                        if ($rutaActual !== $nuevaRuta) {
                            Storage::disk('public')->move($rutaActual, $nuevaRuta);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Documento subido correctamente')
                            ->success()
                            ->send();
                    })
                    ->modalHeading(fn ($record) => "Subir documento de {$record->nombre_completo}")
                    ->modalSubmitActionLabel('Subir documento'),
                
                \Filament\Actions\Action::make('ver_documento')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => self::documentoExiste($record))
                    ->modalHeading(fn ($record) => "Documento de {$record->nombre_completo}")
                    ->modalContent(fn ($record) => view('filament.documentos.ver-documento-modal', [
                        'url' => self::getDocumentoUrl($record),
                        'nombre' => $record->nombre_completo,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('7xl'),

                \Filament\Actions\Action::make('descargar_documento')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => self::documentoExiste($record))
                    ->action(function ($record) {
                        $nombreArchivo = self::generarNombreArchivo($record);
                        $ruta = Storage::disk('public')->path('documentos/' . $nombreArchivo);
                        return response()->download($ruta, $nombreArchivo);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('apellido_paterno', 'asc');
    }

    /**
     * Genera el nombre del archivo basado en el nombre del estudiante
     */
    private static function generarNombreArchivo($record): string
    {
        $nombre = $record->apellido_paterno . '_' . $record->apellido_materno . '_' . $record->nombres;
        $nombre = Str::ascii($nombre); // Remover acentos
        $nombre = Str::slug($nombre, '_'); // Convertir a slug con guiones bajos
        return $nombre . '.pdf';
    }

    /**
     * Verifica si el documento del estudiante existe
     */
    private static function documentoExiste($record): bool
    {
        $nombreArchivo = self::generarNombreArchivo($record);
        return Storage::disk('public')->exists('documentos/' . $nombreArchivo);
    }

    /**
     * Obtiene la URL pública del documento
     */
    private static function getDocumentoUrl($record): string
    {
        $nombreArchivo = self::generarNombreArchivo($record);
        return Storage::disk('public')->url('documentos/' . $nombreArchivo);
    }
}
