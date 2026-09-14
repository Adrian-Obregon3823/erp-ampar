<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json');

$notifid   = intval($_POST['notifid']   ?? 0);
$eventoid  = intval($_POST['eventoid']  ?? 0);
$tipo      = $_POST['tipo']      ?? '';
$respuesta = $_POST['respuesta'] ?? '';

if (
    $notifid  <= 0 ||
    $eventoid < 0 ||
    !in_array($tipo,      ['ESPECIALISTA', 'CHOFER', 'ENTRADA', 'RECEPCION_OC', 'RECEPCION_RECHAZADA', 'RECEPCION_TRASPASO']) ||
    !in_array($respuesta, ['ACEPTADO', 'RECHAZADO', 'LEIDO'])
) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

if ($tipo === 'ENTRADA' || $respuesta === 'LEIDO') {
    notificaciones::marcarLeida($notifid, $usersesion['USUARIO_ID']);
    echo json_encode(['ok' => true]);
    exit;
}

$ok = notificaciones::responder($notifid, $usersesion['USUARIO_ID'], $respuesta, $eventoid, $tipo);
echo json_encode(['ok' => $ok]);
