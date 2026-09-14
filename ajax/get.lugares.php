<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN 
?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Lugares por Almacén
*********************************************************************************
*/
$almacenid = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;

$eventos = new eventos();
$res = $eventos->getcatalogolugares($almacenid);

if ($res <> 0) {
  echo json_encode($res);
} else {
  $array = [];
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>