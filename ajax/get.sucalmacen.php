<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Sucursales - Almacen
*********************************************************************************
*/
$alm = new almacenesv2();
$res = $alm->getcatalogosucursalesalmacenes();
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>