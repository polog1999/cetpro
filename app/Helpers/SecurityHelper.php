<?php

namespace App\Helpers;

/**
 * Helper de seguridad con funciones útiles para validación y sanitización.
 */
class SecurityHelper
{
    /**
     * Sanitiza un string para prevenir XSS.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Eliminar tags HTML
        $value = strip_tags($value);
        
        // Convertir caracteres especiales
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
        
        // Trim espacios
        $value = trim($value);
        
        return $value;
    }

    /**
     * Valida que un email tenga formato correcto.
     *
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida que un DNI peruano sea válido (8 dígitos).
     *
     * @param string $dni
     * @return bool
     */
    public static function isValidDNI(string $dni): bool
    {
        // Debe ser 8 dígitos numéricos
        return preg_match('/^\d{8}$/', $dni) === 1;
    }

    /**
     * Valida que un teléfono peruano sea válido.
     *
     * @param string $phone
     * @return bool
     */
    public static function isValidPhone(string $phone): bool
    {
        // Acepta formatos: 999999999, +51999999999, 01-9999999
        $patterns = [
            '/^9\d{8}$/',           // Móvil: 999999999
            '/^\+519\d{8}$/',       // Móvil con +51
            '/^01\-?\d{7}$/',       // Fijo Lima: 01-9999999
            '/^\d{2}\-?\d{6}$/',    // Fijo provincia
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera un token seguro.
     *
     * @param int $length
     * @return string
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Verifica si una IP está en lista blanca.
     *
     * @param string $ip
     * @param array $whitelist
     * @return bool
     */
    public static function ipInWhitelist(string $ip, array $whitelist): bool
    {
        return in_array($ip, $whitelist);
    }

    /**
     * Sanitiza nombre de archivo para evitar path traversal.
     *
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Eliminar caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $filename);
        
        // Eliminar múltiples puntos consecutivos
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Eliminar puntos al inicio
        $filename = ltrim($filename, '.');
        
        return $filename;
    }

    /**
     * Valida que una URL sea segura.
     *
     * @param string $url
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        
        if ($url === false) {
            return false;
        }

        // Solo permitir http y https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https']);
    }

    /**
     * Encripta datos sensibles.
     *
     * @param string $data
     * @return string
     */
    public static function encryptSensitiveData(string $data): string
    {
        return encrypt($data);
    }

    /**
     * Desencripta datos sensibles.
     *
     * @param string $encryptedData
     * @return string|null
     */
    public static function decryptSensitiveData(string $encryptedData): ?string
    {
        try {
            return decrypt($encryptedData);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Valida fortaleza de contraseña.
     *
     * @param string $password
     * @return array ['valid' => bool, 'messages' => array]
     */
    public static function validatePasswordStrength(string $password): array
    {
        $messages = [];
        $valid = true;

        // Mínimo 8 caracteres
        if (strlen($password) < 8) {
            $valid = false;
            $messages[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $valid = false;
            $messages[] = 'La contraseña debe contener al menos una letra mayúscula';
        }

        // Al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $valid = false;
            $messages[] = 'La contraseña debe contener al menos una letra minúscula';
        }

        // Al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            $valid = false;
            $messages[] = 'La contraseña debe contener al menos un número';
        }

        // Al menos un carácter especial
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $valid = false;
            $messages[] = 'La contraseña debe contener al menos un carácter especial';
        }

        return [
            'valid' => $valid,
            'messages' => $messages,
        ];
    }

    /**
     * Formatea un DNI para mostrar.
     *
     * @param string $dni
     * @return string
     */
    public static function formatDNI(string $dni): string
    {
        // Formato: XX XXX XXX
        if (strlen($dni) === 8) {
            return substr($dni, 0, 2) . ' ' . substr($dni, 2, 3) . ' ' . substr($dni, 5, 3);
        }

        return $dni;
    }

    /**
     * Logs de actividad sospechosa.
     *
     * @param string $activity
     * @param array $context
     * @return void
     */
    public static function logSuspiciousActivity(string $activity, array $context = []): void
    {
        \Log::warning('Actividad sospechosa detectada: ' . $activity, array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]));
    }

    /**
     * Valida que un array solo contenga valores permitidos.
     *
     * @param array $values
     * @param array $allowed
     * @return bool
     */
    public static function arrayOnlyContainsAllowedValues(array $values, array $allowed): bool
    {
        foreach ($values as $value) {
            if (!in_array($value, $allowed)) {
                return false;
            }
        }

        return true;
    }
}
