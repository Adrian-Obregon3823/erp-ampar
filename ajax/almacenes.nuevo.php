<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para agregar un almacen
*********************************************************************************
*/
?>
<?php
    $almacenes = new almacenes();
    $almacenes->nuevo($_POST['altipoalmacen'],$_POST['almacennom'],$_POST['aldescripcion'],$_POST['alcalle'],$_POST['alnumext'],$_POST['alnumint'],$_POST['alcolonia'],$_POST['alcp'],$_POST['altel'],(isset($_POST['alpais'])?$_POST['alpais']:''),(isset($_POST['alestados'])?$_POST['alestados']:''),(isset($_POST['almunicipios'])?$_POST['almunicipios']:''),$_POST['alsucursal']);
?>