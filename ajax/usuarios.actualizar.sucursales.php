<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener tipos de usuario
*********************************************************************************
*/
$login = new login();
$login->actualizarusuariosucursales($_POST['usuarioid'],$_POST['sucursalid'],$_POST['permiso']);
?>