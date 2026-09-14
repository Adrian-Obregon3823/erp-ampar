<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/recepcionesmercancia.php");

/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2026
* Ajax para actualizar documentos de recepción de mercancía
*********************************************************************************
*/

if (!isset($_POST['recepcion_id']) || empty($_POST['recepcion_id'])) {
    echo "ID de recepción inválido.";
    exit;
}

$recepcion_id = $_POST['recepcion_id'];
$es_id = $_POST['es_id'] ?? null;
$archivos = $_FILES;

$recepciones = new recepcionesmercancia();

// Llamar al método para actualizar documentos y cambiar el estatus
$resultado = $recepciones->actualizarDocumentosRecepcion($recepcion_id, $es_id, $archivos, $_POST['num_delivery_general'] ?? '');

echo $resultado;
?>
