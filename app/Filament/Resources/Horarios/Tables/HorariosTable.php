<?php

namespace App\Filament\Resources\Horarios\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\Turno;
use App\Enums\Modalidad;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Horario;
use App\Models\Programa;
use App\Models\Docente;
use App\Filament\Resources\Horarios\HorarioResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Usuario;

class HorariosTable
{
    use PreventDeleteWithDependencies;
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

                TextColumn::make('aula')
                    ->label('Aula')
                    ->toggleable(),

                TextColumn::make('matriculas_count')
                    ->label('Inscritos')
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
                SelectFilter::make('id_programa')
                    ->label('Programa')
                    ->relationship('programa', 'nombre_programa')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('id_docente')
                    ->label('Docente')
                    ->options(fn () => Docente::all()->pluck('nombre_completo', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('turno')
                    ->label('Turno')
                    ->options(Turno::class),

                SelectFilter::make('aula')
                    ->label('Aula')
                    ->options(fn () => Horario::whereNotNull('aula')
                        ->distinct()
                        ->pluck('aula', 'aula')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make('verAlumnos')
                    ->label('Ver Alumnos')
                    ->icon('heroicon-m-users')
                    ->button()
                    ->color('info')
                    ->url(fn (Horario $record): string => HorarioResource::getUrl('ver-alumnos', ['record' => $record])),
                
                Action::make('visualizar_pdf')
                    ->label('')
                    ->tooltip('Visualizar PDF')
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
                DeleteAction::make()
                    ->label('')
                    ->visible(fn () => !auth()->user()?->esProfesor())
                    ->before(fn (DeleteAction $action, $record) => 
                        self::preventDeleteWithDependencies(
                            $action,
                            $record,
                            'matriculas',
                            'matrícula(s) activa(s)'
                        )
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => !auth()->user()?->esProfesor())
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'matriculas',
                                'matrícula(s)',
                                'id_horario'
                            )
                        ),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                /** @var Usuario|null $user */
                $user = Auth::user();
                
                // Si es profesor, mostrar solo sus horarios
                if ($user instanceof Usuario && $user->esProfesor() && $user->docente_id) {
                    $query->where('id_docente', $user->docente_id);
                }
                
                return $query;
            });
    }
}
