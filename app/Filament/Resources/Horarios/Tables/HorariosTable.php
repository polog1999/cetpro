<?php

namespace App\Filament\Resources\Horarios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
            ->recordActions([
                Action::make('ver_alumnos')
                    ->label('Ver alumnos')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->url(fn (Horario $record): string => 
                        HorarioResource::getUrl('ver-alumnos', ['record' => $record->id_horario])
                    ),
                
                Action::make('descargar_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Horario $record) {
                        // Cargar relaciones necesarias
                        $record->load([
                            'programa.especialidad',
                            'programa.cursos',
                            'docente',
                        ]);
                        
                        // Generar PDF
                        $pdf = Pdf::loadView('pdf.seccion-pdf', [
                            'horario' => $record, // actualizado de 'seccion' a 'horario'
                        ]);
                        
                        // Nombre del archivo
                        $filename = 'horario-' . $record->id_horario . '.pdf';
                        
                        // Retornar PDF como descarga
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
