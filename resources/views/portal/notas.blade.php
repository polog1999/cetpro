@extends('portal.layouts.app')

@section('title', 'Mis Notas')

@section('content')
<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Mis Notas</h1>
    <p class="mt-1 text-slate-600">Registro de calificaciones por módulo</p>
</div>

@if($notas->isEmpty())
    <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
        <p class="text-slate-500">No tienes notas registradas</p>
    </div>
@else
    <!-- Summary -->
    @php
        $aprobadas = $notas->filter(fn($n) => in_array($n->nota_letra?->value, ['AD', 'A', 'B']))->count();
        $total = $notas->count();
        $porcentaje = $total > 0 ? round(($aprobadas / $total) * 100) : 0;
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Módulos Aprobados</p>
            <p class="mt-1 text-2xl font-semibold text-green-600">{{ $aprobadas }}/{{ $total }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Porcentaje de Aprobación</p>
            <p class="mt-1 text-2xl font-semibold {{ $porcentaje >= 50 ? 'text-green-600' : 'text-amber-600' }}">{{ $porcentaje }}%</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Total Evaluaciones</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $total }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Programa / Módulo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Calificación
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                        Fecha
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @foreach($notas as $nota)
                    @php
                        $notaLetra = $nota->nota_letra?->value ?? null;
                        $colorClass = match($notaLetra) {
                            'AD' => 'bg-green-100 text-green-800',
                            'A' => 'bg-blue-100 text-blue-800',
                            'B' => 'bg-amber-100 text-amber-800',
                            'C' => 'bg-red-100 text-red-800',
                            default => 'bg-slate-100 text-slate-800',
                        };
                        $esAprobado = in_array($notaLetra, ['AD', 'A', 'B']);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900">
                                {{ $nota->matricula?->horario?->programa?->nombre_programa ?? $nota->curso?->nombre_curso ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $nota->tipo_evaluacion?->value ?? 'Evaluación' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold {{ $colorClass }}">
                                {{ $notaLetra ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm {{ $esAprobado ? 'text-green-600' : 'text-red-600' }}">
                                {{ $esAprobado ? 'Aprobado' : 'Desaprobado' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $nota->created_at?->format('d/m/Y') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
