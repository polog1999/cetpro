<?php

namespace App\Filament\Resources\Programas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Enums\TipoPrograma;
use Filament\Support\Enums\FontWeight;

/**
 * RelationManager para gestionar unidades dentro de módulos.
 * 
 * Solo se muestra para cursos que pertenecen a programas de tipo PROGRAMA_ESTUDIO.
 */
class UnidadesRelationManager extends RelationManager
{
    protected static string $relationship = 'unidades';

    protected static ?string $recordTitleAttribute = 'nombre_unidad';

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Solo mostrar este RelationManager si el curso pertenece a un Programa de Estudio.
     */
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Verificar si el programa padre es de tipo PROGRAMA_ESTUDIO
        $programa = $ownerRecord->programa;
        
        if (!$programa) {
            return false;
        }
        
        return $programa->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Información de la Unidad')
                ->description('Complete los datos de la unidad didáctica')
                ->icon('heroicon-o-academic-cap')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nombre_unidad')
                        ->label('Nombre de la Unidad')
                        ->placeholder('Ej: Introducción a la Cosmetología')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(2)
                        ->prefixIcon('heroicon-o-bookmark'),

                    Forms\Components\TextInput::make('duracion')
                        ->label('Duración')
                        ->placeholder('Ej: 40')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->suffix('horas')
                        ->nullable()
                        ->prefixIcon('heroicon-o-clock')
                        ->helperText('Duración total en horas pedagógicas'),

                    Forms\Components\TextInput::make('orden')
                        ->label('Orden')
                        ->placeholder('Ej: 1')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->prefixIcon('heroicon-o-queue-list')
                        ->helperText('Posición en la secuencia del módulo'),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('Describa brevemente el contenido y objetivos de esta unidad...')
                        ->rows(3)
                        ->nullable()
                        ->columnSpan(2)
                        ->helperText('Descripción del contenido y competencias a desarrollar'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        // Calcular estadísticas del módulo
        $totalUnidades = $this->getOwnerRecord()->unidades()->count();
        $totalHoras = $this->getOwnerRecord()->unidades()->sum('duracion') ?? 0;

        return $table
            ->heading('📚 Unidades del Módulo')
            ->description(
                $totalUnidades > 0 
                    ? "✓ {$totalUnidades} unidad(es) · ⏱ {$totalHoras} horas totales"
                    : 'No hay unidades registradas aún'
            )
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('N°')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('nombre_unidad')
                    ->label('Unidad Didáctica')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->description(fn ($record) => $record->descripcion 
                        ? \Illuminate\Support\Str::limit($record->descripcion, 80) 
                        : null
                    )
                    ->icon('heroicon-o-bookmark')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('duracion')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} hrs" : '—')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === null => 'gray',
                        $state <= 20 => 'info',
                        $state <= 40 => 'success',
                        default => 'warning',
                    })
                    ->icon('heroicon-o-clock')
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->duracion 
                        ? "Aproximadamente " . ceil($record->duracion / 3) . " sesiones de 3 horas"
                        : 'Duración no especificada'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrada')
                    ->date('d M, Y')
                    ->color('gray')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('orden')
            ->striped()
            ->emptyStateHeading('Sin unidades registradas')
            ->emptyStateDescription('Comience agregando la primera unidad a este módulo.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva Unidad')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->modalHeading('Agregar Nueva Unidad')
                    ->modalDescription('Complete los datos de la unidad para agregarla al módulo.')
                    ->modalIcon('heroicon-o-academic-cap')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-asignar el siguiente orden si no se especifica
                        if (empty($data['orden'])) {
                            $maxOrden = $this->getOwnerRecord()->unidades()->max('orden') ?? 0;
                            $data['orden'] = $maxOrden + 1;
                        }
                        return $data;
                    })
                    ->successNotificationTitle('✓ Unidad agregada correctamente'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver detalles'),
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Editar unidad')
                    ->modalHeading('Editar Unidad')
                    ->successNotificationTitle('✓ Unidad actualizada'),
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Eliminar unidad')
                    ->modalHeading('¿Eliminar esta unidad?')
                    ->modalDescription('Esta acción no se puede deshacer. Se eliminará permanentemente esta unidad del módulo.')
                    ->successNotificationTitle('✓ Unidad eliminada'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Eliminar seleccionadas')
                    ->modalHeading('¿Eliminar unidades seleccionadas?')
                    ->modalDescription('Esta acción eliminará permanentemente las unidades seleccionadas.'),
            ])
            ->reorderable('orden')
            ->reorderRecordsTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('Reordenar')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
            );
    }
}
