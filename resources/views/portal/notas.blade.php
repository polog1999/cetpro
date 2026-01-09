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
        $promedio = $notas->avg('nota');
        $aprobadas = $notas->where('nota', '>=', 11)->count();
        $total = $notas->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Promedio General</p>
            <p class="mt-1 text-2xl font-semibold {{ $promedio >= 11 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($promedio, 1) }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-500">Módulos Aprobados</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $aprobadas }}/{{ $total }}</p>
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
                        Nota
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
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900">
                                {{ $nota->matricula?->horario?->programa?->nombre_programa ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $nota->tipo_evaluacion ?? 'Evaluación' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold
                                {{ $nota->nota >= 11 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $nota->nota }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm {{ $nota->nota >= 11 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $nota->nota >= 11 ? 'Aprobado' : 'Desaprobado' }}
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
