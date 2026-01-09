@extends('portal.layouts.app')

@section('title', 'Portal Estudiantil')

@section('content')
<!-- Welcome Section -->
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-slate-900">
        Bienvenido, {{ $estudiante->nombres }}
    </h1>
    <p class="mt-1 text-slate-600">
        Consulta tu información académica y financiera
    </p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Matrículas Activas -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Matrículas Activas</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $matriculasActivas }}</p>
    </div>
    
    <!-- Última Matrícula -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 md:col-span-2">
        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Última Matrícula</p>
        @if($ultimaMatricula)
            <p class="mt-2 text-lg font-medium text-slate-900">
                {{ $ultimaMatricula->horario?->programa?->nombre_programa ?? 'Sin programa asignado' }}
            </p>
            <p class="mt-1 text-sm text-slate-600">
                Estado: 
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    {{ $ultimaMatricula->estado->value === 'completado' ? 'bg-green-100 text-green-800' : 
                       ($ultimaMatricula->estado->value === 'en_proceso' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800') }}">
                    {{ ucfirst(str_replace('_', ' ', $ultimaMatricula->estado->value ?? 'N/A')) }}
                </span>
            </p>
        @else
            <p class="mt-2 text-slate-600">No tienes matrículas registradas</p>
        @endif
    </div>
</div>



<!-- Student Info -->
<div class="bg-white rounded-lg border border-slate-200 p-6">
    <h2 class="text-lg font-medium text-slate-900 mb-4">Información Personal</h2>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <dt class="text-sm font-medium text-slate-500">Nombre Completo</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $estudiante->nombre_completo }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-slate-500">Documento</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $estudiante->tipo_documento?->value ?? 'DNI' }}: {{ $estudiante->nro_documento }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-slate-500">Correo Electrónico</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $estudiante->email ?? 'No registrado' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-slate-500">Teléfono</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $estudiante->telefono ?? 'No registrado' }}</dd>
        </div>
    </dl>
</div>
@endsection
