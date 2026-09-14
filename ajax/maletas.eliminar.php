<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para eliminar una maleta
*********************************************************************************
*/
?>
<?php
    $maletas = new maletas();
    $maletas->eliminarmaleta($_POST['maletaid']);
?>