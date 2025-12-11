<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\SecurityHelper;

class SecurityHelperTest extends TestCase
{
    /**
     * Test de sanitización de strings.
     */
    public function test_sanitiza_strings_correctamente()
    {
        $malicious = '<script>alert("XSS")</script>';
        $sanitized = SecurityHelper::sanitizeString($malicious);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
    }

    /**
     * Test de validación de email.
     */
    public function test_valida_email_correcto()
    {
        $this->assertTrue(SecurityHelper::isValidEmail('usuario@example.com'));
        $this->assertTrue(SecurityHelper::isValidEmail('test.user+tag@domain.co.pe'));
        
        $this->assertFalse(SecurityHelper::isValidEmail('invalid-email'));
        $this->assertFalse(SecurityHelper::isValidEmail('test@'));
        $this->assertFalse(SecurityHelper::isValidEmail('@domain.com'));
    }

    /**
     * Test de validación de DNI peruano.
     */
    public function test_valida_dni_peruano()
    {
        // DNIs válidos (8 dígitos)
        $this->assertTrue(SecurityHelper::isValidDNI('12345678'));
        $this->assertTrue(SecurityHelper::isValidDNI('87654321'));
        
        // DNIs inválidos
        $this->assertFalse(SecurityHelper::isValidDNI('1234567'));  // 7 dígitos
        $this->assertFalse(SecurityHelper::isValidDNI('123456789')); // 9 dígitos
        $this->assertFalse(SecurityHelper::isValidDNI('1234567a')); // Letras
        $this->assertFalse(SecurityHelper::isValidDNI(''));
    }

    /**
     * Test de validación de teléfono peruano.
     */
    public function test_valida_telefono_peruano()
    {
        // Teléfonos válidos
        $this->assertTrue(SecurityHelper::isValidPhone('987654321'));  // Móvil
        $this->assertTrue(SecurityHelper::isValidPhone('+51987654321')); // Móvil con +51
        $this->assertTrue(SecurityHelper::isValidPhone('01-1234567')); // Fijo Lima
        $this->assertTrue(SecurityHelper::isValidPhone('011234567'));  // Fijo Lima sin guión
        
        // Teléfonos inválidos
        $this->assertFalse(SecurityHelper::isValidPhone('12345'));
        $this->assertFalse(SecurityHelper::isValidPhone('abcdefghi'));
        $this->assertFalse(SecurityHelper::isValidPhone(''));
    }

    /**
     * Test de validación de fortaleza de contraseña.
     */
    public function test_valida_fortaleza_de_contrasena()
    {
        // Contraseña fuerte
        $strong = SecurityHelper::validatePasswordStrength('P@ssw0rd123');
        $this->assertTrue($strong['valid']);
        $this->assertEmpty($strong['messages']);
        
        // Contraseña débil (muy corta)
        $weak1 = SecurityHelper::validatePasswordStrength('Pass1!');
        $this->assertFalse($weak1['valid']);
        $this->assertNotEmpty($weak1['messages']);
        
        // Contraseña sin mayúscula
        $weak2 = SecurityHelper::validatePasswordStrength('password123!');
        $this->assertFalse($weak2['valid']);
        $this->assertContains(
            'La contraseña debe contener al menos una letra mayúscula',
            $weak2['messages']
        );
        
        // Contraseña sin número
        $weak3 = SecurityHelper::validatePasswordStrength('Password!');
        $this->assertFalse($weak3['valid']);
        $this->assertContains(
            'La contraseña debe contener al menos un número',
            $weak3['messages']
        );
        
        // Contraseña sin carácter especial
        $weak4 = SecurityHelper::validatePasswordStrength('Password123');
        $this->assertFalse($weak4['valid']);
        $this->assertContains(
            'La contraseña debe contener al menos un carácter especial',
            $weak4['messages']
        );
    }

    /**
     * Test de sanitización de nombre de archivo.
     */
    public function test_sanitiza_nombre_de_archivo()
    {
        $dangerous = '../../../etc/passwd';
        $safe = SecurityHelper::sanitizeFilename($dangerous);
        
        $this->assertStringNotContainsString('..', $safe);
        $this->assertStringNotContainsString('/', $safe);
        
        $dangerous2 = 'file<script>.pdf';
        $safe2 = SecurityHelper::sanitizeFilename($dangerous2);
        
        $this->assertStringNotContainsString('<', $safe2);
        $this->assertStringNotContainsString('>', $safe2);
    }

    /**
     * Test de validación de URL.
     */
    public function test_valida_url()
    {
        $this->assertTrue(SecurityHelper::isValidUrl('https://example.com'));
        $this->assertTrue(SecurityHelper::isValidUrl('http://test.com/path'));
        
        $this->assertFalse(SecurityHelper::isValidUrl('javascript:alert("XSS")'));
        $this->assertFalse(SecurityHelper::isValidUrl('file:///etc/passwd'));
        $this->assertFalse(SecurityHelper::isValidUrl('not-a-url'));
    }

    /**
     * Test de encriptación y desencriptación.
     */
    public function test_encripta_y_desencripta_datos()
    {
        $data = 'Información Sensible';
        
        $encrypted = SecurityHelper::encryptSensitiveData($data);
        $this->assertNotEquals($data, $encrypted);
        
        $decrypted = SecurityHelper::decryptSensitiveData($encrypted);
        $this->assertEquals($data, $decrypted);
    }

    /**
     * Test de formato de DNI.
     */
    public function test_formatea_dni()
    {
        $dni = '12345678';
        $formatted = SecurityHelper::formatDNI($dni);
        
        $this->assertEquals('12 345 678', $formatted);
    }

    /**
     * Test de validación de array con valores permitidos.
     */
    public function test_valida_array_con_valores_permitidos()
    {
        $allowed = ['rojo', 'verde', 'azul'];
        
        $valid = ['rojo', 'verde'];
        $this->assertTrue(
            SecurityHelper::arrayOnlyContainsAllowedValues($valid, $allowed)
        );
        
        $invalid = ['rojo', 'amarillo'];
        $this->assertFalse(
            SecurityHelper::arrayOnlyContainsAllowedValues($invalid, $allowed)
        );
    }

    /**
     * Test de sanitización con valores null.
     */
    public function test_sanitiza_valores_null()
    {
        $result = SecurityHelper::sanitizeString(null);
        $this->assertNull($result);
    }

    /**
     * Test de generación de token seguro.
     */
    public function test_genera_token_seguro()
    {
        $token1 = SecurityHelper::generateSecureToken();
        $token2 = SecurityHelper::generateSecureToken();
        
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 chars hex
    }
}
