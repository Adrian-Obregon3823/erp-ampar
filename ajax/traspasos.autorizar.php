<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar el detalle de una solicitud de entrada y salida
*********************************************************************************
*/
?>
<?php   
$traspasos = new traspasos();
$traspasos->autorizartraspasos($_POST['traspasoid'],$_POST['detalles']);
?>