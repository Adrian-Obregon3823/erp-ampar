<?php include_once("../includes/sesion.php"); ?>
<?php require '../vendor/autoload.php'; ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar el detalle de una solicitud de entrada y salida al stock
*********************************************************************************
*/
?>
<?php
$entradasalida = new entradasalida();
$entradasalida->autorizarentradasalida($_POST['esid'], $_POST['tipo'], $_POST['almacenid'], $_POST['detalles']);
?>
