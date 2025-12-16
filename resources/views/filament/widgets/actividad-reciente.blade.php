<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Actividad Reciente
        </x-slot>
        
        <x-slot name="description">
            Últimos movimientos en el sistema
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Tipo</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Descripción</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Usuario</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($actividades as $actividad)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                    'bg-success-100 text-success-700 dark:bg-success-900/20 dark:text-success-400' => $actividad->tipo === 'Pago registrado',
                                    'bg-info-100 text-info-700 dark:bg-info-900/20 dark:text-info-400' => $actividad->tipo === 'Matrícula creada',
                                ])>
                                    {{ $actividad->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                {{ $actividad->descripcion }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $actividad->usuario }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($actividad->fecha)->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay actividad reciente
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
