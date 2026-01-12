<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-primary-600 via-primary-500 to-success-500 p-6 shadow-xl">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
            </svg>
        </div>
        
        <div class="relative z-10">
            {{-- Header Row --}}
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                {{-- Greeting --}}
                <div class="text-white">
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ $greeting }}, {{ $userName }}
                    </h2>
                    <p class="mt-1 text-sm text-white/80 md:text-base">
                        <x-filament::icon 
                            icon="heroicon-o-calendar" 
                            class="inline-block h-4 w-4 mr-1"
                        />
                        {{ ucfirst($currentDate) }}
                    </p>
                </div>
                
                {{-- Quick Stats --}}
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-white/20 backdrop-blur-sm px-4 py-2 text-white">
                        <div class="text-xs uppercase tracking-wider opacity-80">Panel Administrativo</div>
                        <div class="text-lg font-semibold">CETPRO MDLM</div>
                    </div>
                </div>
            </div>
            
            {{-- Alerts Row --}}
            @if(count($alerts) > 0)
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($alerts as $alert)
                        <div @class([
                            'flex items-center gap-3 rounded-lg px-4 py-3 backdrop-blur-sm transition-transform hover:scale-[1.02]',
                            'bg-red-500/30 border border-red-400/50' => $alert['color'] === 'danger',
                            'bg-amber-500/30 border border-amber-400/50' => $alert['color'] === 'warning',
                            'bg-blue-500/30 border border-blue-400/50' => $alert['color'] === 'info',
                        ])>
                            <div @class([
                                'flex-shrink-0 rounded-full p-2',
                                'bg-red-500/50' => $alert['color'] === 'danger',
                                'bg-amber-500/50' => $alert['color'] === 'warning',
                                'bg-blue-500/50' => $alert['color'] === 'info',
                            ])>
                                <x-filament::icon 
                                    :icon="$alert['icon']"
                                    class="h-5 w-5 text-white"
                                />
                            </div>
                            <span class="text-sm font-medium text-white">
                                {{ $alert['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-lg bg-white/20 backdrop-blur-sm px-4 py-3">
                    <div class="flex items-center gap-3 text-white">
                        <x-filament::icon 
                            icon="heroicon-o-check-circle"
                            class="h-5 w-5 text-emerald-300"
                        />
                        <span class="text-sm font-medium">
                            ¡Todo en orden! No hay alertas pendientes.
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
