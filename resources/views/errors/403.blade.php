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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        
        .error-code {
            font-size: 100px;
            font-weight: bold;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .error-title {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 16px;
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-icon {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
        }
        
        .btn-secondary {
            background: #718096;
            box-shadow: 0 4px 15px rgba(113, 128, 150, 0.4);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(113, 128, 150, 0.6);
        }
        
        .buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        
        .info-box {
            margin-top: 25px;
            padding: 15px;
            background: #fff5f5;
            border-radius: 10px;
            border-left: 4px solid #f5576c;
            text-align: left;
        }
        
        .info-box p {
            font-size: 13px;
            color: #4a5568;
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Acceso Denegado</h1>
        <p class="error-message">
            No tienes permisos para acceder a esta sección.
        </p>
        
        <div class="buttons">
            @auth
                <a href="{{ url('/admin') }}" class="btn">Ir al Panel</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Regresar</a>
            @else
                <a href="{{ route('filament.admin.auth.login') }}" class="btn">Iniciar Sesión</a>
            @endauth
        </div>
        
        @auth
        <div class="info-box">
            <p><strong>💡 ¿Por qué veo esto?</strong></p>
            <p>Tu cuenta no tiene los permisos necesarios para esta página.</p>
            <p>Contacta al administrador si necesitas acceso.</p>
        </div>
        @endauth
    </div>
</body>
</html>
