<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Lista de campos que NO deben ser sanitizados (por ejemplo, contraseñas, contenido HTML permitido)
     */
    protected $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitizar entrada para prevenir XSS
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (!in_array($key, $this->except) && is_string($value)) {
                // Sanitizar el valor
                $value = $this->sanitize($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }

    /**
     * Sanitiza un valor de entrada.
     *
     * @param string $value
     * @return string
     */
    protected function sanitize(string $value): string
    {
        // Eliminar scripts y tags peligrosos
        $value = strip_tags($value);
        
        // Decodificar entidades HTML para evitar doble codificación
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        
        // Convertir caracteres especiales a entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
        
        // Trim espacios
        $value = trim($value);
        
        return $value;
    }
}
