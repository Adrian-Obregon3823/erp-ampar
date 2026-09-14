<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Acción no válida'];

if ($action === 'get_users') {
    $db = new FirebirdConnection();
    $sql = "
        SELECT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO AS USUARIO_USERNAME, P.PERFIL_NOMBRE
        FROM AMPAR_CAT_USUARIOS U
        JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID
        JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
        WHERE U.USUARIO_ACTIVO = 1
        ORDER BY P.PERFIL_NOMBRE, U.USUARIO_NOMBRE
    ";
    $users = $db->query($sql);
    $db->close();
    
    echo json_encode(['success' => true, 'data' => $users]);
    exit;
}

if ($action === 'get_assignments') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código requerido']);
        exit;
    }
    
    $db = new FirebirdConnection();
    $sql = "SELECT USUARIO_ID FROM AMPAR_CONF_USUARIOS_ACTIVIDADES WHERE ACTIVIDAD_CODIGO = ?";
    $res = $db->query($sql, [$codigo]);
    $db->close();
    
    $assigned = [];
    if (!empty($res)) {
        foreach($res as $r) {
            $assigned[] = (int)$r['USUARIO_ID'];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $assigned]);
    exit;
}

if ($action === 'save_assignments') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $users = $_POST['users'] ?? []; // Array of user IDs
    
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código requerido']);
        exit;
    }
    
    $db = new FirebirdConnection();
    // Delete existing
    $db->execute("DELETE FROM AMPAR_CONF_USUARIOS_ACTIVIDADES WHERE ACTIVIDAD_CODIGO = ?", [$codigo]);
    
    // Insert new
    if (is_array($users)) {
        foreach ($users as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) {
                try {
                    $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES (?, ?)", [$uid, $codigo]);
                } catch(Exception $e){}
            }
        }
    }
    
    $db->close();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'save_custom_activity') {
    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $icono = trim($_POST['icono'] ?? 'mdi-checkbox-marked-circle-outline');
    $color = trim($_POST['color'] ?? 'primary');
    $url = trim($_POST['url'] ?? '#');
    $query_sql = trim($_POST['query_sql'] ?? '');
    
    if (empty($titulo) || empty($query_sql)) {
        echo json_encode(['success' => false, 'message' => 'Título y Query SQL son obligatorios']);
        exit;
    }
    
    $db = new FirebirdConnection();
    if ($id > 0) {
        $sql = "UPDATE AMPAR_CONF_ACT_CUSTOM 
                SET ACTIVIDAD_TITULO = ?, ACTIVIDAD_DESCRIPCION = ?, ACTIVIDAD_ICONO = ?, ACTIVIDAD_COLOR = ?, ACTIVIDAD_URL = ?, ACTIVIDAD_QUERY = ? 
                WHERE ACTIVIDAD_ID = ?";
        $db->execute($sql, [$titulo, $desc, $icono, $color, $url, $query_sql, $id]);
    } else {
        $sql = "INSERT INTO AMPAR_CONF_ACT_CUSTOM 
                (ACTIVIDAD_TITULO, ACTIVIDAD_DESCRIPCION, ACTIVIDAD_ICONO, ACTIVIDAD_COLOR, ACTIVIDAD_URL, ACTIVIDAD_QUERY) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $db->execute($sql, [$titulo, $desc, $icono, $color, $url, $query_sql]);
    }
    $db->close();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_custom_activity') {
    $id = (int)($_POST['id'] ?? 0);
    $codigo = 'CUSTOM_' . $id;
    
    if ($id > 0) {
        $db = new FirebirdConnection();
        $db->execute("DELETE FROM AMPAR_CONF_USUARIOS_ACTIVIDADES WHERE ACTIVIDAD_CODIGO = ?", [$codigo]);
        $db->execute("DELETE FROM AMPAR_CONF_ACT_CUSTOM WHERE ACTIVIDAD_ID = ?", [$id]);
        $db->close();
    }
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode($response);
