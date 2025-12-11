<?php

namespace App\Filament\Resources\Apoderados\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

use App\Enums\TipoDocumento;

class ApoderadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_documento')
                    ->options(TipoDocumento::class)
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('nro_documento_component')
                        ->state(null)
                    ),
                TextInput::make('nro_documento')
                    ->key('nro_documento_component')
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
                TextInput::make('apellido_paterno')
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
                TextInput::make('nombres')
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('telefono')
                    ->tel()
                    ->numeric()
                    ->maxLength(9)
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"]),
            ]);
    }
}
