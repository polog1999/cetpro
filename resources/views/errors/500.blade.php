<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Servidor - CETPRO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
            max-width: 600px;
            margin: 20px;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 18px;
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .btn-home {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
            margin: 5px;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 112, 154, 0.6);
        }
        
        .btn-reload {
            background: #4299e1;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.4);
        }
        
        .info-box {
            margin-top: 30px;
            padding: 20px;
            background: #fff5f5;
            border-radius: 10px;
            border-left: 4px solid #fc8181;
        }
        
        .info-box p {
            font-size: 14px;
            color: #742a2a;
            margin: 5px 0;
            text-align: left;
        }
        
        .info-box ul {
            text-align: left;
            margin-left: 20px;
            margin-top: 10px;
        }
        
        .info-box li {
            font-size: 14px;
            color: #742a2a;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-code">500</div>
        <h1 class="error-title">Error del Servidor</h1>
        <p class="error-message">
            Ups! Algo salió mal en nuestro servidor. 
            Nuestro equipo técnico ha sido notificado y está trabajando para resolver el problema.
        </p>
        
        <a href="{{ url('/admin') }}" class="btn-home">Volver al Inicio</a>
        <a href="javascript:location.reload()" class="btn-home btn-reload">Reintentar</a>
        
        <div class="info-box">
            <p><strong>🔧 ¿Qué puedes hacer?</strong></p>
            <ul>
                <li>Recargar la página en unos minutos</li>
                <li>Verificar tu conexión a internet</li>
                <li>Contactar al administrador si el problema persiste</li>
            </ul>
            @if(config('app.debug') && isset($exception))
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #feb2b2;">
                    <strong>Detalles técnicos (solo en modo desarrollo):</strong><br>
                    <code style="font-size: 12px;">{{ $exception->getMessage() }}</code>
                </p>
            @endif
        </div>
    </div>
</body>
</html>
