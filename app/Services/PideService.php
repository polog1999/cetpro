<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;

class PideService
{
    public static function apiPeruDni(string $dni)
    {

        $token = env('APIPERU_TOKEN');
        $url = env('APIPERU_URL');
        // $numero = '46027897';

        $response = Http::withOptions([
            'verify' => false, // Equivale a 'verify' => false de Guzzle
            'connect_timeout' => 5,
        ])
            ->withHeaders([
                'Referer' => $url,
                'User-Agent' => 'laravel/guzzle',
                'Accept' => 'application/json',
            ])
            ->withToken($token) // Configura automáticamente el Bearer token
            ->get("{$url}{$dni}");

        if ($response->successful()) {
            $data = $response->json();
            // dd($data); // O usa return $data;
            return $data;
        } 
        return [
        'success' => false,
        'data' => null,
        'error' => 'No se pudo consultar el DNI'
    ];
    }
    public static function apisNet(string $dni)
    {

        $token = env('APISNET_TOKEN');
        $url = env('APISNET_URL');

        $response = Http::withOptions([
            'verify' => false, // Equivale a 'verify' => false de Guzzle
            'connect_timeout' => 5,
        ])
            ->withHeaders([
                'Referer' => $url,
                'User-Agent' => 'laravel/guzzle',
                'Accept' => 'application/json',
            ])
            ->withToken($token) // Configura automáticamente el Bearer token
            ->get("{$url}dni?numero={$dni}");

        if ($response->successful()) {
            $data = $response->json();
            $data['success'] = true;
            // dd($data); // O usa return $data;
            return $data;
        } 
        return [
        'success' => false,
        'data' => null,
        'error' => 'No se pudo consultar el DNI'
    ];
    }

    // private static function guardarFotoPersona($dni, $base64String)
    // {
    //     // 1. Nombre de archivo único
    //     $nombreArchivo = "fotos_personas/{$dni}_" . Str::random(5) . ".jpg";

    //     // 2. Limpiar el string base64 si trae el header (data:image/jpeg;base64,...)
    //     $imagenBinaria = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64String));

    //     // 3. Guardar en el disco 'public' (carpeta storage/app/public)
    //     Storage::disk('public')->put($nombreArchivo, $imagenBinaria);

    //     // 4. Retornar la URL pública
    //     return Storage::url($nombreArchivo);
    // }
    public static function ws_reniec($dni)
    {
        // 1. LIBERAR SESIÃ“N (Crucial para evitar el 504 en el navegador)

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'socket' => [
                'bindto' => '0:0', // Esto ayuda a forzar la selección de interfaz
                'tcp_nodelay' => true
            ],
            'http' => [
                'timeout' => 10,
                'header' => "Connection: close\r\n"
            ]
        ]);

        $nro_documento = $dni;
        $tipo = 'JS';
        $nro_docu = trim($nro_documento);

        $registro = array(
            'codResu' => '99',
            'detResu' => 'Error desconocido', // Este es el valor por defecto que estÃƒÂ¡s viendo
            'paterno' => '',
            'materno' => '',
            'nombre' => '',
            'foto' => '',
            'restriccion' => ''
        );

        $parametros = array(
            "arg0" => array(
                "nuDniConsulta" => $nro_docu,
                "nuDniUsuario"  => env('PIDE_USER'),
                "nuRucUsuario"  => env('PIDE_RUC'),
                "password"      => env('PIDE_PASS')
            )
        );

        try {
            // 2. No descargues el WSDL cada vez, usa el modo "non-WSDL" o fuerza el timeout
            $time_start = microtime(true);

            $wsdl_local = base_path('resources/wsdl/reniec.wsdl');
            $client = new SoapClient($wsdl_local, [
                'location' => env('PIDE_URL'),
                'trace' => 1,
                'soap_version' => SOAP_1_1,
                'stream_context' => $context,
                'connection_timeout' => 10, // Si en 10s no conecta, muere
                'exceptions' => true
            ]);
            Log::info("Tiempo solo para conectar SOAP: " . (microtime(true) - $time_start));
            $response = $client->consultar($parametros);

            // --- CORRECCIÃƒâ€œN PRINCIPAL AQUÃƒÂ ---
            $obj = null;

            // 1. Verificamos si existe la propiedad 'return' (EstÃƒÂ¡ndar PIDE)
            if (isset($response->return)) {
                $obj = $response->return;
            }
            // 2. Si no, verificamos si es un array
            else if (is_array($response) && isset($response[0])) {
                $obj = $response[0];
                // A veces el array contiene el objeto return
                if (isset($obj->return)) $obj = $obj->return;
            }
            // 3. Si no, asumimos que es el objeto directo
            else {
                $obj = $response;
            }

            // --- VALIDACIÃƒâ€œN DE DATOS ---
            if (isset($obj->coResultado)) {
                $registro['codResu'] = (string)$obj->coResultado;
                $registro['detResu'] = (string)$obj->deResultado;

                if ($registro['codResu'] === '0000') {

                    $d = $obj->datosPersona;

                    $registro['paterno']     = (string)$d->apPrimer;
                    $registro['materno']     = (string)$d->apSegundo;
                    $registro['nombre']      = (string)$d->prenombres;
                    $registro['estadocivil'] = (string)$d->estadoCivil;
                    $registro['direccion']   = (string)$d->direccion;
                    $registro['ubigeo']      = (string) $d->ubigeo;
                    $registro['restriccion'] = (string)$d->restriccion;
                    if (!empty($d->foto)) {

                        // $folderPath = public_path("uploads/foto_dni/");
                        // $folderPath = storage_path("app/public/uploads/foto_dni/");
                        $folderPath = base_path("../foto_dni/");


                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }

                        $fileName = $nro_docu . ".png";
                        $fullPath = $folderPath . $fileName;


                        file_put_contents($fullPath, $d->foto);
                        $registro['foto'] = $nro_docu;
                    }
                }
            } else {
                // DEPURACIÃƒâ€œN: Si llega aquÃƒÂ­, veremos quÃƒÂ© estructura devolviÃƒÂ³ realmente PIDE
                // Convertimos la respuesta a texto para verla en el mensaje de error
                $dump = print_r($response, true);
                $registro['detResu'] = "Estructura no reconocida. Respuesta cruda: " . substr($dump, 0, 200) . "...";
            }
        } catch (SoapFault $e) {
            $registro['detResu'] = "Error SOAP: " . $e->getMessage();
            Log::error("RENIEC CaÃ­do o Bloqueado: " . $e->getMessage());
        } catch (Exception $exc) {
            $registro['detResu'] = "Error General: " . $exc->getMessage();
            Log::error("RENIEC CaÃ­do o Bloqueado: " . $exc->getMessage());
        }

        // if ($tipo == 'JS') {
        //     // Limpiar buffer para evitar HTML basura antes del JSON
        //     if (ob_get_length()) ob_clean();
        //     header('Content-Type: application/json; charset=utf-8');
        //     return json_encode($registro);
        //     // exit;
        // } else {
        //     return $registro;
        // }

        return $registro;
    }
}
