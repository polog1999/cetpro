@extends('portal.layouts.app')

@section('title', 'Mis Documentos')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Mis Documentos</h1>
    <p class="mt-1 text-slate-600">Documentos subidos en tus matrículas</p>
</div>

@if($matriculas->isEmpty())
    <div class="bg-white rounded-lg border border-slate-200 p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-slate-900">No tienes documentos</h3>
        <p class="mt-2 text-sm text-slate-500">Aún no se han subido documentos en tus matrículas.</p>
    </div>
@else
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Programa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fecha Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @foreach($matriculas as $matricula)
                <tr x-data="{ showModal: false }">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                        {{ $matricula->horario?->programa?->nombre_programa ?? 'Sin programa' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                        {{ $matricula->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $matricula->estado->value === 'completado' ? 'bg-green-100 text-green-800' : 
                               ($matricula->estado->value === 'en_proceso' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800') }}">
                            {{ ucfirst(str_replace('_', ' ', $matricula->estado->value ?? 'N/A')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                        <!-- Ver PDF (Modal) -->
                        <button @click="showModal = true" 
                                class="inline-flex items-center px-3 py-1.5 border border-slate-300 text-xs font-medium rounded text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Ver
                        </button>
                        
                        <!-- Descargar PDF -->
                        <a href="{{ asset('storage/' . $matricula->documento_path) }}" 
                           download
                           class="inline-flex items-center px-3 py-1.5 border border-primary-600 text-xs font-medium rounded text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar
                        </a>

                        <!-- Modal para ver PDF -->
                        <div x-show="showModal" 
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto" 
                             aria-labelledby="modal-title" 
                             role="dialog" 
                             aria-modal="true">
                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <!-- Overlay -->
                                <div x-show="showModal"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     @click="showModal = false"
                                     class="fixed inset-0 bg-black/60 transition-opacity"></div>

                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                                <!-- Modal Content -->
                                <div x-show="showModal"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="inline-block w-full max-w-5xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg"
                                     @click.away="showModal = false">
                                    
                                    <!-- Header -->
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                                        <h3 class="text-lg font-medium text-slate-900">
                                            Documento - {{ $matricula->horario?->programa?->nombre_programa ?? 'Matrícula' }}
                                        </h3>
                                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-500 focus:outline-none">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- PDF Viewer -->
                                    <div class="p-4 bg-slate-100" style="height: 70vh;">
                                        <iframe 
                                            src="{{ asset('storage/' . $matricula->documento_path) }}" 
                                            class="w-full h-full rounded border border-slate-200"
                                            frameborder="0">
                                        </iframe>
                                    </div>

                                    <!-- Footer -->
                                    <div class="flex justify-end px-6 py-4 border-t border-slate-200 bg-slate-50">
                                        <a href="{{ asset('storage/' . $matricula->documento_path) }}" 
                                           download
                                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Descargar PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
