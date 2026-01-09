@extends('portal.layouts.app')

@section('title', 'Mis Horarios')

@section('content')
<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Mis Horarios</h1>
    <p class="mt-1 text-slate-600">Horarios de clases de tus matrículas activas</p>
</div>

@if($horarios->isEmpty())
    <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
        <p class="text-slate-500">No tienes horarios activos</p>
    </div>
@else
    <div class="grid gap-4">
        @foreach($horarios as $horario)
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="p-6">
                    <!-- Header del horario -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-slate-900">
                                {{ $horario->programa?->nombre_programa ?? 'Programa' }}
                            </h3>
                            <p class="text-sm text-slate-500">
                                Turno: {{ $horario->turno?->value ?? 'N/A' }}
                            </p>
                        </div>
                        <span class="mt-2 md:mt-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $horario->activo ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                            {{ $horario->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <!-- Detalles -->
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Horario</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $horario->hora_inicio?->format('H:i') ?? 'Sin hora' }} - {{ $horario->hora_fin?->format('H:i') ?? 'Sin hora' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Días</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ is_array($horario->dias) ? implode(', ', $horario->dias) : ($horario->dias ?? 'Por definir') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Aula</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $horario->aula ?? 'Por asignar' }}
                            </dd>
                        </div>
                        @if($horario->docente)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Docente</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ $horario->docente->nombre_completo }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Modalidad</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $horario->modalidad?->value ?? 'Presencial' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
