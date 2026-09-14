<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$mostrar_inactivos = isset($_GET['mostrar_inactivos']) ? intval($_GET['mostrar_inactivos']) : 0;
$grupo_linea_id = isset($_GET['grupo_linea_id']) && $_GET['grupo_linea_id'] !== '' ? intval($_GET['grupo_linea_id']) : null;
$division_id = isset($_GET['division_id']) && $_GET['division_id'] !== '' ? intval($_GET['division_id']) : null;
$categoria_id = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? intval($_GET['categoria_id']) : null;

try {
    $db = new FirebirdConnection();

    $sql = "
        SELECT 
            AR.ARTICULO_ID,
            AR.NOMBRE,
            AR.SEGUIMIENTO,
            AR.ESTATUS,
            CA.CLAVE_ARTICULO,
            GL.NOMBRE AS FAMILIA,
            LA.NOMBRE AS CATEGORIA,
            LA.LINEA_ARTICULO_ID,
            LA.GRUPO_LINEA_ID,
            RLD.DIVISION_ID,
            D.DIVISION_NOMBRE AS DIVISION,
            DA.CLAVE AS CLAVE_SAT
        FROM ARTICULOS AR
        LEFT JOIN (
            SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID 
            FROM CLAVES_ARTICULOS 
            WHERE ROL_CLAVE_ART_ID = 17
        ) CA ON CA.ARTICULO_ID = AR.ARTICULO_ID
        LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
        LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
        LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
        LEFT JOIN AMPAR_CAT_DIVISION D ON D.DIVISION_ID = RLD.DIVISION_ID
        LEFT JOIN DATOS_ADICIONALES DA ON DA.NOM_TABLA = 'ARTICULOS' AND DA.ELEM_ID = AR.ARTICULO_ID
        WHERE LA.GRUPO_LINEA_ID >= 29705";

    $params = [];

    if (!$mostrar_inactivos) {
        $sql .= " AND AR.ESTATUS = 'A'";
    }

    if ($grupo_linea_id !== null) {
        $sql .= " AND LA.GRUPO_LINEA_ID = ?";
        $params[] = $grupo_linea_id;
    }

    if ($division_id !== null) {
        $sql .= " AND RLD.DIVISION_ID = ?";
        $params[] = $division_id;
    }

    if ($categoria_id !== null) {
        $sql .= " AND AR.LINEA_ARTICULO_ID = ?";
        $params[] = $categoria_id;
    }

    $sql .= " ORDER BY AR.ARTICULO_ID DESC";

    $result = $db->query($sql, $params);
    $db->close();

    if (!is_array($result)) {
        $result = [];
    }

    echo json_encode([
        'ok' => true,
        'items' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al cargar artículos: ' . $e->getMessage()
    ]);
}
