<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* Ajax para obtener Tipos de Evento filtrados por Subgrupo
*********************************************************************************
*/
$subgrupoid = isset($_GET['subgrupoid']) ? (int)$_GET['subgrupoid'] : 0;

try {
    $db = new FirebirdConnection();
    
    $sql = "SELECT TIPOEVENTO_ID AS ID, TIPOEVENTO_NOMBRE AS NOMBRE 
            FROM AMPAR_CAT_TIPOEVENTO 
            WHERE TIPOEVENTO_SUBGRUPOID = ? AND TIPOEVENTO_ACTIVO = 1 
            ORDER BY TIPOEVENTO_NOMBRE ASC";
            
    $res = $db->query($sql, [$subgrupoid]);
    
    if ($res && count($res) > 0) {
        echo json_encode($res);
    } else {
        $array[0]['ID'] = '';
        $array[0]['NOMBRE'] = '';
        echo json_encode($array);
    }
} catch (Throwable $e) {
    $array[0]['ID'] = '';
    $array[0]['NOMBRE'] = '';
    echo json_encode($array);
}
?>