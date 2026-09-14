<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener informacion de un almacen
*********************************************************************************
*/
?>
<?php
$almacenes = new almacenes();
$res = $almacenes->getinfoalmacen($_GET['almacenid']);
if ($res<>0){
  echo json_encode($res[0]);
}else{
  echo json_encode(array());
}
?>
