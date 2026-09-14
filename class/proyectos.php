<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2026
* Clase de Proyectos
*********************************************************************************
*/

class proyectos
{
    function getproyectos($usuarioid, $statusgeneral = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "
        SELECT 
            P.*, 
            C.NOMBRE AS CLIENTE_NOMBRE,
            U.USUARIO_NOMBRE AS RESPONSABLE_NOMBRE,
            EG.STATUS_NOMBRE, 
            EG.STATUS_COLOR
        FROM AMPAR_HIS_PROYECTOS P
        LEFT JOIN CLIENTES C 
            ON C.CLIENTE_ID = P.PROYECTO_CLIENTEID
        LEFT JOIN AMPAR_CAT_USUARIOS U 
            ON U.USUARIO_ID = P.PROYECTO_RESPONSABLEID
        LEFT JOIN AMPAR_CONF_STATUS EG 
            ON EG.STATUS_ID = P.PROYECTO_STATUSGENERAL
        WHERE P.PROYECTO_SUCURSALID IN (
            SELECT USUARIOSALMACENES_ALMACENID
            FROM AMPAR_CAT_USUARIOSALMACENES
            WHERE USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
        )
        ";

        if ($statusgeneral !== "" && is_numeric($statusgeneral)) {
            $sql .= " AND P.PROYECTO_STATUSGENERAL = " . (int)$statusgeneral;
        }

        $sql .= " ORDER BY P.PROYECTO_ID DESC";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getproyectobyid($proyectoid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT 
                P.*, 
                C.NOMBRE AS CLIENTE_NOMBRE,
                U.USUARIO_NOMBRE AS RESPONSABLE_NOMBRE,
                EG.STATUS_NOMBRE STATUS_NOMBREGRAL, 
                EG.STATUS_COLOR STATUS_COLORGRAL,
                SU.ALMACEN_NOMBRE SUCURSAL_NOMBRE
            FROM AMPAR_HIS_PROYECTOS P
            LEFT JOIN CLIENTES C ON C.CLIENTE_ID = P.PROYECTO_CLIENTEID
            LEFT JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = P.PROYECTO_RESPONSABLEID
            LEFT JOIN AMPAR_CONF_STATUS EG ON EG.STATUS_ID = P.PROYECTO_STATUSGENERAL
            LEFT JOIN AMPAR_HIS_ALMACEN SU ON SU.ALMACEN_ID = P.PROYECTO_SUCURSALID
            WHERE P.PROYECTO_ID = " . (int)$proyectoid . "
        ";
        $result = $db->query($sql);
        
        $maletas = [];
        if ($result && count($result) > 0) {
            $sqlMaletas = "
                SELECT 
                    EM.PROYECTOMALETA_MALETAID AS MALETA_ID,
                    EM.PROYECTOMALETA_CUMPLIMIENTO AS CUMPLIMIENTO,
                    ec_eq.FOLIO AS ALMACEN_FOLIO,
                    COALESCE(ar.NOMBRE, ar_eq.NOMBRE) AS ALMACEN_NOMBRE,
                    CAST(CASE WHEN EM.PROYECTOMALETA_MALETAID > 0 THEN 'ARTICULO' ELSE 'EQUIPO' END AS VARCHAR(20)) AS TIPO
                FROM AMPAR_HIS_PROYECTOSMALETAS EM
                LEFT JOIN ARTICULOS ar ON ar.ARTICULO_ID = EM.PROYECTOMALETA_MALETAID AND EM.PROYECTOMALETA_MALETAID > 0
                LEFT JOIN AMPAR_EQUIPOCAPITAL ec_eq ON ec_eq.EQUIPOCAPITAL_ID = -EM.PROYECTOMALETA_MALETAID AND EM.PROYECTOMALETA_MALETAID < 0
                LEFT JOIN ARTICULOS ar_eq ON ar_eq.ARTICULO_ID = ec_eq.ARTICULO_ID
                WHERE EM.PROYECTOMALETA_PROYECTOID = " . (int)$proyectoid . "
            ";
            $maletas = $db->query($sqlMaletas) ?: [];
            $result[0]['_MALETAS'] = $maletas;
        }
        
        $db->close();
        return $result;
    }

    function nuevoproyecto(
        $psucalmacen,
        $pconcepto,
        $pclienteid,
        $pfechai,
        $pfechaf,
        $presponsableid,
        $pcumplimiento,
        $pmaletaid,
        $proyecto_tipo = 1,
        $pcumplimientos = [],
        $psourcealmacenid = null,
        $pplazo = null
    ) {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        // 1. Normalizar fechas
        $fi = DateTime::createFromFormat('Y-m-d\TH:i', $pfechai);
        $ff = DateTime::createFromFormat('Y-m-d\TH:i', $pfechaf);
        if ($fi) $pfechai = $fi->format('Y-m-d H:i:s');
        if ($ff) $pfechaf = $ff->format('Y-m-d H:i:s');

        $didBegin = false;
        if (method_exists($db, 'beginTransaction')) {
            $db->beginTransaction();
            $didBegin = true;
        }

        try {
            // Generar folio de proyecto (similar a eventos pero con PRY-)
            $sqlFolio = "
                SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(PROYECTO_FOLIO FROM 5 FOR 5) AS INTEGER)), 0) + 1, 5, '0')
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2)) AS FOLIO
                FROM AMPAR_HIS_PROYECTOS
            ";
            $resFolio = $db->query($sqlFolio);
            $newFolio = 'PRY-' . ($resFolio[0]['FOLIO'] ?? '00001-26');

            // 1.1 Crear almacén para todos los tipos de proyecto
            $newAlmacenId = null;
            $tipoAlmacen = null;
            $prefijoAlmacen = '';
            if ($proyecto_tipo == 1) {
                $tipoAlmacen = 4; // CONSIGNA
                $prefijoAlmacen = 'CONSIGNA ';
            } elseif ($proyecto_tipo == 2) {
                $tipoAlmacen = 6; // RENTA
                $prefijoAlmacen = 'RENTA ';
            } elseif ($proyecto_tipo == 3) {
                $tipoAlmacen = 5; // COMODATO
                $prefijoAlmacen = 'COMODATO ';
            }

            if ($tipoAlmacen && $pclienteid) {
                $resCli = $db->query("SELECT NOMBRE FROM CLIENTES WHERE CLIENTE_ID = ?", [$pclienteid]);
                $cliName = !empty($resCli) ? trim($resCli[0]['NOMBRE']) : 'CLIENTE';
                $almName = substr($prefijoAlmacen . $newFolio . ' - ' . $cliName, 0, 50);
                
                $sqlAlm = "
                    INSERT INTO AMPAR_HIS_ALMACEN
                    (
                        ALMACEN_FOLIO, ALMACEN_TIPOALMACEN, ALMACEN_NOMBRE, ALMACEN_DESCRIPCION,
                        ALMACEN_SUCURSAL_MS, ALMACEN_STATUS
                    ) VALUES (
                        (SELECT 'AL' || LPAD(COALESCE(MAX(CAST(SUBSTRING(ALMACEN_FOLIO FROM 3) AS INTEGER)),0) + 1, 3, '0')
                         FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_FOLIO STARTING WITH 'AL'),
                        ?, ?, ?, ?, 17
                    )
                ";
                $db->execute($sqlAlm, [
                    $tipoAlmacen,
                    $almName,
                    "Almacén para el proyecto " . $newFolio,
                    $psucalmacen
                ]);

                $resAlmId = $db->query("SELECT MAX(ALMACEN_ID) AS NEW_ALM_ID FROM AMPAR_HIS_ALMACEN");
                $newAlmacenId = $resAlmId[0]['NEW_ALM_ID'] ?? null;
            }

            $sqlInsert = "
                INSERT INTO AMPAR_HIS_PROYECTOS
                (
                    PROYECTO_FOLIO, PROYECTO_SUCURSALID, PROYECTO_FECHACREACION, PROYECTO_FECHAI, PROYECTO_FECHAF,
                    PROYECTO_CONCEPTO, PROYECTO_CLIENTEID, PROYECTO_RESPONSABLEID, PROYECTO_CUMPLIMIENTO, PROYECTO_STATUSGENERAL,
                    PROYECTO_TIPO, PROYECTO_ALMACEN_CONSIGNA_ID, PROYECTO_PLAZO_CUMPLIMIENTO
                )
                VALUES
                (
                    ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, 14, ?, ?, ?
                )
            ";
            $db->execute($sqlInsert, [
                $newFolio,
                $psucalmacen,
                $pfechai,
                $pfechaf,
                $pconcepto,
                $pclienteid ?: null,
                $presponsableid ?: null,
                $pcumplimiento ?: 0,
                $proyecto_tipo,
                $newAlmacenId,
                $pplazo ?: null
            ]);

            // Obtener el ID insertado
            $resId = $db->query("SELECT MAX(PROYECTO_ID) AS NEW_ID FROM AMPAR_HIS_PROYECTOS");
            $proyectoid = $resId[0]['NEW_ID'];

            if (!$proyectoid) {
                throw new Exception("Error al insertar proyecto.");
            }

            // Guardar artículos / equipos capital
            if (!empty($pmaletaid) && is_array($pmaletaid)) {
                foreach ($pmaletaid as $mal) {
                    $cumpVal = isset($pcumplimientos[$mal]) ? (int)$pcumplimientos[$mal] : 1;
                    $db->execute("
                        INSERT INTO AMPAR_HIS_PROYECTOSMALETAS (PROYECTOMALETA_PROYECTOID, PROYECTOMALETA_MALETAID, PROYECTOMALETA_CUMPLIMIENTO)
                        VALUES (?, ?, ?)
                    ", [$proyectoid, $mal, $cumpVal]);
                }
            }

            // Bitacora
            $sqlBit = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS)
                    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)";
            
            $comentario = 'SOLICITUD DE PROYECTO FOLIO:<strong>' . $newFolio . '</strong> (Sucursal ID: ' . $psucalmacen . ')';
            $db->execute($sqlBit, [
                $comentario,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $psucalmacen
            ]);

            if ($didBegin && method_exists($db, 'commit')) {
                $db->commit();
            }
            $db->close();
            return ""; // Éxito
        } catch (Exception $e) {
            if ($didBegin && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            $db->close();
            return "Error al guardar proyecto: " . $e->getMessage();
        }
    }

    function editarproyecto($proyectoid, $psucalmacen, $pconcepto, $pclienteid, $pfechai, $pfechaf, $presponsableid, $pcumplimiento, $pmaletaid, $proyecto_tipo = 1, $pcumplimientos = [], $psourcealmacenid = null, $pplazo = null)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        // 1. Normalizar fechas
        $fi = DateTime::createFromFormat('Y-m-d\TH:i', $pfechai);
        $ff = DateTime::createFromFormat('Y-m-d\TH:i', $pfechaf);
        if ($fi) $pfechai = $fi->format('Y-m-d H:i:s');
        if ($ff) $pfechaf = $ff->format('Y-m-d H:i:s');

        $didBegin = false;
        if (method_exists($db, 'beginTransaction')) {
            $db->beginTransaction();
            $didBegin = true;
        }

        try {
            $sqlUpdate = "
                UPDATE AMPAR_HIS_PROYECTOS
                SET PROYECTO_FECHAI = ?,
                    PROYECTO_FECHAF = ?,
                    PROYECTO_CONCEPTO = ?,
                    PROYECTO_CLIENTEID = ?,
                    PROYECTO_RESPONSABLEID = ?,
                    PROYECTO_CUMPLIMIENTO = ?,
                    PROYECTO_TIPO = ?,
                    PROYECTO_PLAZO_CUMPLIMIENTO = ?
                WHERE PROYECTO_ID = ?
            ";
            $db->execute($sqlUpdate, [
                $pfechai,
                $pfechaf,
                $pconcepto,
                $pclienteid ?: null,
                $presponsableid ?: null,
                $pcumplimiento ?: 0,
                $proyecto_tipo,
                $pplazo ?: null,
                $proyectoid
            ]);

            // Borrar maletas previas
            $db->execute("DELETE FROM AMPAR_HIS_PROYECTOSMALETAS WHERE PROYECTOMALETA_PROYECTOID = ?", [$proyectoid]);

            // Guardar nuevas maletas
            if (!empty($pmaletaid) && is_array($pmaletaid)) {
                foreach ($pmaletaid as $mal) {
                    $cumpVal = isset($pcumplimientos[$mal]) ? (int)$pcumplimientos[$mal] : 1;
                    $db->execute("
                        INSERT INTO AMPAR_HIS_PROYECTOSMALETAS (PROYECTOMALETA_PROYECTOID, PROYECTOMALETA_MALETAID, PROYECTOMALETA_CUMPLIMIENTO)
                        VALUES (?, ?, ?)
                    ", [$proyectoid, $mal, $cumpVal]);
                }
            }

            // Bitacora
            $sqlBit = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS)
                    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)";
            
            $comentario = 'EDICIÓN DE PROYECTO ID:<strong>' . $proyectoid . '</strong>';
            $db->execute($sqlBit, [
                $comentario,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $psucalmacen
            ]);

            if ($didBegin && method_exists($db, 'commit')) {
                $db->commit();
            }
            $db->close();
            return ""; // Éxito
        } catch (Exception $e) {
            if ($didBegin && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            $db->close();
            return "Error al actualizar proyecto: " . $e->getMessage();
        }
    }

    function updatestatus($proyectoid, $status)
    {
        $db = new FirebirdConnection();
        $sql = "UPDATE AMPAR_HIS_PROYECTOS SET PROYECTO_STATUSGENERAL = ? WHERE PROYECTO_ID = ?";
        $res = $db->execute($sql, [(int)$status, (int)$proyectoid]);
        $db->close();
        return $res;
    }

    function getcumplimiento($proyectoid)
    {
        $db = new FirebirdConnection(true);
        
        // 1. Obtener plazo del proyecto
        $resProj = $db->query("SELECT PROYECTO_PLAZO_CUMPLIMIENTO FROM AMPAR_HIS_PROYECTOS WHERE PROYECTO_ID = ?", [$proyectoid]);
        $plazo = !empty($resProj) ? trim($resProj[0]['PROYECTO_PLAZO_CUMPLIMIENTO']) : 'TODO';

        // 2. Calcular fecha de inicio según plazo para reseteo de cumplimiento por período calendario
        $startDate = null;
        if ($plazo === 'SEMANA') {
            $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        } elseif ($plazo === 'MES') {
            $startDate = date('Y-m-01 00:00:00');
        } elseif ($plazo === 'BIMENSUAL') {
            $currentMonth = (int)date('m');
            $startMonth = $currentMonth % 2 === 0 ? $currentMonth - 1 : $currentMonth;
            $startDate = date("Y-" . sprintf("%02d", $startMonth) . "-01 00:00:00");
        } elseif ($plazo === 'TRIMESTRAL') {
            $currentMonth = (int)date('m');
            if ($currentMonth <= 3) $startMonth = 1;
            elseif ($currentMonth <= 6) $startMonth = 4;
            elseif ($currentMonth <= 9) $startMonth = 7;
            else $startMonth = 10;
            $startDate = date("Y-" . sprintf("%02d", $startMonth) . "-01 00:00:00");
        } elseif ($plazo === 'SEMESTRAL') {
            $currentMonth = (int)date('m');
            $startMonth = $currentMonth <= 6 ? 1 : 7;
            $startDate = date("Y-" . sprintf("%02d", $startMonth) . "-01 00:00:00");
        }

        $dateCondition = "";
        $queryParams = [];
        if ($startDate !== null) {
            $dateCondition = " AND R.REMISION_FECHA >= CAST(? AS TIMESTAMP) ";
            $queryParams[] = $startDate;
        }
        $queryParams[] = $proyectoid;

        $sqlItems = "
            SELECT 
                em.PROYECTOMALETA_MALETAID AS ITEM_ID,
                em.PROYECTOMALETA_CUMPLIMIENTO AS META,
                COALESCE(ar.NOMBRE, ar_eq.NOMBRE) AS ARTICULO_NOMBRE,
                COALESCE('', ec_eq.FOLIO) AS STOCK_FOLIO,
                (
                    SELECT COUNT(*) 
                    FROM AMPAR_HIS_REMISIONESARTICULOS RA
                    JOIN AMPAR_HIS_REMISIONES R ON R.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                    JOIN AMPAR_HIS_STOCK st_rem ON st_rem.STOCK_ID = RA.REMISIONARTICULO_STOCKID
                    WHERE R.REMISION_PROYECTOID = em.PROYECTOMALETA_PROYECTOID 
                      AND R.REMISION_STATUS = 3
                      $dateCondition
                      AND (
                          (em.PROYECTOMALETA_MALETAID > 0 AND st_rem.STOCK_ARTICULOID = em.PROYECTOMALETA_MALETAID)
                          OR
                          (em.PROYECTOMALETA_MALETAID < 0 AND st_rem.STOCK_ARTICULOID = ec_eq.ARTICULO_ID)
                      )
                ) + (
                    SELECT COUNT(*)
                    FROM AMPAR_HIS_STOCK st_cons
                    JOIN AMPAR_HIS_PROYECTOS PRY ON PRY.PROYECTO_ID = em.PROYECTOMALETA_PROYECTOID
                    WHERE st_cons.STOCK_ALMACENIDACTUAL = PRY.PROYECTO_ALMACEN_CONSIGNA_ID
                      AND st_cons.STOCK_STOCKSTATUSID = 1
                      AND (
                          (em.PROYECTOMALETA_MALETAID > 0 AND st_cons.STOCK_ARTICULOID = em.PROYECTOMALETA_MALETAID)
                          OR
                          (em.PROYECTOMALETA_MALETAID < 0 AND st_cons.STOCK_ARTICULOID = ec_eq.ARTICULO_ID)
                      )
                ) AS REMISIONADOS
            FROM AMPAR_HIS_PROYECTOSMALETAS em
            LEFT JOIN ARTICULOS ar ON ar.ARTICULO_ID = em.PROYECTOMALETA_MALETAID AND em.PROYECTOMALETA_MALETAID > 0
            LEFT JOIN AMPAR_EQUIPOCAPITAL ec_eq ON ec_eq.EQUIPOCAPITAL_ID = -em.PROYECTOMALETA_MALETAID AND em.PROYECTOMALETA_MALETAID < 0
            LEFT JOIN ARTICULOS ar_eq ON ar_eq.ARTICULO_ID = ec_eq.ARTICULO_ID
            WHERE em.PROYECTOMALETA_PROYECTOID = ?
        ";
        $items = $db->query($sqlItems, $queryParams) ?: [];
        $db->close();

        $globalMeta = 0;
        $globalRemisionados = 0;
        $allComplied = true;

        foreach ($items as &$it) {
            $metaVal = (int)$it['META'];
            $remisVal = (int)$it['REMISIONADOS'];
            $globalMeta += $metaVal;
            $globalRemisionados += min($remisVal, $metaVal);
            
            $it['CUMPLE'] = ($remisVal >= $metaVal);
            if (!$it['CUMPLE']) {
                $allComplied = false;
            }
        }

        return [
            'plazo' => $plazo,
            'meta' => $globalMeta,
            'remisionados' => $globalRemisionados,
            'cumple' => $allComplied,
            'detalles' => $items
        ];
    }

    function getarticulosbyproyecto($proyectoid, $almacenid = null)
    {
        $db = new FirebirdConnection(true);
        
        $resProj = $db->query("SELECT PROYECTO_ALMACEN_CONSIGNA_ID, PROYECTO_SUCURSALID FROM AMPAR_HIS_PROYECTOS WHERE PROYECTO_ID = ?", [$proyectoid]);
        $consignaId = $resProj[0]['PROYECTO_ALMACEN_CONSIGNA_ID'] ?? 0;
        
        if (!$almacenid) {
            $almacenid = $consignaId ?: ($resProj[0]['PROYECTO_SUCURSALID'] ?? 0);
        }

        $sql = "
            SELECT 
                COALESCE(st.STOCK_ID, -ec.EQUIPOCAPITAL_ID) AS ID, 
                COALESCE(st.STOCK_FOLIO, ec.FOLIO) AS FOLIO, 
                a.ARTICULO_ID, 
                a.NOMBRE AS ARTICULO_NOMBRE, 
                X.CLAVE_ARTICULO, 
                CAST(em.PROYECTOMALETA_MALETAID AS INTEGER) AS MALETA_ID,
                CAST(CASE WHEN em.PROYECTOMALETA_MALETAID > 0 THEN 'Artículo Individual' ELSE 'Equipo Capital' END AS VARCHAR(150)) AS MALETA_NOMBRE,
                CAST(COALESCE(st.STOCK_FOLIO, ec.FOLIO) AS VARCHAR(50)) AS MALETA_FOLIO,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = p.PROYECTO_CLIENTEID),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = p.PROYECTO_CLIENTEID),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = p.PROYECTO_CLIENTEID),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS TOTAL
            FROM AMPAR_HIS_PROYECTOS p
            INNER JOIN AMPAR_HIS_PROYECTOSMALETAS em ON em.PROYECTOMALETA_PROYECTOID = p.PROYECTO_ID
            LEFT JOIN AMPAR_HIS_STOCK st ON em.PROYECTOMALETA_MALETAID > 0 AND st.STOCK_ARTICULOID = em.PROYECTOMALETA_MALETAID AND (st.STOCK_ALMACENIDACTUAL = " . (int)$almacenid . " OR st.STOCK_ALMACENIDACTUAL = " . (int)$consignaId . ") AND st.STOCK_STOCKSTATUSID = 1
            LEFT JOIN AMPAR_EQUIPOCAPITAL ec ON em.PROYECTOMALETA_MALETAID < 0 AND ec.EQUIPOCAPITAL_ID = -em.PROYECTOMALETA_MALETAID
            INNER JOIN ARTICULOS a ON a.ARTICULO_ID = COALESCE(st.STOCK_ARTICULOID, ec.ARTICULO_ID)
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = a.ARTICULO_ID
            WHERE p.PROYECTO_ID = " . (int)$proyectoid . "
              AND (st.STOCK_ID IS NOT NULL OR ec.EQUIPOCAPITAL_ID IS NOT NULL)
        ";
        $result = $db->query($sql);
        $db->close();
        
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }
}
?>
