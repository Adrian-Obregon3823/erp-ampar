<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar el detalle de una solicitud de entrada y salida
*********************************************************************************
*/
?>
<?php   
$esid = $_POST['esid'];
$entradasalida = new entradasalida();
$res = $entradasalida->getinfoentradasalidabyid($esid);
echo json_encode($res);
?>