<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar una maleta
*********************************************************************************
*/
?>
<?php
    $maletas = new maletas();
    $maletas->editmaleta($_POST['maeditmaletaid'],$_POST['maeditsucalmacen'],$_POST['maedittipomaleta'],$_POST['maeditmaletanom'],$_POST['maeditmaletades'],$_POST['maeditstatus']);
?>