<x-filament-panels::page>
    <div class="max-w-xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 md:p-8">
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Seguridad de la cuenta
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Asegúrese de usar una contraseña larga y aleatoria para mantener su cuenta segura.
                </p>
            </div>

            <form wire:submit="submit" class="space-y-6">
                {{ $this->form }}
                
                <div class="flex justify-end pt-2">
                    <x-filament::button type="submit">
                        Actualizar contraseña
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
