<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener catalogo de almacenes
*********************************************************************************
*/
header('Content-Type: application/json; charset=utf-8');
$almacen = new almacenes();
$res = $almacen->getcatalogoalmacenes((isset($_GET['sucursalid']))?$_GET['sucursalid']:"",(isset($_GET['tipoalmacen']))?$_GET['tipoalmacen']:"",(isset($_GET['status']))?$_GET['status']:"");
if ($res<>0){
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array, JSON_UNESCAPED_UNICODE);
}
?>