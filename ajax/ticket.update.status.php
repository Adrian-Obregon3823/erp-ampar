<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para actualizar status de ticket
*********************************************************************************
*/
?>
<?php   
    $tickets = new tickets();
    $tickets->updatestatusticket($_POST['id'],$_POST['status'],$_POST['comentario']);
?>