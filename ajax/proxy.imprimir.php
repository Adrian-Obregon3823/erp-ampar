<?php
/**
 * Proxy para API de Impresión Local
 * 
 * PROPÓSITO: Resolver problema de Mixed Content
 * - Frontend (HTTPS) → Proxy PHP (HTTPS) → API Local (HTTP)
 * - Los navegadores bloquean peticiones HTTP desde páginas HTTPS
 * - Este proxy hace la petición HTTP desde el servidor PHP
 * 
 * ⚠️ LIMITACIÓN IMPORTANTE:
 * - Este proxy NO funciona si la API está en localhost:8090 del CLIENTE
 * - El proxy se conecta al localhost del SERVIDOR, no del cliente
 * - Solo usar si la API está en una IP accesible desde el servidor
 *   Ejemplo: http://192.168.1.100:8090, http://10.0.0.50:8090
 * 
 * SOLUCIÓN para localhost del cliente:
 * - Chrome/Edge: Permiten Mixed Content automáticamente para localhost
 * - Firefox: Configurar security.mixed_content.block_active_content = false
 * - O instalar certificado SSL en la API local (https://localhost:8443)
 * 
 * USO:
 * - POST /ajax/proxy.imprimir.php con JSON body
 * - GET /ajax/proxy.imprimir.php?action=health
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cargar configuración
$config = include(__DIR__ . '/../config/printer.config.php');
$printerConfig = $config['printer_api'];

// Construir URL base
$apiUrl = 'http://' . $printerConfig['host'] . ':' . $printerConfig['port'];

// Logging si está en modo debug
function logDebug($message, $data = null) {
    global $printerConfig;
    if ($printerConfig['debug']) {
        error_log('[PROXY IMPRIMIR] ' . $message . ($data ? ': ' . json_encode($data) : ''));
    }
}

try {
    // Obtener datos del POST o GET
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    logDebug('Request recibido', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'data' => $data,
        'get' => $_GET
    ]);
    
    // Determinar endpoint
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'health') {
        $endpoint = $printerConfig['endpoints']['health'];
        $data = null;
        logDebug('Health check solicitado');
    } elseif (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos inválidos o vacíos'
        ]);
        exit;
    } else {
        // Determinar endpoint según los datos
        if (isset($data['folio']) && !isset($data['folios'])) {
            $endpoint = $printerConfig['endpoints']['imprimir'];
            logDebug('Impresión simple', ['folio' => $data['folio']]);
        } elseif (isset($data['folios']) && is_array($data['folios'])) {
            $endpoint = $printerConfig['endpoints']['imprimir_multiple'];
            logDebug('Impresión múltiple', ['count' => count($data['folios'])]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parámetros inválidos. Se requiere "folio" o "folios"'
            ]);
            exit;
        }
    }
    
    $fullUrl = $apiUrl . $endpoint;
    logDebug('Haciendo petición a', ['url' => $fullUrl]);
    
    // Inicializar cURL
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $printerConfig['timeout']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    // Si hay datos, configurar POST
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }
    
    // Ejecutar petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    logDebug('Respuesta recibida', [
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'curl_errno' => $curlErrno
    ]);
    
    // Manejar errores de cURL
    if ($curlErrno) {
        $errorMessages = [
            6 => 'No se pudo resolver el host. Verifica la IP/hostname en printer.config.php',
            7 => 'No se pudo conectar al servidor. Verifica que la API esté corriendo en el puerto ' . $printerConfig['port'],
            28 => 'Timeout: La API no respondió a tiempo',
            52 => 'La API devolvió una respuesta vacía',
        ];
        
        $errorMsg = $errorMessages[$curlErrno] ?? $curlError;
        
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión con la API de impresión',
            'details' => $errorMsg,
            'errno' => $curlErrno,
            'config' => [
                'host' => $printerConfig['host'],
                'port' => $printerConfig['port']
            ]
        ]);
        exit;
    }
    
    // Manejar códigos HTTP no exitosos
    if ($httpCode != 200) {
        http_response_code($httpCode);
        
        // Intentar decodificar respuesta
        $responseData = json_decode($response, true);
        
        echo json_encode([
            'success' => false,
            'error' => 'La API de impresión devolvió un error',
            'http_code' => $httpCode,
            'response' => $responseData ?? $response
        ]);
        exit;
    }
    
    // Respuesta exitosa
    http_response_code(200);
    
    // Validar que sea JSON válido
    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si no es JSON válido, envolver en estructura
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
    } else {
        // Si ya es JSON, enviarlo tal cual
        echo $response;
    }
    
} catch (Exception $e) {
    logDebug('Excepción capturada', ['error' => $e->getMessage()]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del proxy',
        'details' => $e->getMessage()
    ]);
}
?>
