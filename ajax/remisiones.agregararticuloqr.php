<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar remision
*********************************************************************************
*/
?>
<?php
$stockid    = isset($_POST['stockid']) ? intval($_POST['stockid']) : 0;
$remisionid = isset($_POST['remisionid']) ? intval($_POST['remisionid']) : 0;
$sucursalid = isset($_POST['sucursalid']) ? intval($_POST['sucursalid']) : 0;
$precio     = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;

$remisiones = new remisiones();
$remisiones->agregararticuloqr(null,$sucursalid, $remisionid, $stockid, $precio);
?>