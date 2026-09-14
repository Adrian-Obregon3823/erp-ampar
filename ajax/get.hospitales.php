<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* Ajax para obtener Hospitales activos
*********************************************************************************
*/
try {
    $db = new FirebirdConnection();
    
    // Traemos solo los activos y los ordenamos alfabéticamente
    $sql = "SELECT HOSPITAL_ID AS ID, HOSPITAL_NOMBRE AS NOMBRE 
            FROM AMPAR_CAT_HOSPITALES 
            WHERE HOSPITAL_ACTIVO = 1 
            ORDER BY HOSPITAL_NOMBRE ASC";
            
    $res = $db->query($sql, []);
    
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