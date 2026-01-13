@extends('portal.layouts.app')

@section('title', 'Mis Pagos')

@section('content')
<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Mis Pagos</h1>
    <p class="mt-1 text-slate-600">Detalle de cuotas y estado de pagos</p>
</div>

@if($pagos->isEmpty())
    <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
        <p class="text-slate-500">No tienes pagos registrados</p>
    </div>
@else
    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @php
            $totalPagado = $pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'cancelado'))->sum('monto');
            $totalPendiente = $pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'pendiente'))->sum('monto');
            $totalVencido = $pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'vencido'))->sum('monto');
        @endphp
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Total Pagado</p>
            <p class="mt-1 text-xl font-semibold text-green-600">S/ {{ number_format($totalPagado, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Pendiente</p>
            <p class="mt-1 text-xl font-semibold text-amber-600">S/ {{ number_format($totalPendiente, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Vencido</p>
            <p class="mt-1 text-xl font-semibold text-red-600">S/ {{ number_format($totalVencido, 2) }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Cuota
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        N° Liquidación
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Programa
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Monto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Vencimiento
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Estado
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @foreach($pagos as $pago)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-900">{{ $pago->nro_cuota }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($pago->num_liquidacion)
                                <span class="text-sm font-mono text-slate-700 bg-slate-100 px-2 py-1 rounded">{{ $pago->num_liquidacion }}</span>
                            @else
                                <span class="text-sm text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $pago->cronograma?->matricula?->horario?->programa?->nombre_programa ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-medium text-slate-900">S/ {{ number_format($pago->monto, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $pago->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $estadoLower = strtolower($pago->estado ?? '');
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ str_contains($estadoLower, 'cancelado') ? 'bg-green-100 text-green-800' : 
                                   (str_contains($estadoLower, 'pendiente') ? 'bg-amber-100 text-amber-800' : 
                                   (str_contains($estadoLower, 'vencido') ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800')) }}">
                                {{ ucfirst($pago->estado ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
