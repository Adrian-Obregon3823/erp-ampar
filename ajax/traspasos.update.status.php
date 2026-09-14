<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para cambiar status de traspasos
*********************************************************************************
*/
?>
<?php   
    $traspasos = new traspasos();
    $motivo = isset($_POST['motivo_rechazo']) ? trim($_POST['motivo_rechazo']) : null;
    $traspasos->updatestatustraspasos($_POST['id'], $_POST['status'], $motivo);
?>