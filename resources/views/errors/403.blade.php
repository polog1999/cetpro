<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - CETPRO</title>
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
            color: #e11d48; /* Rojo profesional adaptado para error/advertencia */
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
            background-color: #4f46e5; /* Color primario índole/morado profesional tipo Filament */
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
        
        .info-box {
            margin-top: 2rem;
            padding: 1rem;
            background: #f9fafb; /* (gray-50) */
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            text-align: left;
        }
        
        .info-box p {
            font-size: 0.85rem;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .info-box p:not(:first-child) {
            font-size: 0.825rem;
            color: #6b7280; /* (gray-500) */
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Acceso Denegado</h1>
        <p class="error-message">
            No tienes los permisos necesarios para acceder a esta sección de la plataforma.
        </p>
        
        <div class="buttons">
            @auth
                <a href="{{ url('/admin') }}" class="btn btn-primary">Ir al Panel</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Regresar</a>
            @else
                <a href="{{ route('filament.admin.auth.login') }}" class="btn btn-primary">Iniciar Sesión</a>
            @endauth
        </div>
        
        @auth
        <div class="info-box">
            <p><strong>💡 ¿Por qué ves esto?</strong></p>
            <p>Tu cuenta actual no cuenta con los roles o privilegios asignados para visualizar esta página.</p>
            <p>Comunícate con el administrador del sistema si requieres acceso.</p>
        </div>
        @endauth
    </div>
</body>
</html>