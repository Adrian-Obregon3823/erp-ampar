<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Eventos
*********************************************************************************
*/
header('Content-Type: application/json');
try {
    $eventos = new eventos();
    $res = $eventos->getarticulosbyevento((isset($_GET['eventoid'])?$_GET['eventoid']:""));
    if (is_array($res) && !empty($res)){
        echo json_encode($res);
    }else{
        // Retornar array vacío si no hay artículos
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>