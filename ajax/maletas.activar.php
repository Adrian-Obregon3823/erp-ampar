<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados.
* Ajax para reactivar una maleta
*********************************************************************************
*/
?>
<?php
    $maletas = new maletas();
    $maletas->activarmaleta($_POST['maletaid']);
?>
