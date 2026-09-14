<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para actualizar status de un inventario
*********************************************************************************
*/
?>
<?php
    $almacenes = new almacenes();
    $almacenes->updatestatusinventario($_POST['id'],$_POST['status']);
?>