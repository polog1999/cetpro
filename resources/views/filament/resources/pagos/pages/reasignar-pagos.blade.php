<x-filament-panels::page>
    <style>
        .reasignar-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .reasignar-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>

    {{-- Alerta informativa --}}
    <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 p-4 flex items-start gap-3">
        <div class="flex-shrink-0 mt-0.5">
            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500" />
        </div>
        <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">¡Atención!</p>
            <p class="text-amber-700 dark:text-amber-400 text-sm mt-0.5">Solo puedes intercambiar liquidaciones con igual monto.</p>
        </div>
    </div>

    {{-- Dos bloques lado a lado (responsive) --}}
    <div class="reasignar-grid">
        {{-- BLOQUE PAGO 1 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="bg-primary-50 dark:bg-primary-950/30 border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                <h3 class="text-sm font-semibold text-primary-700 dark:text-primary-400 uppercase tracking-wide">Pago 1</h3>
            </div>
            <div class="p-6 space-y-5">
                <div class="mb-2">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.500ms="liquidacion_1"
                            placeholder="Ingrese el nro. de liquidación"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-24 flex-shrink-0 pt-0.5">Estado:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">
                            @if($this->pago1)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $this->pago1['estado'] === 'Pagado' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' }}">
                                    {{ $this->pago1['estado'] ?? 'Desconocido' }}
                                </span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400 text-xs">(S/ {{ number_format($this->pago1['monto'] ?? 0, 2) }})</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic text-xs">Sin datos</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-24 flex-shrink-0 pt-0.5">Programa:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100 break-words">
                            @if($this->pago1)
                                {{ $this->pago1['corresponde_a'] }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic text-xs">Sin datos</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- BLOQUE PAGO 2 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="bg-danger-50 dark:bg-danger-950/30 border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                <h3 class="text-sm font-semibold text-danger-700 dark:text-danger-400 uppercase tracking-wide">Pago 2</h3>
            </div>
            <div class="p-6 space-y-5">
                <div class="mb-2">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.500ms="liquidacion_2"
                            placeholder="Ingrese el nro. de liquidación"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-24 flex-shrink-0 pt-0.5">Estado:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">
                            @if($this->pago2)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $this->pago2['estado'] === 'Pagado' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' }}">
                                    {{ $this->pago2['estado'] ?? 'Desconocido' }}
                                </span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400 text-xs">(S/ {{ number_format($this->pago2['monto'] ?? 0, 2) }})</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic text-xs">Sin datos</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-24 flex-shrink-0 pt-0.5">Programa:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100 break-words">
                            @if($this->pago2)
                                {{ $this->pago2['corresponde_a'] }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic text-xs">Sin datos</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Botón Intercambiar --}}
    <div class="flex justify-center mt-8">
        <button
            wire:click="intercambiar"
            type="button"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-base rounded-xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 shadow-lg shadow-primary-500/25"
        >
            <span wire:loading.remove wire:target="intercambiar">
                <x-heroicon-s-arrows-right-left class="w-5 h-5" />
            </span>
            <span wire:loading.remove wire:target="intercambiar">Intercambiar</span>
            <span wire:loading wire:target="intercambiar">
                <x-filament::loading-indicator class="w-5 h-5" />
            </span>
            <span wire:loading wire:target="intercambiar">Procesando...</span>
        </button>
    </div>
</x-filament-panels::page>
