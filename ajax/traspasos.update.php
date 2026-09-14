<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para editar traspasos
*********************************************************************************
*/
?>
<?php
    $traspasos = new traspasos();
    $traspasos->actualizarTraspasoId($_POST['desucursalid'],$_POST['asucursalid'],$_POST['deinvalmacenid'],$_POST['ainvalmacenid'],$_POST['traspasoid'],$_POST['invmotivo'],$_POST['cambios']);
?>