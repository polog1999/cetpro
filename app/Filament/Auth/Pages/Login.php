<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin; // <-- v4
use Filament\Forms\Components\TextInput;
// HasCustomLayout trait removido - plugin AuthUIEnhancer deshabilitado

class Login extends BaseLogin
{
    // Trait HasCustomLayout removido
    
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

        return app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class);
    }
}
