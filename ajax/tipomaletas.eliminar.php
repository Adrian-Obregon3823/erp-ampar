<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para eliminar un tipo de maleta
*********************************************************************************
*/
?>
<?php
    $maletas = new maletas();
    $maletas->eliminartipomaleta(base64_decode($_POST['idtipomaleta']));
?>