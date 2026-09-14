<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/recepcionesmercancia.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ocid = $_POST['ocid'] ?? 0;
    $almacenid = $_POST['almacenid'] ?? 0;
    $observaciones = $_POST['observaciones'] ?? '';
    $detalles = $_POST['detalles'] ?? []; // Array of details: articuloid, esperada, recibida, rechazada, motivo, costo, lote, caducidad
    
    $usersesion = $_SESSION['ampar']['usuario'];
    $usuarioid = $usersesion['USUARIO_ID'];

    if ($ocid <= 0 || empty($detalles) || $almacenid <= 0) {
        echo "Error: Faltan datos (Orden de Compra, Almacén o Detalles vacíos).";
        exit;
    }

    $num_delivery_general = $_POST['num_delivery_general'] ?? '';

    $recepciones = new recepcionesmercancia();
    
    $resultado = $recepciones->guardarRecepcion($ocid, $usuarioid, $observaciones, $detalles, $almacenid, $_FILES, $num_delivery_general);

    if ($resultado === true) {
        echo "OK";
    } else {
        echo $resultado;
    }
} else {
    echo "Método no permitido";
}
?>
