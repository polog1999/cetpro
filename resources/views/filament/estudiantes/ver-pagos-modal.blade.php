@if($estudiante->matriculas->isEmpty())
    <div class="text-center py-8">
        <div class="text-gray-400 mb-2">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <p class="text-gray-500 dark:text-gray-400">Este estudiante no tiene matrículas registradas.</p>
    </div>
@else
    <div class="space-y-6">
        @foreach($estudiante->matriculas as $index => $matricula)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                {{-- Encabezado de la matrícula --}}
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                <span class="text-blue-600 dark:text-blue-400">Matrícula #{{ $index + 1 }}:</span> 
                                {{ $matricula->codigo_inscripcion }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Programa:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">
                                        {{ $matricula->horario?->programa?->nombre_programa ?? $matricula->curso?->nombre_curso ?? 'N/A' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Estado:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-1
                                        @if($matricula->estado->value === 'enproceso') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($matricula->estado->value === 'interrumpido') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($matricula->estado->value === 'culminado') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ $matricula->estado->getLabel() }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Tipo:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">
                                        {{ $matricula->tipo_matricula->getLabel() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contenido: Tabla de pagos --}}
                <div class="p-4">
                    @if($matricula->cronograma && $matricula->cronograma->pagos->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Cuota
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Código
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Monto
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Vencimiento
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Fecha Pago
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($matricula->cronograma->pagos as $pago)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                Cuota {{ $pago->nro_cuota }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                {{ $pago->num_liquidacion }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                                S/. {{ number_format($pago->monto, 2) }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $pago->fecha_vencimiento->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                @php
                                                    $estado = strtolower($pago->estado ?? '');
                                                @endphp
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    @if(str_contains($estado, 'pagado') || str_contains($estado, 'cancelado')) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @elseif(str_contains($estado, 'vencido')) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @elseif(str_contains($estado, 'pendiente')) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                                    @endif">
                                                    {{ ucfirst($pago->estado) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $pago->fecha_pago?->format('d/m/Y') ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">
                                            Total del Cronograma
                                        </td>
                                        <td class="px-4 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">
                                            S/. {{ number_format($matricula->cronograma->monto_total, 2) }}
                                        </td>
                                        <td colspan="3" class="px-4 py-3">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-medium">{{ $matricula->cronograma->num_cuotas }}</span> cuota(s) en total
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                Sin cronograma de pagos generado para esta matrícula
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
