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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .helpful-links {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }
        
        .helpful-links h3 {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 15px;
        }
        
        .helpful-links a {
            display: inline-block;
            margin: 5px 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .helpful-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-message">
            Lo sentimos, la página que estás buscando no existe o ha sido movida. 
            Por favor, verifica la URL o regresa a la página principal.
        </p>
        <a href="{{ url('/admin') }}" class="btn-home">Volver al Inicio</a>
        
        <div class="helpful-links">
            <h3>Enlaces útiles</h3>
            <a href="{{ url('/admin') }}">Panel Principal</a>
            <a href="{{ url('/admin/estudiantes') }}">Estudiantes</a>
            <a href="{{ url('/admin/matriculas') }}">Matrículas</a>
        </div>
    </div>
</body>
</html>
