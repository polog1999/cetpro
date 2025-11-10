<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\Page;

use Filament\Schemas\Schema;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Models\Estudiante;
use App\Models\Matricula;

class GestionarPagos extends Page
{
    use InteractsWithForms;
    protected static string $resource = PagoResource::class;
    protected string $view = 'filament.resources.pagos.pages.gestionar-pagos';
    protected static ?string $title = 'Generador de Pagos';

    public ?int $estudiante_id = null;
    public ?int $matricula_id = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('estudiante_id')
                    ->label('Estudiante')
                    ->options(Estudiante::all()->pluck('nombres', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('matricula_id', null)) // Resetea la sección
                    ->required(),
            
                Select::make('matricula_id')
                    ->label('Sección (Matrícula)')
                    ->options(fn (Get $get): array => 
                        // Carga solo las matrículas del estudiante seleccionado
                        Matricula::where('estudiante_id', $get('estudiante_id'))
                            ->with('seccion') // Carga la relación 'seccion'
                            ->get()
                            // Usa el accesor 'nombre_completo'
                            ->pluck('seccion.nombre_completo', 'id') 
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // Reactivo
                    ->disabled(fn (Get $get): bool => blank($get('estudiante_id'))), // Se deshabilita si no hay estudiante
            ])
            // ->rows()
            ->statePath('data'); // Guarda el estado en una propiedad 'data'
    }

}
