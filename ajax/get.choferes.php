<?php include_once ("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Choferes
*********************************************************************************
*/
$eventos = new eventos();
$res = $eventos->getcatalogochoferes(isset($_GET['sucursalid'])?base64_decode($_GET['sucursalid']):"");
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>