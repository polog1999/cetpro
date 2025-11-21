<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin; // <-- v4
use Filament\Forms\Components\TextInput;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;

class Login extends BaseLogin
{
    use HasCustomLayout;
    
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
            'usuario'  => $data['usuario'] ?? null,
            'password' => $data['password'] ?? null,
        ];
    }
}
