<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

$usuarioId = isset($usersesion['USUARIO_ID']) ? (int)$usersesion['USUARIO_ID'] : 0;
if ($usuarioId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'articulos_nuevos' => [],
        'mensaje' => 'Usuario no válido para polling RFID.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$baseUrl = isset($GLOBALS['rfid_api_base_url']) ? rtrim($GLOBALS['rfid_api_base_url'], '/') : 'http://127.0.0.1:8000';
$timeout = isset($GLOBALS['rfid_api_timeout_seconds']) ? (int)$GLOBALS['rfid_api_timeout_seconds'] : 4;

function remisionRfidErrorResponse($mensaje) {
    echo json_encode([
        'status' => 'error',
        'articulos_nuevos' => [],
        'mensaje' => $mensaje
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function remisionRfidFetch($baseUrl, $timeout, $usuarioId) {
    $endpoint = $baseUrl . '/rfid/web/remision/' . (int)$usuarioId;
    $responseBody = null;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeout));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, $timeout));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception('No fue posible consultar la API RFID: ' . $err);
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => max(1, $timeout),
                'ignore_errors' => true
            ]
        ]);

        $responseBody = @file_get_contents($endpoint, false, $context);
        if ($responseBody === false) {
            throw new Exception('No fue posible consultar la API RFID.');
        }

        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/\s(\d{3})\s?/', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            }
        }
    }

    if ($httpCode >= 400) {
        throw new Exception('La API RFID respondió con error HTTP ' . $httpCode . '.');
    }

    $data = json_decode($responseBody, true);
    if (!is_array($data)) {
        throw new Exception('Respuesta inválida de la API RFID.');
    }

    if (!isset($data['status'])) {
        $data['status'] = 'success';
    }
    if (!isset($data['articulos_nuevos']) || !is_array($data['articulos_nuevos'])) {
        $data['articulos_nuevos'] = [];
    }

    $data['usuario_id_consultado'] = (int)$usuarioId;
    return $data;
}

try {
    // 1) Consulta con usuario de sesion
    $data = remisionRfidFetch($baseUrl, $timeout, $usuarioId);

    // 2) Fallback pragmático: si llega vacío y la sesión no es 1, intenta usuario 1
    // (útil cuando la app RFID está enviando temporalmente a usuario_id=1)
    if (
        $data['status'] === 'success'
        && empty($data['articulos_nuevos'])
        && $usuarioId !== 1
    ) {
        $dataFallback = remisionRfidFetch($baseUrl, $timeout, 1);
        if ($dataFallback['status'] === 'success' && !empty($dataFallback['articulos_nuevos'])) {
            $data = $dataFallback;
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    remisionRfidErrorResponse($e->getMessage());
}
