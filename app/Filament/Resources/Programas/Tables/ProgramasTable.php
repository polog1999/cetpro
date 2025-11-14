<?php

namespace App\Filament\Resources\Programas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Programa;
use Filament\Actions\Action;

use App\Filament\Resources\Programas\ProgramaResource;




class ProgramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_programa')
                    ->searchable(),
                TextColumn::make('duracion')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('num_componentes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_rubro')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                // 👇 Nuevo botón "Agregar cursos"
            Action::make('agregarCursos')
                ->label('Agregar cursos')
                ->icon('heroicon-m-plus')
                ->button()
                ->url(fn (Programa $record): string =>
                    ProgramaResource::getUrl('view', ['record' => $record])
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),

              

                
            ]);
            
    }
}
