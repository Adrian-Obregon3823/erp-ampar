<?php include_once ("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para actualizar los permisos de un perfil
*********************************************************************************
*/
$permisos = new Permisos();
$perfil = $_POST['perfil'];
$menus = $_POST['menus'] ?? [];
$res = $permisos->updateperfilmenu($perfil,$menus);
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>