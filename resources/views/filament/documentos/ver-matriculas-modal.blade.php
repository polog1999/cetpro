@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4">
    @if($matriculas->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-document class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>Este estudiante no tiene matrículas registradas.</p>
        </div>
    @else
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Programa / Curso</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Certificado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($matriculas as $matricula)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 font-medium">
                            {{ $matricula->codigo_inscripcion }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $matricula->curso?->nombre_curso ?? $matricula->horario?->programa?->nombre_programa ?? 'Sin programa' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                @switch($matricula->tipo_matricula->value)
                                    @case('Programa') bg-blue-100 text-blue-800 @break
                                    @case('Curso') bg-green-100 text-green-800 @break
                                    @case('Módulo') bg-yellow-100 text-yellow-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch
                            ">
                                {{ $matricula->tipo_matricula->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                {{ $matricula->estado->getColor() === 'success' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $matricula->estado->getColor() === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $matricula->estado->getColor() === 'danger' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $matricula->estado->getColor() === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}
                            ">
                                {{ $matricula->estado->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($matricula->documento_path && Storage::disk('public')->exists($matricula->documento_path))
                                <span class="inline-flex items-center gap-1 text-green-600">
                                    <x-heroicon-s-check-circle class="w-5 h-5" />
                                    @if($matricula->tipo_certificado)
                                        {{ $matricula->tipo_certificado->getLabel() }}
                                    @else
                                        Subido
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-gray-400">
                                    <x-heroicon-o-document class="w-5 h-5" />
                                    Sin documento
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($matricula->documento_path && Storage::disk('public')->exists($matricula->documento_path))
                                <a href="{{ Storage::disk('public')->url($matricula->documento_path) }}" 
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    Descargar
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
