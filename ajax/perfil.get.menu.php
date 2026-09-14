<?php include_once ("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener los menus que existen
*********************************************************************************
*/
$permisos = new Permisos();
$perfil = $_GET['perfil'] ?? 0;
$res = $permisos->getmenus($perfil);
if ($res<>0){
  echo json_encode(array_values($res));
}else{
  $array[0]['MENU_ID'] = '';
  $array[0]['MENU_NOMBRE'] = '';
  echo json_encode($array);
}
?>