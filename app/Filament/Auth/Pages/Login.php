<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin; // <-- v4
use Filament\Forms\Components\TextInput;
// HasCustomLayout trait removido - plugin AuthUIEnhancer deshabilitado

class Login extends BaseLogin
{
    // Trait HasCustomLayout removido
    
    /**
     * Texto de bienvenida profesional
     */
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return new \Illuminate\Support\HtmlString('
            <div class="text-center">
                <h1 class="text-2xl font-bold text-primary-600 dark:text-primary-400">CETPRO LA MOLINA</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sistema de Gestión Académica</p>
            </div>
        ');
    }
    
    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('usuario')
            ->label('Usuario')
            ->required()
            ->autofocus()
            ->autocomplete('username');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'usuario'  => trim($data['usuario'] ?? ''),
            'password' => trim($data['password'] ?? ''),
        ];
    }

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        $credentials = $this->getCredentialsFromFormData($data);

        // Verificar si el usuario existe
        $user = \App\Models\Usuario::where('usuario', $credentials['usuario'])->first();

        if (! $user) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.usuario' => 'El usuario no existe.',
            ]);
        }

        // Verificar si está activo
        if (! $user->activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.usuario' => 'Usuario inactivo, contacte con administración.',
            ]);
        }

        // Verificar contraseña
        if (! filament()->auth()->attempt($credentials, $data['remember'] ?? false)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.password' => 'La contraseña es incorrecta.',
            ]);
        }

        session()->regenerate();
        
        // Si es alumno (estudiante), redirigir al portal en lugar del admin
        if ($user->esAlumno()) {
            return new class implements \Filament\Auth\Http\Responses\Contracts\LoginResponse {
                public function toResponse($request)
                {
                    return redirect()->route('portal.dashboard');
                }
            };
        }

        return app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class);
    }
}
