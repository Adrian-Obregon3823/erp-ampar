<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

$detalleid = isset($_POST['detalleid']) ? (int)$_POST['detalleid'] : 0;
$subtotal  = isset($_POST['subtotal'])  ? (float)$_POST['subtotal'] : 0;
$iva       = isset($_POST['iva'])       ? (float)$_POST['iva']      : 0;
$total     = isset($_POST['total'])     ? (float)$_POST['total']    : 0;

if ($detalleid <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de detalle inválido.']);
    exit;
}

$remisiones = new remisiones();
$resp = $remisiones->actualizarPrecioDetalleRemision($detalleid, $subtotal, $iva, $total);
echo json_encode($resp);
