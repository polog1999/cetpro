<?php

namespace App\Filament\Resources\Estudiantes\RelationManagers;

use App\Enums\CalificacionLetra;
use App\Models\Nota;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Calificación')
                    ->schema([
                        Select::make('curso_id')
                            ->label('Curso')
                            ->relationship('curso', 'nombre_curso')
                            ->required(),
                        
                        Select::make('docente_id')
                            ->label('Docente')
                            ->relationship('docente', 'nombres')
                            ->default(fn () => auth()->user()?->docente_id)
                            ->required(),
                        
                        TextInput::make('nota_numerica')
                            ->label('Nota numérica')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->step(0.01)
                            ->helperText('Ingrese una nota entre 0 y 20'),
                        
                        Select::make('nota_letra')
                            ->label('Nota en letras')
                            ->options(CalificacionLetra::class)
                            ->helperText('O seleccione AD, A, B o C'),
                    ])
                    ->columns(2),

                Section::make('Documentos')
                    ->schema([
                        FileUpload::make('pdf_calificacion')
                            ->label('Documento PDF')
                            ->directory('notas')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120),
                        
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->searchable(),
                TextColumn::make('nota_numerica')
                    ->label('Nota Num.')
                    ->badge()
                    ->color(fn ($state) => $state !== null && $state >= 11 ? 'success' : 'danger'),
                TextColumn::make('nota_letra')
                    ->label('Nota Letra')
                    ->badge()
                    ->color(fn ($state) => in_array($state, ['AD', 'A']) ? 'success' : 'warning'),
                TextColumn::make('docente.nombres')
                    ->label('Profesor'),
                TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->date('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Acción de subir nota - solo visible si el profesor NO ha subido nota aún
                CreateAction::make()
                    ->label('Subir Nota')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(function () {
                        $user = auth()->user();
                        
                        // Si es admin, siempre puede crear
                        if ($user?->role?->es_admin) {
                            return true;
                        }
                        
                        // Si es profesor, verificar si ya subió nota para esta matrícula
                        if ($user?->esProfesor()) {
                            $docenteId = $user->docente_id;
                            $matriculaId = $this->getOwnerRecord()->id;
                            
                            // Verificar si ya existe una nota de este docente para esta matrícula
                            $yaSubioNota = Nota::where('matricula_id', $matriculaId)
                                ->where('docente_id', $docenteId)
                                ->exists();
                            
                            // Solo mostrar si NO ha subido nota
                            return !$yaSubioNota;
                        }
                        
                        return false;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-asignar el docente si es profesor
                        $user = auth()->user();
                        if ($user?->esProfesor() && $user->docente_id) {
                            $data['docente_id'] = $user->docente_id;
                        }
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Nota registrada')
                            ->body('La nota ha sido subida correctamente.')
                            ->send();
                    }),
            ])
            ->recordActions([
                // Solo admin puede editar/eliminar
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->role?->es_admin ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role?->es_admin ?? false),
                ]),
            ]);
    }
}
