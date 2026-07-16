<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sección de selectores --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Seleccionar Programa, Curso y Horario
            </h2>

            {{-- MODIFICADO: Grid dinámico (5 columnas si es programa de estudio, 4 columnas para formación continua) --}}
            <div class="grid grid-cols-1 {{ $this->esProgramaEstudio() ? 'md:grid-cols-5' : 'md:grid-cols-4' }} gap-4">
                {{-- Selector de Tipo de Programa --}}
                <div>
                    <label for="tipo_programa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Tipo de Programa
                    </label>
                    <select wire:model.live="tipo_programa" id="tipo_programa"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">-- Seleccionar tipo --</option>
                        @foreach ($this->tiposPrograma as $label)
                            {{-- Evaluamos si $label es objeto Enum o string --}}
                            @php
                                $val = is_object($label) && method_exists($label, 'value') ? $label->value : $label;
                                $name =
                                    is_object($label) && method_exists($label, 'name')
                                        ? ($label->name === 'PROGRAMA_ESTUDIO'
                                            ? 'Programa de Estudio'
                                            : 'Formación Continua')
                                        : $label;
                            @endphp
                            <option value="{{ $val }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Programa / Formación Continua --}}
                <div>
                    <label for="programa_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Programa / Formación Continua
                    </label>
                    <select wire:model.live="programa_id" id="programa_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if (!$tipo_programa) disabled @endif>
                        <option value="">-- Buscar programa --</option>
                        @foreach ($this->programas as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Curso / Módulo --}}
                <div>
                    <label for="curso_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Curso / Módulo
                    </label>
                    <select wire:model.live="curso_id" id="curso_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if (!$programa_id) disabled @endif>
                        <option value="">-- Seleccionar curso --</option>
                        @foreach ($this->cursos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- NUEVO: Selector de Unidad Didáctica (Solo se renderiza si es Programa de Estudio) --}}
                @if ($this->esProgramaEstudio())
                    <div>
                        <label for="unidad_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Unidad Didáctica
                        </label>
                        <select wire:model.live="unidad_id" id="unidad_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            @if (!$curso_id) disabled @endif>
                            <option value="">-- Seleccionar unidad --</option>
                            @foreach ($this->unidades as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Selector de Horario --}}
                <div>
                    <label for="horario_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Horario Asignado
                    </label>
                    <select wire:model.live="horario_id" id="horario_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if (!$programa_id) disabled @endif>
                        <option value="">-- Seleccionar horario --</option>
                        @foreach ($this->horarios as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Mensaje de error si no hay horarios --}}
            @if ($programa_id && !$this->tieneHorarios)
                <div
                    class="mt-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="text-amber-700 dark:text-amber-300 font-medium">
                            Ningún curso u horario asignado. Revisar información.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tabla de estudiantes y notas --}}
        @if ($horario_id && $curso_id && $this->estudiantes->isNotEmpty())
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Estudiantes Matriculados
                    </h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->estudiantes->count() }} estudiante(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Apellidos y Nombres</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                    DNI</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                                    Nota</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->estudiantes as $index => $estudiante)
                                {{-- MODIFICADO: Se agregó wire:key para evitar que las notas se crucen entre horarios --}}
                                <tr wire:key="estudiante-{{ $estudiante['matricula_id'] }}"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $estudiante['nombre_completo'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ $estudiante['dni'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if (!$this->puedeGuardarNotas())
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $estudiante['ya_tiene_nota'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                                {{ $estudiante['ya_tiene_nota'] ? intval($estudiante['nota_actual']) : '--' }}
                                            </span>
                                        @else
                                            <input type="text"
                                                wire:model.blur="notas.{{ $estudiante['matricula_id'] }}"
                                                maxlength="2" inputmode="numeric"
                                                data-nota-index="{{ $index }}"
                                                class="nota-input w-16 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-lg font-semibold"
                                                placeholder="--" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Sección de Botones --}}
                @if ($this->puedeGuardarNotas())
                    <div x-data="{ showConfirm: false }"
                        class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col items-end gap-3">
                        <div class="flex justify-end gap-3" x-show="!showConfirm">
                            <button wire:click="cancelar" type="button"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancelar
                            </button>
                            <button @click="showConfirm = true" type="button"
                                style="background-color: #16a34a; color: white;"
                                class="px-6 py-2 font-medium rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800 transition-colors">
                                Guardar Notas
                            </button>
                        </div>

                        {{-- Mensaje de confirmación --}}
                        <div x-show="showConfirm" x-cloak x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="w-full max-w-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 text-right">
                            <p class="text-sm text-amber-800 dark:text-amber-200 mb-3 font-medium">
                                ⚠️ Una vez guardadas, las notas se registrarán permanentemente. <br>
                                Podrá editarlas después si es necesario, pero quedará registro.
                            </p>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirm = false" type="button"
                                    class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button wire:click="guardarNotas" wire:loading.attr="disabled" type="button"
                                    style="background-color: #16a34a; color: white;"
                                    class="px-4 py-1.5 text-sm font-medium rounded hover:bg-green-700 shadow-sm">
                                    <span wire:loading.remove wire:target="guardarNotas">Sí, Confirmar Guardado</span>
                                    <span wire:loading wire:target="guardarNotas">Guardando...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @elseif($horario_id && $curso_id && $this->estudiantes->isEmpty())
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 border border-gray-200 dark:border-gray-700 text-center">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">
                    No hay estudiantes matriculados en este horario.
                </p>
            </div>
        @endif
    </div>

    {{-- Script para auto-avance de campos --}}
    @push('scripts')
        <script>
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('nota-input')) {
                    // Solo permitir números
                    let val = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = val;

                    if (val.length >= 2) {
                        const currentIndex = parseInt(e.target.dataset.notaIndex);
                        const nextInput = document.querySelector(`[data-nota-index="${currentIndex + 1}"]`);
                        if (nextInput) {
                            nextInput.focus();
                            nextInput.select();
                        }
                    }
                }
            });
        </script>
    @endpush
</x-filament-panels::page>
