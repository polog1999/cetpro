<?php

namespace App\Filament\Resources\Apoderados\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

use App\Enums\TipoDocumento;
use App\Models\Apoderado;
use App\Services\PideService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ApoderadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_documento')
                    ->default(TipoDocumento::DNI)
                    ->options(TipoDocumento::class)
                    ->live()
                    
                    ->afterStateUpdated(
                        function (Select $component, Set $set) {
                            // Al cambiar el número, reseteamos los campos de identidad
                            $set('numero_documento', null);
                            $set('nombres', null);
                            $set('apellido_paterno', null);
                            $set('apellido_materno', null);
                            return $component
                                ->getContainer()
                                ->getComponent('nro_documento_component')
                                ->state(null);
                        }
                    )
                    ->required(),
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
                    })
                    ->suffixActions(
                        [
                            self::botonBuscarPersona()
                        ]
                    )
                    ->required(),
                TextInput::make('apellido_paterno')
                // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                    ->extraAttributes([
                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                    ])
                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                    ->trim()

                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                    'style' => 'text-transform: uppercase'])
                    ->required()
                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),
                TextInput::make('apellido_materno')
                // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                    ->extraAttributes([
                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                    ])
                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                    ->trim()

                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                    'style' => 'text-transform: uppercase'])
                    ->required()
                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),
                TextInput::make('nombres')
                // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                    ->extraAttributes([
                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                    ])
                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                    ->trim()

                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                    'style' => 'text-transform: uppercase'])
                    ->required()
                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),
                TextInput::make('telefono')
                    ->tel()
                    ->numeric()
                    ->maxLength(9)
                    ->required()
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"]),
            ]);
    }
    protected static function botonBuscarPersona()
    {
        return Action::make('buscar_persona')
            ->color('success')
            ->icon('heroicon-m-magnifying-glass')
            ->extraAttributes([
                // Forzamos al texto/ícono a ser verde y usamos !important (text-success-600)
                'class' => '[&_.fi-icon]:!text-success-600 dark:[&_.fi-icon]:!text-success-400',
            ])
            ->visible(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI)
            ->action(function ($state, Set $set, Get $get) {
                if (!$state) return;

                if (strlen($state) === 8) {

                    $persona = Apoderado::where('nro_documento', $state)->first();

                      if ($persona) {
                    
                        $set('nombres', null);
                        $set('apellido_paterno', null);
                        $set('apellido_materno', null);
                      
                        Notification::make()
                            ->title('Ya se encuentra registrado')
                            ->success()
                            ->send();
                        return;
                    }


                    // 3. Si no existe en BD, Consultar al PIDE
                    // Supongamos que tienes un Service: PideService::consultar($dni)
                    $datosPide = PideService::ws_reniec($state);

                    if ($datosPide['codResu'] === '0000') {
                        $set('pide_fallo', false); // Activamos edición manual
                        $set('nombres', $datosPide['nombre']);
                        $set('apellido_paterno', $datosPide['paterno']);
                        $set('apellido_materno', $datosPide['materno']);
                        // $set('foto_url', '/uploads/foto_dni/' . $state . '.png');
                        Notification::make()
                            ->title('Datos del PIDE')
                            ->body('Se consumió el PIDE')
                            ->success()
                            ->send();
                    } else {
                        $datosApiPeru = PideService::apiPeruDni($state);

                        if ($datosApiPeru['success']) {
                            // dd('probando');
                            $set('pide_fallo', false); // Activamos edición manual
                            $set('nombres', $datosApiPeru['data']['nombres']);
                            $set('apellido_paterno', $datosApiPeru['data']['apellido_paterno']);
                            $set('apellido_materno', $datosApiPeru['data']['apellido_materno']);
                            Notification::make()
                                ->title('Datos del ApisPeru')
                                ->body('Se consumió el ApisPeru')
                                ->success()
                                ->send();
                        } else {
                            $datosApisNet = PideService::apisNet($state);

                            if ($datosApisNet['success']) {
                                $set('pide_fallo', false); // Activamos edición manual
                                $set('nombres', $datosApisNet['nombres']);
                                $set('apellido_paterno', $datosApisNet['apellidoPaterno']);
                                $set('apellido_materno', $datosApisNet['apellidoMaterno']);
                                Notification::make()
                                    ->title('Datos de ApisNet')
                                    ->body('Se consumió el ApisNet')
                                    ->success()
                                    ->send();
                            } else {
                                // FALLÓ EL PIDE
                                $set('pide_fallo', true); // Activamos edición manual
                                $set('nombres', null);
                                $set('apellido_paterno', null);
                                $set('apellido_materno', null);
                                $set('foto_url', null);
                                Notification::make()
                                    ->title('PIDE no disponible')
                                    ->body('Complete los datos manualmente.')
                                    ->warning()
                                    ->send();
                            }
                        }
                    }
                } else {
                    Notification::make()
                        ->title('Alerta')
                        ->body('El DNI debe tener 8 dígitos')
                        ->warning()
                        ->send();
                }
            });
    }
}
