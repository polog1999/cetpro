<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Usuarios del Portal Estudiantil
        </x-slot>
        
        <x-slot name="description">
            Lista de todos los estudiantes que tienen acceso al portal. Solo el administrador puede cambiar contraseñas.
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
