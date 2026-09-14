<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar una maleta
*********************************************************************************
*/
?>
<?php
    $maletas = new maletas();
    $almacenes = new almacenes();
    
    // Obtener la sucursal a la que pertenece el almacén seleccionado
    $almacen_padre_id = $_POST['maalmacen'];
    $infoalmacen = $almacenes->getinfoalmacen($almacen_padre_id);
    $sucursalid = $infoalmacen[0]['ALMACEN_SUCURSAL_MS'];
    
    $maletas->nuevamaleta($sucursalid, $_POST['matipomaleta'], $_POST['mamaletanom'], $_POST['mamaletades'], $almacen_padre_id);
?>