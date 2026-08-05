<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sección de selectores --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Seleccionar Parámetros de Calificación
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                {{-- Selector de Tipo de Programa --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Programa</label>
                    <select wire:model.live="tipo_programa" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($this->tiposPrograma as $label)
                            @php
                                $val = is_object($label) && method_exists($label, 'value') ? $label->value : $label;
                                $name = is_object($label) && method_exists($label, 'name') ? ($label->name === 'PROGRAMA_ESTUDIO' ? 'Programa de Estudio' : 'Formación Continua') : $label;
                            @endphp
                            <option value="{{ $val }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Programa / Formación Continua --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Programa</label>
                    <select wire:model.live="programa_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" @if (!$tipo_programa) disabled @endif>
                        <option value="">-- Seleccionar --</option>
                        @foreach ($this->programas as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- MÓDULO: Solo aparece si es Programa de Estudio --}}
                @if ($this->esProgramaEstudio())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Módulo</label>
                        <select wire:model.live="curso_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" @if (!$programa_id) disabled @endif>
                            <option value="">-- Seleccionar módulo --</option>
                            @foreach ($this->cursos as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- UNIDAD DIDÁCTICA (Aparece tanto para Programa como Formación Continua filtrado de la nueva tabla) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unidad Didáctica</label>
                    <select wire:model.live="unidad_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" @if ($this->esProgramaEstudio() && !$curso_id) disabled @elseif(!$programa_id) disabled @endif>
                        <option value="">-- Seleccionar unidad --</option>
                        @foreach ($this->unidades as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Horario --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Horario Asignado</label>
                    <select wire:model.live="horario_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" @if (!$programa_id) disabled @endif>
                        <option value="">-- Seleccionar grupo --</option>
                        @foreach ($this->horarios as $id => $desc)
                            <option value="{{ $id }}">{{ $desc }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabla de estudiantes y notas --}}
        @if ($horario_id && $unidad_id && $this->estudiantes->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Estudiantes Matriculados</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $this->estudiantes->count() }} estudiante(s)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Apellidos y Nombres</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">DNI</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">Nota</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->estudiantes as $index => $estudiante)
                                <tr wire:key="estudiante-{{ $estudiante['matricula_id'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900 dark:text-white">{{ $estudiante['nombre_completo'] }}</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $estudiante['dni'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if (!$this->puedeGuardarNotas())
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $estudiante['ya_tiene_nota'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                                {{ $estudiante['ya_tiene_nota'] ? intval($estudiante['nota_actual']) : '--' }}
                                            </span>
                                        @else
                                            <input type="text" wire:model.blur="notas.{{ $estudiante['matricula_id'] }}" maxlength="2" inputmode="numeric" data-nota-index="{{ $index }}" class="nota-input w-16 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-lg font-semibold" placeholder="--" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($this->puedeGuardarNotas())
                    <div x-data="{ showConfirm: false }" class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col items-end gap-3">
                        <div class="flex justify-end gap-3" x-show="!showConfirm">
                            <button wire:click="cancelar" type="button" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</button>
                            <button @click="showConfirm = true" type="button" style="background-color: #16a34a; color: white;" class="px-6 py-2 font-medium rounded-lg hover:bg-green-700 transition-colors">Guardar Notas</button>
                        </div>
                        <div x-show="showConfirm" x-cloak class="w-full max-w-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 text-right">
                            <p class="text-sm text-amber-800 dark:text-amber-200 mb-3 font-medium">⚠️ Una vez guardadas, las notas se registrarán permanentemente.</p>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirm = false" type="button" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50">Cancelar</button>
                                <button wire:click="guardarNotas" wire:loading.attr="disabled" type="button" style="background-color: #16a34a; color: white;" class="px-4 py-1.5 text-sm font-medium rounded hover:bg-green-700 shadow-sm"><span wire:loading.remove wire:target="guardarNotas">Sí, Confirmar Guardado</span><span wire:loading wire:target="guardarNotas">Guardando...</span></button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
      @push('scripts')
    <script>
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('nota-input')) {
                // Solo permitir números
                let val = e.target.value.replace(/[^0-9]/g, '');
                
                // Limitar a máximo 20 (opcional, por si escriben 99)
                if (parseInt(val) > 20) {
                    val = '20';
                }
                
                e.target.value = val;
                
                // Si tiene 2 caracteres, pasar automáticamente al siguiente input de abajo
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

        // Soporte para saltar a la fila de abajo presionando la tecla ENTER
        document.addEventListener('keydown', function(e) {
            if (e.target.classList.contains('nota-input') && e.key === 'Enter') {
                e.preventDefault();
                const currentIndex = parseInt(e.target.dataset.notaIndex);
                const nextInput = document.querySelector(`[data-nota-index="${currentIndex + 1}"]`);
                if (nextInput) {
                    nextInput.focus();
                    nextInput.select();
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>