<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Filtros de Búsqueda</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione los parámetros para generar el documento oficial.</p>
        </div>

        @php
            // Ayudante visual para determinar el tipo de programa seleccionado en tiempo real
            $esProgramaEstudio = false;
            if ($tipo_programa) {
                $val = is_object($tipo_programa) ? $tipo_programa->value : $tipo_programa;
                $esProgramaEstudio = ($val === 'PROGRAMA_ESTUDIO' || $val === \App\Enums\TipoPrograma::PROGRAMA_ESTUDIO->value);
            }
            
            // Lógica para saber si los botones deben estar habilitados
            // Si es programa, exige curso_id. Si es formación continua, con el horario_id basta.
            $datosCompletos = false;
            if ($anio && $horario_id) {
                $datosCompletos = $esProgramaEstudio ? !empty($curso_id) : true;
            }
            
            // Para la ruta, si es formacion continua (sin curso_id), pasamos 0 o un flag para que el route no rompa.
            $paramCurso = $curso_id ? $curso_id : 0; 
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            {{-- Año Académico --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Año Académico</label>
                <select wire:model.live="anio" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500">
                    @foreach ($this->anios as $val)
                        <option value="{{ $val }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo de Programa --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                <select wire:model.live="tipo_programa" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($this->tiposPrograma as $tipo)
                        @php
                            $val = is_object($tipo) ? $tipo->value : $tipo;
                            $nom = is_object($tipo) ? $tipo->name : $tipo;
                        @endphp
                        <option value="{{ $val }}">{{ str_replace('_', ' ', $nom) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Programa / Formación Continua --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Programa / Formación</label>
                <select wire:model.live="programa_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500" @if (!$tipo_programa) disabled @endif>
                    <option value="">-- Seleccionar --</option>
                    @foreach ($this->programas as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Módulo (Oculto si es Formación Continua) --}}
            @if ($esProgramaEstudio)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Módulo</label>
                    <select wire:model.live="curso_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500" @if (!$programa_id) disabled @endif>
                        <option value="">-- Seleccionar módulo --</option>
                        @foreach ($this->cursos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Horario --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Horario Asignado</label>
                <select wire:model.live="horario_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500" @if (!$programa_id) disabled @endif>
                    <option value="">-- Seleccionar grupo --</option>
                    @foreach ($this->horarios as $id => $desc)
                        <option value="{{ $id }}">{{ $desc }}</option>
                    @endforeach
                </select>
            </div>
        </div>

       {{-- BOTONES DE DESCARGA --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 flex justify-end items-center h-16">
            
            {{-- Indicador de carga --}}
            <div wire:loading class="text-sm text-gray-500 dark:text-gray-400 mr-4 font-semibold animate-pulse">
                Actualizando datos...
            </div>

            {{-- Contenedor de los botones --}}
            <div wire:loading.remove class="flex gap-4">
                
                {{-- Botón Nómina --}}
                <a 
                    href="{{ $datosCompletos ? route('reportes.nomina.stream', ['horario_id' => $horario_id, 'anio' => $anio, 'curso_id' => $paramCurso]) : '#' }}"
                    target="_blank"
                    style="background-color: #16a34a !important; color: white !important;"
                    class="{{ !$datosCompletos ? 'opacity-50 pointer-events-none' : '' }} inline-flex items-center justify-center px-4 py-2 font-bold rounded-lg shadow transition duration-200 hover:bg-green-700"
                >
                    Ver Nómina de Matrícula (PDF)
                </a>
                
                {{-- Botón Acta --}}
                <a 
                    href="{{ $datosCompletos ? route('reportes.acta.stream', ['horario_id' => $horario_id, 'anio' => $anio, 'curso_id' => $paramCurso]) : '#' }}"
                    target="_blank"
                    style="background-color: #2563eb !important; color: white !important;"
                    class="{{ !$datosCompletos ? 'opacity-50 pointer-events-none' : '' }} inline-flex items-center justify-center px-4 py-2 font-bold rounded-lg shadow transition duration-200 hover:bg-blue-700"
                >
                    Ver Acta de Evaluación (Word)
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>