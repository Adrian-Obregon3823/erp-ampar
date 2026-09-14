<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Proveedores por evento
*********************************************************************************
*/
header('Content-Type: application/json');
try {
    $eve = new eventos();
    $res = $eve->getcatalogoproveedoresbyevento($_GET['eventoid']);
    if (is_array($res) && !empty($res)){
        echo json_encode($res);
    }else{
        // Retornar array vacío si no hay proveedores
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>