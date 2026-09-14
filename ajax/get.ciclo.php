<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener años
*********************************************************************************
*/
$array = [];
$j=0;
for ($i = date("Y"); $i >= 2025; $i--) {
    $array[$j]['id'] = $i;
    $array[$j]['nombre'] = $i;
    $j++;
}
echo json_encode($array);
?>