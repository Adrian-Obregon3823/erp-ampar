<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para actualizar status de un evento especialista
*********************************************************************************
*/
?>
<?php
    $evento = new eventos();
    $evento->updatestatuseventoespecialista($_POST['eventoid'],$_POST['status']);
?>