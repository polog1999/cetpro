<div>
    @if($cronograma)
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Detalle de Pagos</h3>
            <x-filament::button
                tag="a"
                href="{{ route('matriculas.cronograma-pdf', ['matricula' => $cronograma->matricula_id]) }}"
                target="_blank"
                icon="heroicon-o-arrow-down-tray"
                color="primary"
                size="sm"
            >
                Descargar PDF
            </x-filament::button>
        </div>

        <div class="mb-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-bold text-gray-500">Monto Total:</span>
                    <span class="font-medium">S/. {{ number_format($cronograma->monto_total, 2) }}</span>
                </div>
                <div>
                    <span class="font-bold text-gray-500">Saldo Pendiente:</span>
                    <span class="font-medium text-red-600">S/. {{ number_format($pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'pendiente'))->sum('monto'), 2) }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-xl">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nro de recibo</th>
                        <th scope="col" class="px-6 py-3">Nro Liquidación</th>
                        <th scope="col" class="px-6 py-3">Vencimiento</th>
                        <th scope="col" class="px-6 py-3">Monto</th>
                        <th scope="col" class="px-6 py-3">Estado</th>
                        <th scope="col" class="px-6 py-3">Fecha Pago</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $pago)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $pago->nro_cuota }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $pago->num_liquidacion ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $pago->fecha_vencimiento?->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                S/. {{ number_format($pago->monto, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $estado = strtolower($pago->estado ?? '');
                                    $color = match(true) {
                                        str_contains($estado, 'pagado') || str_contains($estado, 'cancelado') => 'text-green-600 bg-green-100',
                                        str_contains($estado, 'pendiente') => 'text-yellow-600 bg-yellow-100',
                                        str_contains($estado, 'vencido') => 'text-red-600 bg-red-100',
                                        str_contains($estado, 'anulado') => 'text-gray-600 bg-gray-100',
                                        default => 'text-gray-600 bg-gray-100'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $color }}">
                                    {{ $pago->estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $pago->fecha_pago?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center">No hay recibos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="p-4 text-center text-gray-500">
            No se ha generado un cronograma para esta matrícula.
        </div>
    @endif
</div>
