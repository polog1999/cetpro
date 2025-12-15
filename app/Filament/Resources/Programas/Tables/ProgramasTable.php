<?php

namespace App\Filament\Resources\Programas\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Programa;
use Filament\Actions\Action;
use App\Filament\Resources\Programas\ProgramaResource;
use App\Enums\TipoPrograma;

class ProgramasTable
{
    use PreventDeleteWithDependencies;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_programa')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('tipo_programa')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(
                        fn (?TipoPrograma $state) => $state?->getLabel()
                    )
                    ->color(
                        fn (?TipoPrograma $state) => $state?->getColor()
                    ),

                TextColumn::make('duracion')
                    ->label('Meses')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('num_cursos')
                    ->label('N° cursos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('especialidad.nombre_especialidad')
                    ->label('Especialidad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('horarios_count')
                    ->label('Horarios')
                    ->counts('horarios')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('matriculas_count')
                    ->label('Total alumnos')
                    ->getStateUsing(function (Programa $record): int {
                        return $record->horarios()
                            ->withCount('matriculas')
                            ->get()
                            ->sum('matriculas_count');
                    })
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
            ->recordActions([
                Action::make('agregarCursos')
                    ->label(fn (Programa $record): string => 
                        $record->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO ? 'Módulos' : 'Cursos'
                    )
                    ->icon('heroicon-m-academic-cap')
                    ->color('success')
                    ->button()
                    ->url(fn (Programa $record): string =>
                        ProgramaResource::getUrl('view', ['record' => $record])
                    ),
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (DeleteAction $action, $record) => 
                        self::preventDeleteWithDependencies(
                            $action,
                            $record,
                            'horarios',
                            'horario(s)'
                        )
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'horarios',
                                'horario(s)',
                                'nombre_programa'
                            )
                        ),
                ]),
            ]);
    }
}
