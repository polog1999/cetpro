@extends('portal.layouts.app')

@section('title', 'Cambiar Contraseña')

@section('content')
<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Cambiar Contraseña</h1>
    <p class="mt-1 text-slate-600">Actualiza tu contraseña de acceso al portal</p>
</div>

<div class="max-w-md">
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-600 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('portal.cambiar-password.update') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label for="password_actual" class="block text-sm font-medium text-slate-700 mb-1">
                    Contraseña actual
                </label>
                <input type="password" 
                       name="password_actual" 
                       id="password_actual"
                       class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                       required>
            </div>

            <div>
                <label for="password_nuevo" class="block text-sm font-medium text-slate-700 mb-1">
                    Nueva contraseña
                </label>
                <input type="password" 
                       name="password_nuevo" 
                       id="password_nuevo"
                       class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                       required>
                <p class="mt-1 text-xs text-slate-500">Mínimo 6 caracteres</p>
            </div>

            <div>
                <label for="password_nuevo_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                    Confirmar nueva contraseña
                </label>
                <input type="password" 
                       name="password_nuevo_confirmation" 
                       id="password_nuevo_confirmation"
                       class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                       required>
            </div>

            <div class="pt-4">
                <button type="submit" 
                        class="w-full px-4 py-2 bg-primary-600 text-white font-medium rounded-md hover:bg-primary-700 transition-colors">
                    Cambiar contraseña
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
