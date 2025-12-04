<?php

namespace App\Filament\Resources\Horarios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enums\Turno;
use App\Enums\Modalidad;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Horario;
use App\Filament\Resources\Horarios\HorarioResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class HorariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('programa.nombre_programa')
                    ->label('Programa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('docente.nombre_completo')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('turno')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn (?Turno $state) => $state?->getLabel())
                    ->color(fn (?Turno $state) => 'primary'),

                TextColumn::make('modalidad')
                    ->label('Modalidad')
                    ->badge()
                    ->formatStateUsing(fn (?Modalidad $state) => $state?->getLabel())
                    ->color(fn (?Modalidad $state) => $state?->getColor()),

                TextColumn::make('dias')
                    ->label('Días')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }

                        return $state;
                    }),

                TextColumn::make('horario')
                    ->label('Horas'),

                TextColumn::make('aula')
                    ->label('Aula')
                    ->toggleable(),

                TextColumn::make('matriculas_count')
                    ->label('Alumnos inscritos')
                    ->counts('matriculas')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Creado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Actualizado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make('verAlumnos')
                    ->label('Ver Alumnos')
                    ->icon('heroicon-m-users')
                    ->button()
                    ->color('info')
                    ->url(fn (Horario $record): string => HorarioResource::getUrl('ver-alumnos', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('visualizar_pdf')
                    ->label('Visualizar PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Vista previa del PDF')
                    ->modalContent(function (Horario $record) {
                        // Cargar relaciones necesarias
                        $record->load([
                            'programa.especialidad',
                            'programa.cursos',
                            'docente',
                        ]);
                        
                        // Generar PDF
                        $pdf = Pdf::loadView('pdf.horario-pdf', [
                            'horario' => $record,
                        ]);
                        
                        // Convertir PDF a base64 para mostrarlo en iframe
                        $pdfBase64 = base64_encode($pdf->output());
                        
                        return view('components.pdf-preview', [
                            'pdfBase64' => $pdfBase64,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalFooterActions(function (Horario $record) {
                        return [
                            Action::make('descargar')
                                ->label('Descargar archivo PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function () use ($record) {
                                    // Cargar relaciones necesarias
                                    $record->load([
                                        'programa.especialidad',
                                        'programa.cursos',
                                        'docente',
                                    ]);
                                    
                                    // Generar PDF
                                    $pdf = Pdf::loadView('pdf.horario-pdf', [
                                        'horario' => $record,
                                    ]);
                                    
                                    // Nombre del archivo
                                    $filename = 'horario-' . $record->id_horario . '.pdf';
                                    
                                    // Retornar PDF como descarga
                                    return response()->streamDownload(function () use ($pdf) {
                                        echo $pdf->output();
                                    }, $filename);
                                }),
                        ];
                    })
                    ->modalWidth('7xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
