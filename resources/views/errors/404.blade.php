<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - CETPRO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f3f4f6; /* Fondo gris claro estilo Filament (gray-100) */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1f2937; /* Texto oscuro principal (gray-800) */
            padding: 1rem;
        }
        
        .error-container {
            background: #ffffff;
            border-radius: 0.75rem; /* Bordes redondeados sutiles (rounded-xl) */
            padding: 3rem 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Sombra suave tipo card */
            border: 1px solid #e5e7eb; /* Borde fino sutil (gray-200) */
            text-align: center;
            max-width: 32rem;
            width: 100%;
        }
        
        .error-code {
            font-size: 3rem;
            font-weight: 700;
            color: #4f46e5; /* Color primario índole/morado profesional tipo Filament */
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
        }
        
        .error-title {
            font-size: 1.25rem;
            color: #111827; /* (gray-900) */
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 0.95rem;
            color: #4b5563; /* (gray-600) */
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        
        .error-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem; /* (rounded-lg) */
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #4f46e5; /* Color primario Filament */
            color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
        }
        
        .btn-secondary {
            background-color: #ffffff;
            color: #374151; /* (gray-700) */
            border-color: #d1d5db; /* (gray-300) */
        }
        
        .btn-secondary:hover {
            background-color: #f9fafb; /* (gray-50) */
            border-color: #9ca3af;
        }
        
        .countdown {
            margin-top: 1.5rem;
            font-size: 0.825rem;
            color: #6b7280; /* (gray-500) */
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-message">
            La página que buscas no existe o ha sido movida a otra sección.
        </p>
        
        <div class="buttons">
            @auth
                <a href="{{ url('/admin') }}" class="btn btn-primary">Ir al Panel</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Regresar</a>
            @else
                <a href="{{ route('filament.admin.auth.login') }}" class="btn btn-primary">Iniciar Sesión</a>
            @endauth
        </div>
        
        @guest
        <div class="countdown">
            Serás redirigido al login en <span id="countdown">5</span> segundos...
        </div>
        <script>
            let seconds = 5;
            const countdown = document.getElementById('countdown');
            const interval = setInterval(() => {
                seconds--;
                countdown.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = "{{ route('filament.admin.auth.login') }}";
                }
            }, 1000);
        </script>
        @endguest
    </div>
</body>
</html>