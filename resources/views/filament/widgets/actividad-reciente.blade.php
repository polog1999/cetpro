<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon 
                    icon="heroicon-o-clock" 
                    class="h-5 w-5 text-primary-500"
                />
                Actividad Reciente
            </div>
        </x-slot>
        
        <x-slot name="description">
            Últimos movimientos en el sistema
        </x-slot>

        <div class="relative">
            {{-- Timeline Line --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary-500 via-gray-200 to-transparent dark:via-gray-700"></div>
            
            <div class="space-y-4">
                @forelse($actividades as $actividad)
                    <div class="relative flex items-start gap-4 pl-10 group">
                        {{-- Timeline Dot --}}
                        <div class="absolute left-2 top-2 flex items-center justify-center">
                            <div @class([
                                'w-5 h-5 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-gray-900 transition-transform group-hover:scale-110',
                                'bg-success-500' => $actividad->tipo === 'Pago registrado',
                                'bg-info-500' => $actividad->tipo === 'Matrícula creada',
                            ])>
                                @if($actividad->tipo === 'Pago registrado')
                                    <x-filament::icon icon="heroicon-m-banknotes" class="h-3 w-3 text-white" />
                                @else
                                    <x-filament::icon icon="heroicon-m-academic-cap" class="h-3 w-3 text-white" />
                                @endif
                            </div>
                        </div>
                        
                        {{-- Content Card --}}
                        <div class="flex-1 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-primary-500/50 dark:hover:ring-primary-500/50 transition-all duration-200 hover:shadow-md">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full',
                                        'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => $actividad->tipo === 'Pago registrado',
                                        'bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400' => $actividad->tipo === 'Matrícula creada',
                                    ])>
                                        {{ $actividad->tipo }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $actividad->descripcion }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <x-filament::icon icon="heroicon-m-user" class="h-3.5 w-3.5" />
                                        {{ $actividad->usuario }}
                                    </span>
                                    <span class="flex items-center gap-1" title="{{ \Carbon\Carbon::parse($actividad->fecha)->format('d/m/Y H:i') }}">
                                        <x-filament::icon icon="heroicon-m-clock" class="h-3.5 w-3.5" />
                                        {{ \Carbon\Carbon::parse($actividad->fecha)->locale('es')->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pl-10 py-8">
                        <div class="flex flex-col items-center justify-center text-center">
                            <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-4 mb-3">
                                <x-filament::icon icon="heroicon-o-inbox" class="h-8 w-8 text-gray-400" />
                            </div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                No hay actividad reciente
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Las actividades aparecerán aquí cuando se registren pagos o matrículas
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

