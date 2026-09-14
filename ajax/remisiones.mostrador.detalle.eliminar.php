<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

if (isset($_POST['detalleid'])) {
    $remisiones = new remisiones();
    $resp = $remisiones->eliminarArticuloDetalleRemision($_POST['detalleid']);
    echo json_encode($resp);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID de detalle no proporcionado.']);
}
