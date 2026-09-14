<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

if (isset($_POST['remisionid']) && isset($_POST['invdetid'])) {
    $remisiones = new remisiones();
    $resp = $remisiones->agregarArticuloDetalleRemision(
        $_POST['remisionid'],
        $_POST['invdetid'],
        $_POST['subtotal'],
        $_POST['iva'],
        $_POST['total']
    );
    echo json_encode($resp);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos.']);
}
