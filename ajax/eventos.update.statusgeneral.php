<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para actualizar status de un evento
*********************************************************************************
*/
?>
<?php
    $evento = new eventos();
    $evento->updatestatusevento($_POST['id'],$_POST['status']);
?>