<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$q      = trim($_POST['q'] ?? $_GET['q'] ?? '');
$page   = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
$limit  = max(5, (int)($_POST['limit'] ?? $_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

function cleanStr(&$item) {
    if (is_string($item)) {
        $item = preg_replace('/\s+/', ' ', trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8')));
    }
}

try {
    $db = new FirebirdConnection();

    $respond = function ($ok, $data = null, $msg = '') {
        echo json_encode(['ok' => $ok, 'data' => $data, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    };

    // ─────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────
    if ($action === 'list') {
        $where  = " WHERE COALESCE(P.ESTATUS, 'N') = 'N' ";
        $params = [];

        if ($q !== '') {
            $like     = '%' . $q . '%';
            $where   .= " AND (
                UPPER(P.NOMBRE)    LIKE UPPER(?) OR
                UPPER(P.EMAIL)     LIKE UPPER(?) OR
                UPPER(P.TELEFONO1) LIKE UPPER(?) OR
                UPPER(P.CONTACTO1) LIKE UPPER(?) OR
                UPPER(P.POBLACION) LIKE UPPER(?)
            )";
            $params = [$like, $like, $like, $like, $like];
        }

        $sqlCount = "SELECT COUNT(*) AS N FROM PROVEEDORES P $where";
        $rows     = $db->query($sqlCount, $params);
        $total    = (int)($rows[0]['N'] ?? 0);

        $sql = "
            SELECT FIRST $limit SKIP $offset
                P.PROVEEDOR_ID,
                P.NOMBRE,
                P.CONTACTO1,
                P.TELEFONO1,
                P.EMAIL,
                P.POBLACION,
                P.CALLE,
                P.NOMBRE_CALLE,
                P.COLONIA,
                P.RFC_CURP,
                IIF(COALESCE(P.ESTATUS, 'N') = 'N', 1, 0) AS ACTIVO
            FROM PROVEEDORES P
            $where
            ORDER BY P.PROVEEDOR_ID DESC
        ";
        $items = $db->query($sql, $params) ?: [];

        array_walk_recursive($items, 'cleanStr');
        $respond(true, ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    // ─────────────────────────────────────────────
    // FKS (debug)
    // ─────────────────────────────────────────────
    if ($action === 'fks') {
        $sql = "
        SELECT 
            rc.RDB\$CONSTRAINT_NAME AS constraint_name,
            relc.RDB\$RELATION_NAME AS table_name,
            idxseg.RDB\$FIELD_NAME AS field_name,
            relc2.RDB\$RELATION_NAME AS target_table,
            idxseg2.RDB\$FIELD_NAME AS target_field
        FROM RDB\$RELATION_CONSTRAINTS rc
        JOIN RDB\$REF_CONSTRAINTS refc ON rc.RDB\$CONSTRAINT_NAME = refc.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS idxseg ON rc.RDB\$INDEX_NAME = idxseg.RDB\$INDEX_NAME
        JOIN RDB\$RELATION_CONSTRAINTS rc2 ON refc.RDB\$CONST_NAME_UQ = rc2.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS idxseg2 ON rc2.RDB\$INDEX_NAME = idxseg2.RDB\$INDEX_NAME
        WHERE rc.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND TRIM(relc.RDB\$RELATION_NAME) = 'PROVEEDORES';
        ";
        $rows = $db->query($sql);
        $respond(true, $rows);
    }

    // ─────────────────────────────────────────────
    // GET (single)
    // ─────────────────────────────────────────────
    elseif ($action === 'get') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID inválido");

        $row = $db->query(
            "SELECT
                P.PROVEEDOR_ID,
                P.NOMBRE,
                P.CONTACTO1,
                P.TELEFONO1,
                P.EMAIL,
                P.CALLE,
                P.NOMBRE_CALLE,
                P.NUM_EXTERIOR,
                P.COLONIA,
                P.POBLACION,
                P.MONEDA_ID,
                P.COND_PAGO_ID,
                P.RFC_CURP,
                P.CODIGO_POSTAL,
                P.CLAVE_REGIMEN_FISCAL,
                P.USO_CFDI,
                P.LIMITE_CREDITO
            FROM PROVEEDORES P WHERE P.PROVEEDOR_ID = ?",
            [$id]
        );

        if (!$row) $respond(false, null, 'No encontrado');
        $data = $row[0];
        array_walk_recursive($data, 'cleanStr');
        $respond(true, $data);
    }

    // ─────────────────────────────────────────────
    // SAVE (insert o update)
    // ─────────────────────────────────────────────
    elseif ($action === 'save') {
        $id        = (int)($_POST['id']          ?? 0);
        $nombre    = trim($_POST['NOMBRE']       ?? '');
        $contacto1 = trim($_POST['CONTACTO1']    ?? '');
        $telefono1 = trim($_POST['TELEFONO1']    ?? '');
        $email     = trim($_POST['EMAIL']        ?? '');
        $calle     = trim($_POST['CALLE']        ?? '');
        $nomCalle  = trim($_POST['NOMBRE_CALLE'] ?? '');
        $numExt    = trim($_POST['NUM_EXTERIOR'] ?? '');
        $colonia   = trim($_POST['COLONIA']      ?? '');
        $poblacion = trim($_POST['UBICACION']    ?? $_POST['POBLACION'] ?? ''); // Soporte para name="UBICACION"
        $rfc       = trim($_POST['RFC_CURP']     ?? '');
        $cp        = trim($_POST['CODIGO_POSTAL'] ?? '');
        $regimen   = trim($_POST['CLAVE_REGIMEN_FISCAL'] ?? '');
        $usoCfdi   = trim($_POST['USO_CFDI']     ?? '');
        $limiteCred= !empty($_POST['LIMITE_CREDITO']) ? (float)$_POST['LIMITE_CREDITO'] : 0;
        $condPago  = !empty($_POST['COND_PAGO_ID']) ? (int)$_POST['COND_PAGO_ID'] : null;
        $monedaId  = !empty($_POST['MONEDA_ID'])    ? (int)$_POST['MONEDA_ID']    : null;

        if (empty($nombre)) {
            $respond(false, null, 'El nombre comercial es requerido.');
        }

        if ($id > 0) {
            $db->execute(
                "UPDATE PROVEEDORES SET
                    NOMBRE=?, CONTACTO1=?, TELEFONO1=?, EMAIL=?,
                    CALLE=?, NOMBRE_CALLE=?, NUM_EXTERIOR=?, COLONIA=?, POBLACION=?,
                    RFC_CURP=?, CODIGO_POSTAL=?, CLAVE_REGIMEN_FISCAL=?, USO_CFDI=?, LIMITE_CREDITO=?,
                    MONEDA_ID=COALESCE(?, (SELECT FIRST 1 MONEDA_ID FROM MONEDAS)),
                    COND_PAGO_ID=COALESCE(?, (SELECT FIRST 1 COND_PAGO_ID FROM CONDICIONES_PAGO_CP))
                WHERE PROVEEDOR_ID=?",
                [
                    $nombre,
                    $contacto1 ?: null,
                    $telefono1 ?: null,
                    $email     ?: null,
                    $calle     ?: null,
                    $nomCalle  ?: null,
                    $numExt    ?: null,
                    $colonia   ?: null,
                    $poblacion ?: null,
                    $rfc       ?: null,
                    $cp        ?: null,
                    $regimen   ?: null,
                    $usoCfdi   ?: null,
                    $limiteCred,
                    $monedaId,
                    $condPago,
                    $id
                ]
            );
            $respond(true, null, 'Proveedor actualizado correctamente.');
        } else {
            $resMax = $db->query("SELECT MAX(PROVEEDOR_ID) AS MAX_ID FROM PROVEEDORES");
            $new_id = ($resMax[0]['MAX_ID'] ?? 0) + 1;

            $db->execute(
                "INSERT INTO PROVEEDORES
                    (PROVEEDOR_ID, NOMBRE, CONTACTO1, TELEFONO1, EMAIL,
                     CALLE, NOMBRE_CALLE, NUM_EXTERIOR, COLONIA, POBLACION,
                     RFC_CURP, CODIGO_POSTAL, CLAVE_REGIMEN_FISCAL, USO_CFDI,
                     MONEDA_ID, COND_PAGO_ID,
                     CARGA_IMPUESTOS, RETENER_IMPUESTOS, SUJETO_IEPS, EXTRANJERO,
                     LIMITE_CREDITO, ORDEN_MINIMA, ACTIVIDAD_PRINCIPAL)
                VALUES
                    (?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?,
                     ?, ?, ?, ?,
                     COALESCE(?, (SELECT FIRST 1 MONEDA_ID FROM MONEDAS)),
                     COALESCE(?, (SELECT FIRST 1 COND_PAGO_ID FROM CONDICIONES_PAGO_CP)),
                     'S', 'N', 'N', 'N',
                     ?, 0, 85)",
                [
                    $new_id, $nombre,
                    $contacto1 ?: null,
                    $telefono1 ?: null,
                    $email     ?: null,
                    $calle     ?: null,
                    $nomCalle  ?: null,
                    $numExt    ?: null,
                    $colonia   ?: null,
                    $poblacion ?: null,
                    $rfc       ?: null,
                    $cp        ?: null,
                    $regimen   ?: null,
                    $usoCfdi   ?: null,
                    $monedaId,
                    $condPago,
                    $limiteCred
                ]
            );
            $respond(true, null, 'Proveedor creado correctamente.');
        }
    }

    // ─────────────────────────────────────────────
    // SET ACTIVO
    // ─────────────────────────────────────────────
    elseif ($action === 'set_activo') {
        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 1);
        if ($id <= 0) throw new Exception("ID inválido");
        $val = $activo ? 'N' : 'B';
        $db->execute("UPDATE PROVEEDORES SET ESTATUS=? WHERE PROVEEDOR_ID=?", [$val, $id]);
        $respond(true, null, ($activo ? 'Activado' : 'Desactivado'));
    }

    else {
        throw new Exception("Acción inválida: $action");
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
