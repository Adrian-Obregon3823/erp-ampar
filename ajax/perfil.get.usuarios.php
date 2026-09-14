<?php include_once ("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener los usuarios
*********************************************************************************
*/
$permisos = new Permisos();
$res = $permisos->getusuarios();
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['USUARIO_ID'] = '';
  $array[0]['USUARIO_NOMBRE'] = '';
  echo json_encode($array);
}
?>