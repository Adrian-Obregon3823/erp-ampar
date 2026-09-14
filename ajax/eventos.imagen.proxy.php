<?php

/**
 * Proxy de imágenes de la API externa (api_rfid_ampar)
 * Sirve imágenes desde C:\apps\api_rfid_ampar\uploads\fotos_eventos
 * de forma segura, sin exponer la ruta del filesystem.
 */
require_once("../includes/sesion.php");

// =====================================================================
// CONFIGURACIÓN: Ruta física de las fotos de la API externa.
// Si la API cambia de ubicación, solo modifica esta constante.
// El valor debe ser la ruta ABSOLUTA en el servidor donde están
// los archivos de fotos_eventos de la api_rfid_ampar.
// =====================================================================
define('API_FOTOS_BASE', 'C:\\apps\\api_rfid_ampar\\uploads');

$ruta = $_GET['ruta'] ?? '';

// Seguridad: normalizar y verificar que no salga del directorio permitido
$ruta = ltrim(str_replace(['..', '\\'], ['', '/'], $ruta), '/');

if (empty($ruta)) {
    http_response_code(400);
    exit('Ruta inválida');
}

// Construir la ruta absoluta completa
// RUTA_FOTO en BD: fotos_eventos/entrega/8/... 
// Base = uploads → ruta completa: uploads\fotos_eventos\entrega\8\...
// No se quita ningún prefijo, se usa la ruta tal cual.

$rutaAbsoluta = API_FOTOS_BASE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta);

// Verificar que el archivo existe y es un archivo real
if (!file_exists($rutaAbsoluta) || !is_file($rutaAbsoluta)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

// Verificar que la ruta real no salga fuera del directorio permitido (path traversal)
$rutaReal = realpath($rutaAbsoluta);
$baseReal = realpath(API_FOTOS_BASE);
if ($rutaReal === false || strpos($rutaReal, $baseReal) !== 0) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Detectar tipo MIME
$ext = strtolower(pathinfo($rutaReal, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'pdf'  => 'application/pdf',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// Enviar headers y el archivo
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($rutaReal));
header('Cache-Control: private, max-age=3600');
readfile($rutaReal);
exit;
