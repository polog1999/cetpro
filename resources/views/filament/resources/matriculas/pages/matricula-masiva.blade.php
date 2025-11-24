<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulario de selección de sección --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                Seleccionar Sección
            </h3>
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        {{-- Tabla de estudiantes --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                Estudiantes Disponibles
            </h3>
            @if(!$this->seccionSeleccionada)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800 dark:text-yellow-200 font-medium text-sm">
                        ⚠️ Seleccione una sección para habilitar la matrícula masiva
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Seleccione los estudiantes que desea matricular marcando los checkboxes y luego haga clic en "Matricular seleccionados"
                </p>
            @endif
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
