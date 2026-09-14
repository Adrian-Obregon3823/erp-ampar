<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para confirmar la finalizacion de un folio de garantia de proveedor
*********************************************************************************
*/
?>
<?php
$entradasalida = new entradasalida();
$entradasalida->finalizarfolioproveedor($_POST['cpid'],$_POST['detalles']);
?>