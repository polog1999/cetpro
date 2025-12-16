<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones del Día
        </x-slot>
        
        <x-slot name="description">
            Tareas pendientes que requieren atención
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($acciones as $accion)
                <a href="{{ $accion['url'] }}" 
                   class="block p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-primary-500 dark:hover:ring-primary-600">
                    <div class="flex items-center gap-3">
                        <div @class([
                            'p-3 rounded-full',
                            'bg-danger-100 dark:bg-danger-900/20' => $accion['color'] === 'danger',
                            'bg-warning-100 dark:bg-warning-900/20' => $accion['color'] === 'warning',
                            'bg-info-100 dark:bg-info-900/20' => $accion['color'] === 'info',
                            'bg-gray-100 dark:bg-gray-900/20' => $accion['color'] === 'gray',
                        ])>
                            <x-filament::icon
                                :icon="$accion['icono']"
                                @class([
                                    'w-6 h-6',
                                    'text-danger-600 dark:text-danger-400' => $accion['color'] === 'danger',
                                    'text-warning-600 dark:text-warning-400' => $accion['color'] === 'warning',
                                    'text-info-600 dark:text-info-400' => $accion['color'] === 'info',
                                    'text-gray-600 dark:text-gray-400' => $accion['color'] === 'gray',
                                ])
                            />
                        </div>
                        
                        <div class="flex-1">
                            <div class="text-2xl font-bold">
                                {{ number_format($accion['contador']) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $accion['titulo'] }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
