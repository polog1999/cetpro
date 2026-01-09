<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Estudiantil') | CETPRO MDLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a5f',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Poppins', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-primary-900 text-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('portal.dashboard') }}" class="text-lg font-semibold tracking-tight">
                        CETPRO MDLM
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('portal.dashboard') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Inicio
                    </a>
                    <a href="{{ route('portal.matriculas') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.matriculas') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Matrículas
                    </a>
                    <a href="{{ route('portal.pagos') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.pagos') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Pagos
                    </a>
                    <a href="{{ route('portal.horarios') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.horarios') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Horarios
                    </a>
                    <a href="{{ route('portal.notas') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.notas') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Notas
                    </a>
                    <a href="{{ route('portal.documentos') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('portal.documentos') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                        Documentos
                    </a>
                </nav>

                <!-- User Menu Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center space-x-2 text-sm text-slate-300 hover:text-white transition-colors focus:outline-none">
                        <span class="hidden sm:block">{{ auth()->user()->estudiante->nombre_completo ?? 'Estudiante' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                        
                        <a href="{{ route('portal.cambiar-password') }}" 
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                            Cambiar contraseña
                        </a>
                        
                        <form action="{{ route('portal.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <nav class="md:hidden border-t border-white/10 px-4 py-2 flex overflow-x-auto space-x-1">
            <a href="{{ route('portal.dashboard') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.dashboard') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Inicio
            </a>
            <a href="{{ route('portal.matriculas') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.matriculas') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Matrículas
            </a>
            <a href="{{ route('portal.pagos') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.pagos') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Pagos
            </a>
            <a href="{{ route('portal.horarios') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.horarios') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Horarios
            </a>
            <a href="{{ route('portal.notas') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.notas') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Notas
            </a>
            <a href="{{ route('portal.documentos') }}" 
               class="px-3 py-1.5 text-xs font-medium rounded whitespace-nowrap {{ request()->routeIs('portal.documentos') ? 'bg-white/10 text-white' : 'text-slate-300' }}">
                Documentos
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-slate-500">
                © {{ date('Y') }} CETPRO María de los Milagros. Portal Estudiantil.
            </p>
        </div>
    </footer>
</body>
</html>
