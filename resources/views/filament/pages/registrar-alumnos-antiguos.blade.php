<x-filament-panels::page>
    <form wire:submit.prevent="registrar">
        {{ $this->getForm('form') }}
    </form>
</x-filament-panels::page>
