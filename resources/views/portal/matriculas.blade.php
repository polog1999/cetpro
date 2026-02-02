@extends('portal.layouts.app')

@section('title', 'Mis Matrículas')

@section('content')
<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Mis Matrículas</h1>
    <p class="mt-1 text-slate-600">Historial de matrículas en programas y cursos</p>
</div>

@if($matriculas->isEmpty())
    <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
        <p class="text-slate-500">No tienes matrículas registradas</p>
    </div>
@else
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Programa / Curso
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Nro de recibos
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @foreach($matriculas as $matricula)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900">
                                @if($matricula->tipo_matricula?->value === 'curso' || $matricula->tipo_matricula?->value === 'modulo')
                                    {{ $matricula->curso?->nombre ?? 'Curso no asignado' }}
                                @else
                                    {{ $matricula->horario?->programa?->nombre_programa ?? 'Programa no asignado' }}
                                @endif
                            </div>
                            @if($matricula->tipo_matricula?->value === 'curso' || $matricula->tipo_matricula?->value === 'modulo')
                                <div class="text-sm text-slate-500">
                                    {{ $matricula->curso?->programa?->nombre_programa ?? '' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-slate-600">
                                {{ ucfirst(str_replace('_', ' ', $matricula->tipo_matricula?->value ?? 'programa')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $matricula->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $matricula->estado->value === 'completado' ? 'bg-green-100 text-green-800' : 
                                   ($matricula->estado->value === 'en_proceso' ? 'bg-blue-100 text-blue-800' : 
                                   ($matricula->estado->value === 'anulado' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800')) }}">
                                {{ ucfirst(str_replace('_', ' ', $matricula->estado->value ?? 'N/A')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($matricula->cronograma && $matricula->cronograma->pagos->count() > 0)
                                <div class="flex items-center space-x-1">
                                    @php
                                        $pagados = $matricula->cronograma->pagos->filter(fn($p) => str_contains(strtolower($p->estado ?? ''), 'cancelado'))->count();
                                        $total = $matricula->cronograma->pagos->count();
                                    @endphp
                                    <span class="text-sm text-slate-600">{{ $pagados }}/{{ $total }}</span>
                                    <div class="w-16 bg-slate-200 rounded-full h-1.5">
                                        <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ ($pagados / $total) * 100 }}%"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
