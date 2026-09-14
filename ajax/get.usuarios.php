<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener tipos de usuario
*********************************************************************************
*/
$login = new login();
$term = strtoupper($_GET['term']) ?? '';
$res = $login->getcatalogousuarios($term);
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  $array[0]['LABEL'] = '';
  echo json_encode($array);
}
?>