<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar el detalle de una garantía de proveedor
*********************************************************************************
*/
?>
<?php   
$cpid = $_POST['cpid'];
$entradasalida = new entradasalida();
$res = $entradasalida->getinfogarantiaproveedorbyid($cpid);
echo json_encode($res);
?>