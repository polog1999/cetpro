<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700 max-w-2xl">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Censo Estadístico Anual</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Genera el reporte en formato Excel consolidado</p>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Seleccionar Año Académico</label>
            <select wire:model.live="anio" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500">
                @foreach ($this->anios as $val)
                    <option value="{{ $val }}">{{ $val }}</option>
                @endforeach
            </select>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 flex justify-end">
            <a 
                href="{{ route('reportes.censo.descargar', ['anio' => $anio]) }}"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow inline-flex items-center transition"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Descargar Excel de Censo
            </a>
        </div>
    </div>
</x-filament-panels::page>