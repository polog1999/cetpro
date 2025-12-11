<?php

namespace App\Filament\Resources\Empleados\Schemas;

use App\Enums\TipoDocumento;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class EmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('apellido_paterno')
                    ->required()
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('apellido_materno')
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('correo')
                    ->email()
                    ->required(),
                TextInput::make('celular')
                    ->numeric()
                    ->maxLength(9)
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"]),
                Select::make('tipo_documento')
                    ->options(TipoDocumento::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('num_documento_component')
                        ->state(null) // Limpiar valor al cambiar tipo
                    ),
                TextInput::make('num_documento')
                    ->key('num_documento_component')
                    ->required()
                    ->maxLength(function ($get) {
                        $tipo = $get('tipo_documento');
                        if (! $tipo instanceof TipoDocumento) {
                            $tipo = TipoDocumento::tryFrom($tipo);
                        }
                        return $tipo?->getMaxLength() ?? 8;
                    })
                    ->extraInputAttributes(function ($get) {
                        $tipo = $get('tipo_documento');
                        if (! $tipo instanceof TipoDocumento) {
                            $tipo = TipoDocumento::tryFrom($tipo);
                        }
                        $isNumeric = $tipo?->isNumeric() ?? true;
                        $maxLength = $tipo?->getMaxLength() ?? 8;
                        
                        $regex = $isNumeric ? '/[^0-9]/g' : '/[^a-zA-Z0-9]/g';
                        
                        return [
                            'oninput' => "this.value = this.value.replace($regex, '').slice(0, $maxLength)",
                        ];
                    }),
            ]);
    }
}
