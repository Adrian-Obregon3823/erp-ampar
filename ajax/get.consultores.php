<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener consultores
*********************************************************************************
*/
$log = new login();
$res = $log->getconsultorestodos();
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['id'] = '';
  $array[0]['nombre'] = '';
  echo json_encode($array);
}
?>