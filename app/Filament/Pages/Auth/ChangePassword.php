<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $slug = 'cambiar-contrasena';
    protected static ?string $title = 'Cambiar Contraseña';
    protected string $view = 'filament.pages.auth.change-password';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form($form)
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Grid::make(1)
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('current_password')
                            ->label('Contraseña Actual')
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->extraInputAttributes(['style' => 'max-width: 500px']),
                        \Filament\Forms\Components\TextInput::make('new_password')
                            ->label('Nueva Contraseña')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->same('new_password_confirmation')
                            ->rule(\Illuminate\Validation\Rules\Password::default())
                            ->extraInputAttributes(['style' => 'max-width: 500px']),
                        \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirmar Nueva Contraseña')
                            ->password()
                            ->required()
                            ->extraInputAttributes(['style' => 'max-width: 500px']),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();
        $user->password = $data['new_password']; // El mutador en el modelo hashea
        $user->save();

        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Contraseña actualizada correctamente')
            ->send();
            
        $this->form->fill();
    }
}
