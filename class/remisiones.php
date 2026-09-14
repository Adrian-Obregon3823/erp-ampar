<?php
class remisiones
{

    function getremisiones()
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT RE.*, S.*, SU.*, A.ALMACEN_NOMBRE, 
            E.EVENTO_FOLIO, CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
            P.PROYECTO_FOLIO, CAST(P.PROYECTO_CONCEPTO AS VARCHAR(1000)) AS PROYECTO_CONCEPTO,
            (
                COALESCE((
                    SELECT SUM(REMISIONARTICULO_TOTAL) 
                    FROM AMPAR_HIS_REMISIONESARTICULOS 
                    WHERE REMISIONARTICULO_REMISIONID = RE.REMISION_ID
                ), 0) 
                +
                COALESCE((
                    SELECT SUM(REMISIONPROVARTICULO_TOTAL) 
                    FROM AMPAR_HIS_REMISIONESPARTICULOS 
                    WHERE REMISIONPROVARTICULO_REMISIONID = RE.REMISION_ID
                ), 0)
            ) AS TOTAL
            FROM AMPAR_HIS_REMISIONES RE
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = REMISION_STATUS
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = REMISION_SUCURSALID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = RE.REMISION_ALMACENID
            LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = RE.REMISION_EVENTOID
            LEFT JOIN AMPAR_HIS_PROYECTOS P ON P.PROYECTO_ID = RE.REMISION_PROYECTOID
            WHERE RE.REMISION_EVENTOID IS NOT NULL OR RE.REMISION_PROYECTOID IS NOT NULL
            ORDER BY COALESCE(RE.REMISION_EVENTOID, RE.REMISION_PROYECTOID) DESC, RE.REMISION_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getremisionbyid($db, $remisionid)
    {
        $ownConn = ($db === null);
        if ($ownConn) $db = $this->db();
        $sql = "
            SELECT RE.*, REA.*, ST.*, ED.*, AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO, SU.NOMBRE SUCURSAL_NOMBRE, S.*, 
            RE.REMISION_FOLIO AS REMISION_FOLIO,
            E.EVENTO_FOLIO, E.EVENTO_CLIENTEID, P.PROYECTO_FOLIO, P.PROYECTO_CLIENTEID, COALESCE(CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)), CAST(P.PROYECTO_CONCEPTO AS VARCHAR(1000))) AS EVENTO_CONCEPTO, EVENTO_FECHAI, EVENTO_FECHAF,
            UE.USUARIO_NOMBRE ESPECIALISTA_NOMBRE, UC.USUARIO_NOMBRE CHOFER_NOMBRE,
            UV.USUARIO_NOMBRE VENDEDOR_NOMBRE,
            MST.ALMACEN_NOMBRE AS MALETA_NOMBRE, MST.ALMACEN_FOLIO AS MALETA_FOLIO,
            H.*, COALESCE(C.NOMBRE, E.EVENTO_NOMBREPARTICULAR, CAST(COALESCE(E.EVENTO_CLIENTEID, P.PROYECTO_CLIENTEID) AS VARCHAR(255))) AS CLIENTE_NOMBRE, E.EVENTO_NOMBREPARTICULAR, ALM.ALMACEN_NOMBRE AS REMISION_ALMACEN_NOMBRE,
            (SELECT FIRST 1 TRIM(COALESCE(D.CALLE, '') || ' ' || COALESCE(D.NUM_EXTERIOR, '')) || '|||' || COALESCE(D.COLONIA, '') || '|||' || COALESCE(D.POBLACION, '') || '|||' || COALESCE(D.CODIGO_POSTAL, '') || '|||' || COALESCE(CIU.NOMBRE, '') || '|||' || COALESCE(EST.NOMBRE, '') FROM DIRS_CLIENTES D LEFT JOIN CIUDADES CIU ON CIU.CIUDAD_ID = D.CIUDAD_ID LEFT JOIN ESTADOS EST ON EST.ESTADO_ID = D.ESTADO_ID WHERE D.CLIENTE_ID = COALESCE(RE.REMISION_CLIENTEID, E.EVENTO_CLIENTEID, P.PROYECTO_CLIENTEID) AND D.ES_DIR_PPAL = 'S') AS CLIENTE_DIRECCION_COMPLETA,
            (SELECT FIRST 1 D.RFC_CURP FROM DIRS_CLIENTES D WHERE D.CLIENTE_ID = COALESCE(RE.REMISION_CLIENTEID, E.EVENTO_CLIENTEID, P.PROYECTO_CLIENTEID) AND D.ES_DIR_PPAL = 'S') AS CLIENTE_RFC_CURP,
            C.CONTACTO1 AS CLIENTE_CONTACTO,
            (SELECT FIRST 1 CP.NOMBRE FROM CONDICIONES_PAGO CP WHERE CP.COND_PAGO_ID = C.COND_PAGO_ID) AS CLIENTE_CONDICION_PAGO
            FROM 
            AMPAR_HIS_REMISIONES RE
            LEFT JOIN AMPAR_HIS_EVENTOS E ON EVENTO_ID = REMISION_EVENTOID
            LEFT JOIN AMPAR_HIS_PROYECTOS P ON P.PROYECTO_ID = REMISION_PROYECTOID
            LEFT JOIN AMPAR_CAT_HOSPITALES H ON HOSPITAL_ID = EVENTO_HOSPITALID
            LEFT JOIN CLIENTES C ON C.CLIENTE_ID = COALESCE(RE.REMISION_CLIENTEID, E.EVENTO_CLIENTEID, P.PROYECTO_CLIENTEID)
            LEFT JOIN AMPAR_CAT_USUARIOS UE ON UE.USUARIO_ID = EVENTO_ESPECIALISTAID
            LEFT JOIN AMPAR_CAT_USUARIOS UC ON UC.USUARIO_ID = EVENTO_CHOFERID
            LEFT JOIN AMPAR_CAT_USUARIOS UV ON UV.USUARIO_ID = RE.REMISION_USUARIOID
            LEFT JOIN AMPAR_HIS_REMISIONESARTICULOS REA ON REMISIONARTICULO_REMISIONID = REMISION_ID
            LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = REMISIONARTICULO_STOCKID
            LEFT JOIN AMPAR_HIS_ESDET ED ON ESDET_ID = STOCK_ESDETID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = COALESCE(ST.STOCK_ARTICULOID, ESDET_ARTICULOID) 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = REMISION_SUCURSALID
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = REMISION_STATUS
            LEFT JOIN AMPAR_HIS_ALMACEN ALM ON ALM.ALMACEN_ID = RE.REMISION_ALMACENID
            LEFT JOIN AMPAR_HIS_ALMACEN MST ON MST.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            WHERE REMISION_ID = " . $remisionid . "
        ";
        $result = $db->query($sql);
        if ($ownConn) $db->close();
        return $result;
    }

    function getremisionprovinfobyid($remisionid)
    {
        $db = new FirebirdConnection();
        $remisionid = intval($remisionid);

        $sql = "
            SELECT 
                P.NOMBRE AS NOMBREPROVEEDOR,
                RPA.*,
                AR.NOMBRE AS NOMBRE_ARTICULO,
                X.CLAVE_ARTICULO,
                /* Precios por cliente con fallback a cliente NULL */
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_TOTAL,
                /* Compatibilidad: PRECIO = SUBTOTAL */
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO
            FROM AMPAR_HIS_REMISIONESPARTICULOS RPA
            LEFT JOIN PROVEEDORES P
                ON P.PROVEEDOR_ID = RPA.REMISIONPROVARTICULO_PROVID
            LEFT JOIN ARTICULOS AR
                ON AR.ARTICULO_ID = RPA.REMISIONPROVARTICULO_ARTICULOID
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID
                FROM CLAVES_ARTICULOS
                WHERE ROL_CLAVE_ART_ID = 17
            ) X
                ON X.ARTICULO_ID = AR.ARTICULO_ID
            JOIN AMPAR_HIS_REMISIONES R
                ON R.REMISION_ID = RPA.REMISIONPROVARTICULO_REMISIONID
            JOIN AMPAR_HIS_EVENTOS E
                ON E.EVENTO_ID = R.REMISION_EVENTOID
            WHERE RPA.REMISIONPROVARTICULO_REMISIONID = {$remisionid}
        ";

        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function nuevaremision($sucursal, $eventoid, $articulos = [], $subtotales = [], $ivas = [], $totales = [], $proveedorid = [], $idarticulosfiltro = [], $cantidades = [], $subtotalarticulosfiltro = [], $ivaarticulosfiltro = [], $totalarticulosfiltro = [], $subtotalproveedor = [], $ivaproveedor = [], $totalproveedor = [], $remalmacen = null, $proyectoid = null)
    {
        $db = new FirebirdConnection();
        $this->syncTableGenerator($db, 'AMPAR_HIS_REMISIONES', 'REMISION_ID');

        $colEvento = ($eventoid !== null && $eventoid !== "" && $eventoid !== "NULL") ? $eventoid : "NULL";
        $colProyecto = ($proyectoid !== null && $proyectoid !== "" && $proyectoid !== "NULL") ? $proyectoid : "NULL";

        $sql = "
            Insert into AMPAR_HIS_REMISIONES
            (         
                REMISION_FECHA,
                REMISION_FOLIO,
                REMISION_STATUS,
                REMISION_SUCURSALID,
                REMISION_ALMACENID,
                REMISION_EVENTOID,
                REMISION_PROYECTOID
            )
            VALUES
            (
                CURRENT_TIMESTAMP,
                (
                    SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(REMISION_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                    FROM AMPAR_HIS_REMISIONES
                ),
                1,
                " . $sucursal . ",
                " . (($remalmacen == "" || $remalmacen == null) ? "NULL" : $remalmacen) . ",
                " . $colEvento . ",
                " . $colProyecto . "
            )
        ";
        $id_insertado = $db->executeconreturning($sql, 'REMISION_ID');
        if ($id_insertado) {
            if (!empty($articulos) and !empty($totales)) {
                $this->nuevaremisiondet($db, $id_insertado, $articulos, $subtotales, $ivas, $totales);
            }

            //CAMBIAR STATUS DE EVENTO A EN REMISION (Solo si es evento)
            if ($colEvento !== "NULL") {
                $sql2 = "update AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 16 WHERE EVENTO_ID = " . $colEvento;
                $db->execute($sql2);
            }

            //Si se seleccionaron articulos de proveedores guardar en la base de datos
            if (!empty($proveedorid) and !empty($idarticulosfiltro) and !empty($cantidades)) {
                $this->nuevaremisionprovdet($db, $id_insertado, $proveedorid, $idarticulosfiltro, $cantidades, $subtotalarticulosfiltro, $ivaarticulosfiltro, $totalarticulosfiltro, $subtotalproveedor, $ivaproveedor, $totalproveedor);
            }
        } else {
            echo "Error al insertar registro.";
        }
        $db->close();
        return $id_insertado;
    }

    private function syncTableGenerator($db, $tableName, $idCol)
    {
        try {
            $resMax = $db->query("SELECT COALESCE(MAX({$idCol}), 0) AS MAX_ID FROM {$tableName}");
            $maxId = (int)($resMax[0]['MAX_ID'] ?? 0);
            if ($maxId > 0) {
                // 1) Si es columna Identity en Firebird 3+, RESTART WITH
                try {
                    $db->execute("ALTER TABLE {$tableName} ALTER COLUMN {$idCol} RESTART WITH " . ($maxId + 1));
                } catch (Exception $eIdentity) {
                    // Ignorar si la sintaxis ALTER TABLE ALTER COLUMN RESTART no aplica
                }

                // 2) Buscar el generador de columna Identity en RDB$RELATION_FIELDS
                $sqlIdentityGen = "
                    SELECT TRIM(RDB\$GENERATOR_NAME) AS GEN_NAME
                    FROM RDB\$RELATION_FIELDS
                    WHERE TRIM(RDB\$RELATION_NAME) = '{$tableName}'
                      AND TRIM(RDB\$FIELD_NAME) = '{$idCol}'
                      AND RDB\$GENERATOR_NAME IS NOT NULL
                ";
                $gensIdentity = $db->query($sqlIdentityGen);
                if ($gensIdentity && is_array($gensIdentity)) {
                    foreach ($gensIdentity as $g) {
                        if (!empty($g['GEN_NAME'])) {
                            $db->execute("SET GENERATOR " . $g['GEN_NAME'] . " TO " . $maxId);
                        }
                    }
                }

                // 3) Buscar generadores asociados a triggers Before Insert en RDB$DEPENDENCIES
                $sqlGen = "
                    SELECT TRIM(D.RDB\$DEPENDED_ON_NAME) AS GEN_NAME
                    FROM RDB\$DEPENDENCIES D
                    JOIN RDB\$TRIGGERS T ON D.RDB\$DEPENDENT_NAME = T.RDB\$TRIGGER_NAME
                    WHERE TRIM(T.RDB\$RELATION_NAME) = '{$tableName}'
                      AND D.RDB\$DEPENDED_ON_TYPE = 14
                ";
                $gens = $db->query($sqlGen);
                if ($gens && is_array($gens)) {
                    foreach ($gens as $g) {
                        if (!empty($g['GEN_NAME'])) {
                            $db->execute("SET GENERATOR " . $g['GEN_NAME'] . " TO " . $maxId);
                        }
                    }
                }

                // 4) Búsqueda directa por nombre del generador en RDB$GENERATORS
                $cleanName = str_replace('AMPAR_HIS_', '', $tableName);
                $sqlLike = "
                    SELECT TRIM(RDB\$GENERATOR_NAME) AS GEN_NAME
                    FROM RDB\$GENERATORS
                    WHERE RDB\$GENERATOR_NAME LIKE '%{$cleanName}%'
                ";
                $gensLike = $db->query($sqlLike);
                if ($gensLike && is_array($gensLike)) {
                    foreach ($gensLike as $g) {
                        if (!empty($g['GEN_NAME'])) {
                            $db->execute("SET GENERATOR " . $g['GEN_NAME'] . " TO " . $maxId);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorar errores en resincronización de generador
        }
    }

    function nuevaremisiondet($db, $remisionid, $articulos, $subtotales, $ivas, $totales)
    {
        $this->syncTableGenerator($db, 'AMPAR_HIS_REMISIONESARTICULOS', 'REMISIONARTICULO_ID');
        for ($i = 0; $i < count($articulos); $i++) {
            $stockid = (int)$articulos[$i];

            $rawId = (string)$articulos[$i];
            $stockid = (int)$rawId;

            // Manejo de Equipo Capital (empieza con ec_ o es negativo)
            if (str_starts_with($rawId, 'ec_') || $stockid < 0) {
                $eqId = abs((int)str_replace('ec_', '', $rawId));
                $resEq = $db->query("SELECT ARTICULO_ID, FOLIO FROM AMPAR_EQUIPOCAPITAL WHERE EQUIPOCAPITAL_ID = {$eqId}");
                if ($resEq && !empty($resEq)) {
                    $artIdEq = (int)$resEq[0]['ARTICULO_ID'];
                    $folioEq = $resEq[0]['FOLIO'];

                    $remInfo = $db->query("SELECT REMISION_ALMACENID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
                    $almIdEq = ($remInfo && !empty($remInfo)) ? (int)$remInfo[0]['REMISION_ALMACENID'] : 1;

                    $newStockId = $this->createStockForArticle($db, $artIdEq, $almIdEq, $folioEq);
                    if ($newStockId) {
                        $stockid = $newStockId;
                    }
                }
            } else {
                // Verificar si el stockid existe en AMPAR_HIS_STOCK
                $checkStock = $db->query("SELECT 1 FROM AMPAR_HIS_STOCK WHERE STOCK_ID = {$stockid}");
                if (!$checkStock || $checkStock == 0) {
                    // No existe en stock. ¿Es un artículo del catálogo?
                    $checkArt = $db->query("SELECT ARTICULO_ID FROM ARTICULOS WHERE ARTICULO_ID = {$stockid}");
                    if ($checkArt && $checkArt != 0) {
                        // Obtener almacén de la remisión
                        $remInfo = $db->query("SELECT REMISION_ALMACENID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
                        $almacenId = ($remInfo && !empty($remInfo)) ? (int)$remInfo[0]['REMISION_ALMACENID'] : null;
                        if ($almacenId) {
                            $newStockId = $this->createStockForArticle($db, $stockid, $almacenId);
                            if ($newStockId) {
                                $stockid = $newStockId;
                            }
                        }
                    }
                }
            }

            $sql = "
                Insert into AMPAR_HIS_REMISIONESARTICULOS
                (         
                    REMISIONARTICULO_REMISIONID,
                    REMISIONARTICULO_STOCKID,
                    REMISIONARTICULO_SUBTOTAL,
                    REMISIONARTICULO_IVA,
                    REMISIONARTICULO_TOTAL
                )
                VALUES
                (
                    " . $remisionid . ",
                    " . $stockid . ",
                    " . $subtotales[$i] . ",
                    " . $ivas[$i] . ",
                    " . $totales[$i] . "
                )
            ";
            $db->execute($sql);
        }
    }

    function nuevaremisionprovdet($db, $remisionid, $idproveedores, $articulos, $cantidades, $cusubtotales, $cuivas, $cutotales, $subtotales, $ivas, $totales)
    {
        $this->syncTableGenerator($db, 'AMPAR_HIS_REMISIONESPARTICULOS', 'REMISIONPROVARTICULO_ID');
        for ($i = 0; $i < count($idproveedores); $i++) {
            $sql = "
                Insert into AMPAR_HIS_REMISIONESPARTICULOS
                (         
                    REMISIONPROVARTICULO_REMISIONID,
                    REMISIONPROVARTICULO_PROVID,
                    REMISIONPROVARTICULO_ARTICULOID,
                    REMISIONPROVARTICULO_CANTIDAD,
                    REMISIONPROVARTICULO_CUSUBTOTAL,
                    REMISIONPROVARTICULO_CUIVA,
                    REMISIONPROVARTICULO_CUTOTAL,
                    REMISIONPROVARTICULO_SUBTOTAL,
                    REMISIONPROVARTICULO_IVA,
                    REMISIONPROVARTICULO_TOTAL
                )
                VALUES
                (
                    " . $remisionid . ",
                    " . $idproveedores[$i] . ",
                    " . $articulos[$i] . ",
                    " . $cantidades[$i] . ",
                    " . $cusubtotales[$i] . ",
                    " . $cuivas[$i] . ",
                    " . $cutotales[$i] . ",
                    " . $subtotales[$i] . ",
                    " . $ivas[$i] . ",
                    " . $totales[$i] . "
                )
            ";
            $db->execute($sql);
        }
    }

    function updatestatusremisiones($id, $status)
    {

        $db = new FirebirdConnection();

        // 1) Actualiza status remisión
        $sql = "UPDATE AMPAR_HIS_REMISIONES SET REMISION_STATUS = {$status} WHERE REMISION_ID = {$id}";
        $db->execute($sql);

        // Si se cancela (5), devolver stock a disponible (1)
        if ($status == 5) {
            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 
                         WHERE STOCK_ID IN (SELECT REMISIONARTICULO_STOCKID FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_REMISIONID = {$id})");
        }

        if ($status == 3) {
            // === Transacción ===
            try {
                if (method_exists($db, 'beginTransaction')) $db->beginTransaction();

                // 2) Traer todo el detalle necesario
                $sql = "
                    SELECT RE.*, REA.*, ST.*,
                           AR.NOMBRE AS ARTICULO_NOMBRE, X.CLAVE_ARTICULO,
                           SU.NOMBRE AS SUCURSAL_NOMBRE, S.*,
                           E.EVENTO_ID, E.EVENTO_FOLIO,
                           CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
                           E.EVENTO_FECHAI, E.EVENTO_FECHAF,
                           UE.USUARIO_NOMBRE AS ESPECIALISTA_NOMBRE,
                           UC.USUARIO_NOMBRE AS CHOFER_NOMBRE,
                           H.*, COALESCE(C.NOMBRE, E.EVENTO_NOMBREPARTICULAR, CAST(E.EVENTO_CLIENTEID AS VARCHAR(255))) AS CLIENTE_NOMBRE, E.EVENTO_NOMBREPARTICULAR
                    FROM AMPAR_HIS_REMISIONES RE
                    LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = RE.REMISION_EVENTOID
                    LEFT JOIN AMPAR_CAT_HOSPITALES H ON H.HOSPITAL_ID = E.EVENTO_HOSPITALID
                    LEFT JOIN CLIENTES C ON C.CLIENTE_ID = E.EVENTO_CLIENTEID
                    LEFT JOIN AMPAR_CAT_USUARIOS UE ON UE.USUARIO_ID = E.EVENTO_ESPECIALISTAID
                    LEFT JOIN AMPAR_CAT_USUARIOS UC ON UC.USUARIO_ID = E.EVENTO_CHOFERID
                    LEFT JOIN AMPAR_HIS_REMISIONESARTICULOS REA ON REA.REMISIONARTICULO_REMISIONID = RE.REMISION_ID
                    LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = REA.REMISIONARTICULO_STOCKID
                    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                    LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                    LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = RE.REMISION_SUCURSALID
                    LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = RE.REMISION_STATUS
                    WHERE RE.REMISION_ID = {$id}
                ";
                $detalle = $db->query($sql);

                // 3) Baja de stock + Bitácora
                $usersesion = $_SESSION['ampar']['usuario'] ?? 'sistema';
                $userId     = $usersesion['USUARIO_ID'] ?? null;
                $userCorreo = $usersesion['USUARIO_CORREO'] ?? null;
                $ipuser     = method_exists('bitacora', 'getip') ? bitacora::getip() : $_SERVER['REMOTE_ADDR'] ?? null;

                foreach ($detalle as $det) {
                    // Baja de stock
                    $sql1 = "UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 3 WHERE STOCK_ID = " . $det["STOCK_ID"];
                    $db->execute($sql1);

                    $txtEvento = !empty($det['EVENTO_ID']) ? " (Evento #{$det['EVENTO_ID']}, Folio: " . $det['EVENTO_FOLIO'] . ")" : " (Remisión Mostrador)";
                    $comentario = "Baja de artículo <b>{$det['STOCK_FOLIO']}</b> - {$det['ARTICULO_NOMBRE']} ({$det['CLAVE_ARTICULO']}) por remisión #{$det['REMISION_FOLIO']}{$txtEvento}";
                    if (method_exists($db, 'executeParams')) {
                        $sqlBit = "INSERT INTO AMPAR_BITACORA
                               (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
                                BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
                               VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $db->executeParams($sqlBit, [
                            $comentario,
                            $userId,
                            $usersesion,
                            $ipuser,
                            $det['REMISION_SUCURSALID'] ?? null,
                            $det['STOCK_ALMACENIDACTUAL']   ?? null,
                            $det['STOCK_ID']          ?? null,
                            $det['EVENTO_ID']         ?? null,
                        ]);
                    } else {
                        $sucursalId = $det['REMISION_SUCURSALID'] ?? 'NULL';
                        $almacenId  = $det['STOCK_ALMACENIDACTUAL'] ?? 'NULL';
                        $stockId    = $det['STOCK_ID'] ?? 'NULL';
                        $eventoId   = $det['EVENTO_ID'] ?? 'NULL';
                        $comentarioSQL = str_replace("'", "''", $comentario);
                        $usuarioSQL    = $userCorreo;
                        $ipSQL         = $ipuser ? ("'" . str_replace("'", "''", $ipuser) . "'") : "NULL";
                        $userIdSQL     = $userId !== null ? (int)$userId : "NULL";

                        $sqlBit2 = "
                            INSERT INTO AMPAR_BITACORA
                            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
                             BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
                            VALUES (CURRENT_TIMESTAMP, '{$comentarioSQL}', {$userIdSQL}, '{$usuarioSQL}', {$ipSQL},
                                    {$sucursalId}, {$almacenId}, {$stockId}, {$eventoId})
                        ";
                        $db->execute($sqlBit2);
                    }
                }

                // 4) Evento -> finalizado
                // A petición del usuario, finalizar una remisión YA NO finaliza el evento automáticamente
                // para permitir crear múltiples remisiones para el mismo evento.
                /*
                $sql2 = "UPDATE AMPAR_HIS_EVENTOS
                         SET EVENTO_STATUSGENERAL = 3
                         WHERE EVENTO_ID = (SELECT REMISION_EVENTOID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$id})";
                $db->execute($sql2);
                */

                // 5) Crear OC por proveedor
                $sqlProv = "
                    SELECT
                        P.PROVEEDOR_ID,
                        P.NOMBRE AS NOMBREPROVEEDOR,
                        RPA.REMISIONPROVARTICULO_ARTICULOID,
                        RPA.REMISIONPROVARTICULO_CANTIDAD,
                        RPA.REMISIONPROVARTICULO_SUBTOTAL,
                        RPA.REMISIONPROVARTICULO_IVA,
                        RPA.REMISIONPROVARTICULO_TOTAL,
                        AR.NOMBRE AS NOMBRE_ARTICULO,
                        X.CLAVE_ARTICULO,
                        E.EVENTO_ID
                    FROM AMPAR_HIS_REMISIONESPARTICULOS RPA
                    LEFT JOIN PROVEEDORES P
                        ON P.PROVEEDOR_ID = RPA.REMISIONPROVARTICULO_PROVID
                    LEFT JOIN ARTICULOS AR
                        ON AR.ARTICULO_ID = RPA.REMISIONPROVARTICULO_ARTICULOID
                    LEFT JOIN (
                        SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID
                        FROM CLAVES_ARTICULOS
                        WHERE ROL_CLAVE_ART_ID = 17
                    ) X
                        ON X.ARTICULO_ID = AR.ARTICULO_ID
                    JOIN AMPAR_HIS_REMISIONES R
                        ON R.REMISION_ID = RPA.REMISIONPROVARTICULO_REMISIONID
                    JOIN AMPAR_HIS_EVENTOS E
                        ON E.EVENTO_ID = R.REMISION_EVENTOID
                    WHERE RPA.REMISIONPROVARTICULO_REMISIONID = {$id}
                      AND P.PROVEEDOR_ID IS NOT NULL
                ";
                $infoprov = $db->query($sqlProv);

                if ($infoprov && is_array($infoprov) && count($infoprov) > 0) {
                    $porProv = [];
                    $eventoIdDeOC = null;

                    foreach ($infoprov as $re) {
                        $provId = (int)$re['PROVEEDOR_ID'];
                        if (!isset($porProv[$provId])) {
                            $porProv[$provId] = [
                                'PROVEEDOR_ID' => $provId,
                                'rows' => []
                            ];
                        }
                        $porProv[$provId]['rows'][] = $re;
                        if ($eventoIdDeOC === null) $eventoIdDeOC = (int)$re['EVENTO_ID'];
                    }

                    foreach ($porProv as $provId => $bucket) {
                        $sqlFolio = "
                            SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(OC_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0')
                                   || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2)) AS NUEVOFOLIO
                            FROM AMPAR_OC
                        ";
                        $rFolio = $db->query($sqlFolio);
                        $nuevoFolio = $rFolio && isset($rFolio[0]['NUEVOFOLIO']) ? $rFolio[0]['NUEVOFOLIO'] : '00001-' . date('y');

                        $usuarioIdOC = $GLOBALS['usersesion']['USUARIO_ID'] ?? ($_SESSION['USUARIO_ID'] ?? null);
                        $sqlOC = "
                            INSERT INTO AMPAR_OC (OC_FECHA, OC_FOLIO, OC_EVENTOID, OC_PROVEEDORID, OC_USUARIOID)
                            VALUES (CURRENT_TIMESTAMP, '{$nuevoFolio}', {$eventoIdDeOC}, {$provId}, " . ($usuarioIdOC ? (int)$usuarioIdOC : "NULL") . ")
                        ";
                        $ocId = $db->executeconreturning($sqlOC, 'OC_ID');

                        foreach ($bucket['rows'] as $row) {
                            $artId   = (int)$row['REMISIONPROVARTICULO_ARTICULOID'];
                            $cant    = (float)$row['REMISIONPROVARTICULO_CANTIDAD'];
                            $subtotal  = (float)$row['REMISIONPROVARTICULO_SUBTOTAL'];
                            $iva  = (float)$row['REMISIONPROVARTICULO_IVA'];
                            $total  = (float)$row['REMISIONPROVARTICULO_TOTAL'];

                            $sqlDet = "
                                INSERT INTO AMPAR_OCDET (OCDET_OCID, OCDET_ARTICULOID, OCDET_CANTIDAD, OCDET_PRECIO)
                                VALUES ({$ocId}, {$artId}, {$cant}, {$total})
                            ";
                            $db->execute($sqlDet);
                        }

                        $comentarioOC = "Se creó OC {$nuevoFolio} para proveedor #{$provId} por remisión #{$id} (evento #{$eventoIdDeOC})";
                        $comentarioOCSQL = str_replace("'", "''", $comentarioOC);
                        $userIdSQL = isset($userId) && $userId !== null ? (int)$userId : "NULL";
                        $usuarioSQL = $userCorreo;
                        $ipSQL = isset($ipuser) ? ("'" . str_replace("'", "''", $ipuser) . "'") : "NULL";

                        $sqlBitOC = "
                            INSERT INTO AMPAR_BITACORA
                            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_EVENTOID)
                            VALUES (CURRENT_TIMESTAMP, '{$comentarioOCSQL}', {$userIdSQL}, '{$usuarioSQL}', {$ipSQL}, {$eventoIdDeOC})
                        ";
                        $db->execute($sqlBitOC);
                    }
                }

                if (method_exists($db, 'commit')) $db->commit();
            } catch (Throwable $e) {
                if (method_exists($db, 'rollBack')) $db->rollBack();
                throw $e;
            }
        }

        // Cancelación -> regresa evento a iniciado (15)
        if ($status == 5) {
            $sql2 = "UPDATE AMPAR_HIS_EVENTOS
                     SET EVENTO_STATUSGENERAL = 15
                     WHERE EVENTO_ID = (SELECT REMISION_EVENTOID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$id})";
            $db->execute($sql2);
        }

        $db->close();
    }

    function eliminardetalle($tipo, $id)
    {
        $db = new FirebirdConnection();
        if ($tipo == 'AMPAR') {
            $sql = "
                DELETE FROM AMPAR_HIS_REMISIONESARTICULOS
                WHERE
                REMISIONARTICULO_ID = " . $id . "
            ";
        }
        if ($tipo == 'PROVEEDOR') {
            $sql = "
                DELETE FROM AMPAR_HIS_REMISIONESPARTICULOS
                WHERE
                REMISIONPROVARTICULO_ID = " . $id . "
            ";
        }
        $db->execute($sql);
        $db->close();
    }

    public function agregarArticuloProveedor($db, $remisionid, $proveedorid, $articuloid, $cantidad)
    {
        $own = ($db === null);
        if ($own) $db = $this->db();

        $remisionid = (int)$remisionid;
        $proveedorid = (int)$proveedorid;
        $articuloid = (int)$articuloid;
        $cantidad = (int)$cantidad;
        if ($cantidad <= 0) throw new Exception("Cantidad inválida");

        // Cliente del evento
        $clienteId = $this->getClienteIdByRemision($db, $remisionid);

        // Precio unitario por cliente (subtotal/iva/total)
        $p = $db->query("
            SELECT FIRST 1 ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL
            FROM AMPAR_CAT_ARTPRECIO
            WHERE ARTPRECIO_ARTICULOID = {$articuloid}
            AND ARTPRECIO_CLIENTEID  = {$clienteId}
            UNION ALL
            SELECT FIRST 1 ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL
            FROM AMPAR_CAT_ARTPRECIO
            WHERE ARTPRECIO_ARTICULOID = {$articuloid}
            AND ARTPRECIO_CLIENTEID IS NULL
        ");
        if (!$p || $p == 0) throw new Exception("Sin tarifa configurada para el artículo.");
        $pu = $p[0];

        // Montos totales por cantidad
        $cusub = (float)$pu['ARTPRECIO_SUBTOTAL'];
        $cuiva = (float)$pu['ARTPRECIO_IVA'];
        $cutot = (float)$pu['ARTPRECIO_TOTAL'];

        $sub = $cusub * $cantidad;
        $iva = $cuiva * $cantidad;
        $tot = $cutot * $cantidad;

        $this->syncTableGenerator($db, 'AMPAR_HIS_REMISIONESPARTICULOS', 'REMISIONPROVARTICULO_ID');
        // Insert
        $db->execute("
            INSERT INTO AMPAR_HIS_REMISIONESPARTICULOS
            (REMISIONPROVARTICULO_REMISIONID, REMISIONPROVARTICULO_PROVID, REMISIONPROVARTICULO_ARTICULOID,
            REMISIONPROVARTICULO_CANTIDAD, REMISIONPROVARTICULO_CUSUBTOTAL, REMISIONPROVARTICULO_CUIVA, REMISIONPROVARTICULO_CUTOTAL,
            REMISIONPROVARTICULO_SUBTOTAL, REMISIONPROVARTICULO_IVA, REMISIONPROVARTICULO_TOTAL)
            VALUES
            ({$remisionid}, {$proveedorid}, {$articuloid},
            {$cantidad}, {$cusub}, {$cuiva}, {$cutot},
            {$sub}, {$iva}, {$tot})
        ");

        // Bitácora
        $ev = $db->query("
        SELECT R.REMISION_EVENTOID EVID, E.EVENTO_FOLIO, E.EVENTO_SUCURSALID
        FROM AMPAR_HIS_REMISIONES R
        JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = R.REMISION_EVENTOID
        WHERE R.REMISION_ID = {$remisionid}
        ");
        if ($ev && $ev != 0) {
            $usersesion = $_SESSION['ampar']['usuario'] ?? [];
            $ipuser = bitacora::getip();
            $prov = $db->query("SELECT NOMBRE FROM PROVEEDORES WHERE PROVEEDOR_ID = {$proveedorid}");
            $art  = $db->query("SELECT NOMBRE FROM ARTICULOS   WHERE ARTICULO_ID = {$articuloid}");
            $txt = 'SE AGREGÓ PROVEEDOR/ARTÍCULO <b>' . htmlentities(($prov[0]['NOMBRE'] ?? '') . ' - ' . ($art[0]['NOMBRE'] ?? '')) . '</b> CANT: ' . $cantidad . ' A REMISIÓN (Evento ' . $ev[0]['EVENTO_FOLIO'] . ')';
            $db->execute("
            INSERT INTO AMPAR_BITACORA
            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $txt,
                $usersesion['USUARIO_ID'] ?? null,
                $usersesion['USUARIO_CORREO'] ?? null,
                $ipuser,
                $ev[0]['EVENTO_SUCURSALID'] ?? null,
                null,
                null,
                $ev[0]['EVID'] ?? null
            ]);
        }

        if ($own) $db->close();
        return true;
    }

    function consultasiexisteenremision($stockid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT *
            FROM AMPAR_HIS_REMISIONESARTICULOS REA
            LEFT JOIN AMPAR_HIS_REMISIONES RE ON REMISION_ID = REMISIONARTICULO_REMISIONID
            WHERE 
            REMISIONARTICULO_STOCKID = " . $stockid . "
            AND (REMISION_STATUS = 1 OR REMISION_STATUS = 3)
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function iniciarevento($eventoid, $stockid)
    {
        $evento = new eventos();
        $db = new FirebirdConnection();

        $activa = $this->getRemisionActivaByEvento($db, (int)$eventoid);
        if ($activa) {
            $db->close();
            return [
                'status' => 'success',
                'creada' => false,
                'remisionid' => (int)$activa['REMISION_ID'],
                'remisionfolio' => $activa['REMISION_FOLIO'] ?? null,
                'eventoid' => (int)$eventoid,
            ];
        }

        //Update status evento
        $evento->updatestatusevento($eventoid, 15);
        //Consulta información evento
        $info = $evento->geteventobyid($eventoid);
        //Crear Remisión
        //Consulta precio
        $productos = $evento->getarticulosbyevento($eventoid);
        // Inicializar arrays
        $articulos = [];
        $subtotales = [];
        $ivas = [];
        $totales = [];
        // Buscar el producto con el ID igual a $stockid
        foreach ($productos as $producto) {
            if ($producto['ID'] == $stockid) {
                $articulos[] = $producto['ID'];
                $subtotales[] = $producto['SUBTOTAL'];
                $ivas[] = $producto['IVA'];
                $totales[] = $producto['TOTAL'];
                break; // ya lo encontramos, salimos del loop
            }
        }
        // Validar antes de llamar a la función
        if (!empty($articulos) && !empty($subtotales)) {
            // Mandamos variables vacías en los parámetros intermedios y el almacén al final
            $remisionid = $this->nuevaremision($info[0]['EVENTO_SUCURSALID'], $eventoid, $articulos, $subtotales, $ivas, $totales, [], [], [], [], [], [], [], [], [], $info[0]['EVENTO_ALMACENID']);
            $rem = $this->getremisionbyid($db, $remisionid);
            $folio = ($rem && $rem != 0) ? ($rem[0]['REMISION_FOLIO'] ?? null) : null;
            $db->close();
            return [
                'status' => 'success',
                'creada' => true,
                'remisionid' => (int)$remisionid,
                'remisionfolio' => $folio,
                'eventoid' => (int)$eventoid,
            ];
        }

        $db->close();
        return [
            'status' => 'error',
            'message' => "No se encontró el artículo con ID $stockid en el evento.",
            'eventoid' => (int)$eventoid,
        ];
    }

    private function db()
    {
        return new FirebirdConnection();
    }

    private function json($arr)
    {
        return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getRemisionActivaByEvento($db, $eventoid)
    {
        $sql = "
            SELECT FIRST 1 REMISION_ID, REMISION_FOLIO
            FROM AMPAR_HIS_REMISIONES
            WHERE REMISION_EVENTOID = {$eventoid}
              AND REMISION_STATUS = 1
            ORDER BY REMISION_ID DESC
        ";
        $r = $db->query($sql);
        return ($r && $r != 0) ? $r[0] : null;
    }

    private function articuloEstaEnRemisionActiva($db, $stockid)
    {
        $sql = "
            SELECT FIRST 1 RE.REMISION_ID, RE.REMISION_STATUS
            FROM AMPAR_HIS_REMISIONESARTICULOS RA
            JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
            WHERE RA.REMISIONARTICULO_STOCKID = {$stockid}
              AND RE.REMISION_STATUS IN (1,3) -- 1=guardada/activa, 3=finalizada
            ORDER BY RE.REMISION_ID DESC
        ";
        $r = $db->query($sql);
        return ($r && $r != 0) ? $r[0] : null;
    }

    // Inserta un renglón en detalle de remisión (1 artículo)
    private function insertarDetalleArticulo($db, $remisionid, $stockid, $precioTotal, $ivaFactor = 0.16)
    {
        $subtotal = round($precioTotal / (1 + $ivaFactor), 2);
        $iva = round($precioTotal - $subtotal, 2);
        $total = $precioTotal;

        $sql = "
            INSERT INTO AMPAR_HIS_REMISIONESARTICULOS
            (
                REMISIONARTICULO_REMISIONID,
                REMISIONARTICULO_STOCKID,
                REMISIONARTICULO_SUBTOTAL,
                REMISIONARTICULO_IVA,
                REMISIONARTICULO_TOTAL
            )
            VALUES
            (
                {$remisionid},
                {$stockid},
                {$subtotal},
                {$iva},
                {$total}
            )
        ";
        $db->execute($sql);
    }

    // Crear remisión vacía y actualizar status de evento/maletas
    function nuevaremisionsindet($db, $sucursal, $eventoid, $almacen = null)
    {
        $sql = "
            Insert into AMPAR_HIS_REMISIONES
            (REMISION_FECHA, REMISION_FOLIO, REMISION_STATUS, REMISION_SUCURSALID, REMISION_ALMACENID, REMISION_EVENTOID)
            VALUES
            (
                CURRENT_TIMESTAMP,
                (
                    SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(REMISION_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                    FROM AMPAR_HIS_REMISIONES
                ),
                1,
                {$sucursal},
                " . (($almacen == "" || $almacen == null) ? "NULL" : $almacen) . ",
                {$eventoid}
            )
        ";
        $id_insertado = $db->executeconreturning($sql, 'REMISION_ID');

        // Evento a EN REMISIÓN (16)
        $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 16 WHERE EVENTO_ID = {$eventoid}");
        return $id_insertado;
    }

    // NUEVO flujo de validación (devuelve STRING JSON)
    function validarqrcarrito($stockid)
    {
        $db = $this->db();

        // 1) Ubicar artículo, maleta, evento(s) vigentes (no 3 ni 5)
        $sql = "
            SELECT
                S.STOCK_ID,
                A.ALMACEN_ID,
                A.ALMACEN_NOMBRE,
                E.EVENTO_ID,
                E.EVENTO_FOLIO,
                E.EVENTO_CONCEPTO,
                E.EVENTO_STATUSGENERAL,
                E.EVENTO_SUCURSALID,
                E.EVENTO_ALMACENID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_SUBTOTAL,
                COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_IVA,
                COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  = E.EVENTO_HOSPITALID),
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS PRECIO_TOTAL
            FROM AMPAR_HIS_STOCK S
            JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
            JOIN AMPAR_HIS_EVENTOSMALETAS EM ON EM.EVENTOMALETA_MALETAID = A.ALMACEN_ID
            JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = EM.EVENTOMALETA_EVENTOID
            JOIN ARTICULOS AR ON AR.ARTICULO_ID = S.STOCK_ARTICULOID
            LEFT JOIN AMPAR_CAT_ARTPRECIO P ON P.ARTPRECIO_ARTICULOID = S.STOCK_ARTICULOID AND P.ARTPRECIO_CLIENTEID IS NULL
            WHERE S.STOCK_ID = {$stockid}
              AND S.STOCK_STOCKSTATUSID = 1         -- solo stock disponible
              AND E.EVENTO_STATUSGENERAL NOT IN (3,5) -- no finalizado ni cancelado
            ORDER BY E.EVENTO_FECHAI ASC
        ";
        $rows = $db->query($sql);

        if (!$rows || $rows == 0) {
            $db->close();
            return $this->json([
                'valido' => false,
                'mensaje' => 'NOEVENTO',
                'detalle' => 'No se encontró ningún evento vigente que contenga este artículo.'
            ]);
        }

        // 2) ¿hay evento EN REMISIÓN (16)?
        $remisionTarget = null;
        $rowTarget = null;
        foreach ($rows as $r) {
            if (intval($r['EVENTO_STATUSGENERAL']) === 16) {
                // ¿ya existe remisión activa para ese evento?
                $remAct = $this->getRemisionActivaByEvento($db, intval($r['EVENTO_ID']));
                if ($remAct) {
                    $remisionTarget = $remAct; // ['REMISION_ID','REMISION_FOLIO']
                    $rowTarget = $r;
                    break;
                } else {
                    // si el evento dice 16 pero no hay remisión activa, la creamos por consistencia
                    $newId = $this->nuevaremisionsindet($db, intval($r['EVENTO_SUCURSALID']), intval($r['EVENTO_ID']), $r['EVENTO_ALMACENID']);
                    $remisionTarget = ['REMISION_ID' => $newId, 'REMISION_FOLIO' => null];
                    $rowTarget = $r;
                    break;
                }
            }
        }

        // 3) Si no hay 16, ¿hay INICIADO (15)?
        if (!$remisionTarget) {
            foreach ($rows as $r) {
                if (intval($r['EVENTO_STATUSGENERAL']) === 15) {
                    // crear remisión y regresar listo para agregar
                    $newId = $this->nuevaremisionsindet($db, intval($r['EVENTO_SUCURSALID']), intval($r['EVENTO_ID']), $r['EVENTO_ALMACENID']);
                    // obtener folio
                    $inf = $this->getremisionbyid($db, $newId);
                    $folio = $inf && $inf != 0 ? $inf[0]['REMISION_FOLIO'] : null;

                    $db->close();
                    return $this->json([
                        'valido' => true,
                        'mensaje' => 'AGREGAR',
                        'stockid' => $stockid,
                        'evento_id' => intval($r['EVENTO_ID']),
                        'evento_nombre' => $r['EVENTO_CONCEPTO'],
                        'evento_status' => 16,
                        'maleta_nombre' => $r['ALMACEN_NOMBRE'],
                        'articulo_nombre' => $r['ARTICULO_NOMBRE'],
                        'precio_subtotal' => (float)$r['PRECIO_SUBTOTAL'],
                        'precio_iva'      => (float)$r['PRECIO_IVA'],
                        'precio_total'    => (float)$r['PRECIO_TOTAL'],
                        'remisionid' => $newId,
                        'remisionfolio' => $folio,
                        'sucursalid' => intval($r['EVENTO_SUCURSALID'])
                    ]);
                }
            }
        }

        // 4) Si tenemos evento en remisión y remisión activa -> validar si YA agregado
        if ($remisionTarget && $rowTarget) {
            $ya = $this->articuloEstaEnRemisionActiva($db, $stockid);
            if ($ya && intval($ya['REMISION_STATUS']) === 1) {
                $db->close();
                return $this->json([
                    'valido' => false,
                    'mensaje' => 'YAAGREGADO',
                    'detalle' => 'El artículo ya se encuentra en la remisión activa.',
                    'remisionid' => intval($remisionTarget['REMISION_ID'])
                ]);
            }
            // listo para AGREGAR
            $db->close();
            return $this->json([
                'valido' => true,
                'mensaje' => 'AGREGAR',
                'stockid' => $stockid,
                'evento_id' => intval($rowTarget['EVENTO_ID']),
                'evento_nombre' => $rowTarget['EVENTO_CONCEPTO'],
                'evento_status' => 16,
                'maleta_nombre' => $rowTarget['ALMACEN_NOMBRE'],
                'articulo_nombre' => $rowTarget['ARTICULO_NOMBRE'],
                'precio_subtotal' => floatval($rowTarget['PRECIO_SUBTOTAL']),
                'precio_iva' => floatval($rowTarget['PRECIO_IVA']),
                'precio_total' => floatval($rowTarget['PRECIO_TOTAL']),
                'remisionid' => intval($remisionTarget['REMISION_ID']),
                'remisionfolio' => $remisionTarget['REMISION_FOLIO'],
                'sucursalid' => intval($rowTarget['EVENTO_SUCURSALID'])
            ]);
        }

        // 5) No hay 15 ni 16
        $db->close();
        return $this->json([
            'valido' => false,
            'mensaje' => 'NOEVENTO',
            'detalle' => 'No se encontró ningún evento iniciado que contenga este artículo.'
        ]);
    }

    // Arreglo de tu método para insertar por QR
    function agregararticuloqr($db, $sucursalid, $remisionid, $stockid, $precio_ignorar)
    {
        $ownConn = ($db === null);
        if ($ownConn) $db = $this->db();

        $remisionid = (int)$remisionid;
        $stockid    = (int)$stockid;

        // Si el stockid no existe en AMPAR_HIS_STOCK, pero existe en ARTICULOS (Equipo Capital)
        // lo creamos en stock dinámicamente.
        $checkStock = $db->query("SELECT 1 FROM AMPAR_HIS_STOCK WHERE STOCK_ID = {$stockid}");
        if (!$checkStock || $checkStock == 0) {
            $checkArt = $db->query("SELECT ARTICULO_ID FROM ARTICULOS WHERE ARTICULO_ID = {$stockid}");
            if ($checkArt && $checkArt != 0) {
                // Obtener almacén de la remisión
                $remInfo = $db->query("SELECT REMISION_ALMACENID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
                $almacenId = ($remInfo && !empty($remInfo)) ? (int)$remInfo[0]['REMISION_ALMACENID'] : null;
                if ($almacenId) {
                    $newStockId = $this->createStockForArticle($db, $stockid, $almacenId);
                    if ($newStockId) {
                        $stockid = $newStockId;
                    } else {
                        if ($ownConn) $db->close();
                        echo "Error: no se pudo crear el registro de stock para el Equipo Capital.";
                        return;
                    }
                } else {
                    if ($ownConn) $db->close();
                    echo "Error: la remisión no tiene un almacén asignado.";
                    return;
                }
            }
        }

        // ¿Ya está en esa remisión?
        $ex = $db->query("
            SELECT 1
            FROM AMPAR_HIS_REMISIONESARTICULOS
            WHERE REMISIONARTICULO_REMISIONID = {$remisionid}
            AND REMISIONARTICULO_STOCKID    = {$stockid}
        ");
        if ($ex && $ex != 0) {
            if ($ownConn) $db->close();
            echo "YA";
            return;
        }

        // Obtén cliente del evento y artículo del stock
        $clienteId  = $this->getClienteIdByRemision($db, $remisionid);
        $articuloId = $this->getArticuloIdByStock($db, $stockid);

        if (!$articuloId) {
            if ($ownConn) $db->close();
            echo "Error: artículo no encontrado para el stock.";
            return;
        }

        // Precio (subtotal, iva, total) por cliente con fallback a NULL
        $p = $this->getPrecioArticulo($db, $articuloId, $clienteId);
        if (!$p) {
            if ($ownConn) $db->close();
            echo "Error: sin tarifa configurada (cliente y NULL).";
            return;
        }

        // Inserta detalle con los montos correctos desde AMPAR_CAT_ARTPRECIO
        $sql = "
            INSERT INTO AMPAR_HIS_REMISIONESARTICULOS
            (REMISIONARTICULO_REMISIONID, REMISIONARTICULO_STOCKID,
            REMISIONARTICULO_SUBTOTAL, REMISIONARTICULO_IVA, REMISIONARTICULO_TOTAL)
            VALUES
            ({$remisionid}, {$stockid},
            {$p['ARTPRECIO_SUBTOTAL']}, {$p['ARTPRECIO_IVA']}, {$p['ARTPRECIO_TOTAL']})
        ";
        $db->execute($sql);

        if ($ownConn) $db->close();
        echo ""; // éxito
    }

    public function createStockForArticle($db, $articuloId, $almacenId, $customFolio = null)
    {
        $articuloId = (int)$articuloId;
        $almacenId = (int)$almacenId;

        if ($customFolio) {
            $folio = $customFolio;
        } else {
            // Generar un folio de stock del formato A00000-YY
            $sqlFolio = "
                SELECT 'A' ||
                    LPAD(COALESCE(MAX(CAST(SUBSTRING(STOCK_FOLIO FROM 2 FOR 5) AS INTEGER)),0)+1, 5, '0')
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE)-2000 AS VARCHAR(2)) AS FOLIO
                FROM AMPAR_HIS_STOCK
                WHERE STOCK_FOLIO LIKE 'A%'
            ";
            $resFolio = $db->query($sqlFolio);
            $folio = ($resFolio && !empty($resFolio)) ? $resFolio[0]['FOLIO'] : 'A00001-26';
        }

        // Insertar en AMPAR_HIS_STOCK con estatus = 1 (disponible)
        $sql = "
            INSERT INTO AMPAR_HIS_STOCK
            (STOCK_FECHA, STOCK_FOLIO, STOCK_STOCKSTATUSID, STOCK_ALMACENIDACTUAL, STOCK_ARTICULOID)
            VALUES
            (CURRENT_TIMESTAMP, '{$folio}', 1, {$almacenId}, {$articuloId})
        ";
        $db->execute($sql);

        // Obtener el ID insertado
        $res = $db->query("SELECT STOCK_ID FROM AMPAR_HIS_STOCK WHERE STOCK_FOLIO = '{$folio}'");
        return ($res && !empty($res)) ? (int)$res[0]['STOCK_ID'] : null;
    }

    private function getClienteIdByRemision($db, $remisionId)
    {
        $remisionId = (int)$remisionId;
        $sql = "
            SELECT E.EVENTO_HOSPITALID
            FROM AMPAR_HIS_REMISIONES R
            JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = R.REMISION_EVENTOID
            WHERE R.REMISION_ID = {$remisionId}
        ";
        $r = $db->query($sql);
        return ($r && $r != 0 && isset($r[0]['EVENTO_HOSPITALID']) && is_numeric($r[0]['EVENTO_HOSPITALID'])) ? (int)$r[0]['EVENTO_HOSPITALID'] : 0;
    }

    private function getArticuloIdByStock($db, $stockid)
    {
        $sql = "
            SELECT
                COALESCE(S.STOCK_ARTICULOID, ED.ESDET_ARTICULOID) AS ARTICULO_ID
            FROM AMPAR_HIS_STOCK S
            LEFT JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ID = S.STOCK_ESDETID
            WHERE S.STOCK_ID = {$stockid}
        ";
        $r = $db->query($sql);
        return ($r && $r != 0) ? (int)$r[0]['ARTICULO_ID'] : null;
    }

    private function getPrecioArticulo($db, $articuloId, $clienteId)
    {
        // 1) Busca precio por cliente
        $sql1 = "
            SELECT FIRST 1 ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL
            FROM AMPAR_CAT_ARTPRECIO
            WHERE ARTPRECIO_ARTICULOID = {$articuloId}
            AND ARTPRECIO_CLIENTEID  = {$clienteId}
        ";
        $r1 = $db->query($sql1);
        if ($r1 && $r1 != 0) return $r1[0];

        // 2) Fallback a cliente NULL
        $sql2 = "
            SELECT FIRST 1 ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL
            FROM AMPAR_CAT_ARTPRECIO
            WHERE ARTPRECIO_ARTICULOID = {$articuloId}
            AND ARTPRECIO_CLIENTEID  IS NULL
        ";
        $r2 = $db->query($sql2);
        return ($r2 && $r2 != 0) ? $r2[0] : null;
    }

    function getremisionesmostrador($concepto = '')
    {
        $db = new FirebirdConnection();
        $filtroConcepto = "";
        if ($concepto !== '') {
            $filtroConcepto = " AND RE.CONCEPTO_REMISION = '{$concepto}' ";
        }
        $sql = "
            SELECT RE.*, S.STATUS_NOMBRE, S.STATUS_COLOR, SU.NOMBRE AS SUCURSAL_NOMBRE, A.ALMACEN_NOMBRE,
            (
                SELECT SUM(REMISIONARTICULO_TOTAL) 
                FROM AMPAR_HIS_REMISIONESARTICULOS 
                WHERE REMISIONARTICULO_REMISIONID = RE.REMISION_ID
            ) AS TOTAL,
            (
                SELECT COUNT(*)
                FROM AMPAR_HIS_REMISIONESARTICULOS
                WHERE REMISIONARTICULO_REMISIONID = RE.REMISION_ID
            ) AS CANTIDAD,
            (
                SELECT FIRST 1 MST.ALMACEN_FOLIO || ' - ' || MST.ALMACEN_NOMBRE
                FROM AMPAR_HIS_REMISIONESARTICULOS REA
                JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = REA.REMISIONARTICULO_STOCKID
                JOIN AMPAR_HIS_ALMACEN MST ON MST.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
                WHERE REA.REMISIONARTICULO_REMISIONID = RE.REMISION_ID
                AND MST.ALMACEN_TIPOALMACEN = 3
            ) AS MALETA_INFO
            FROM AMPAR_HIS_REMISIONES RE
            LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = RE.REMISION_STATUS
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = RE.REMISION_SUCURSALID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = RE.REMISION_ALMACENID
            WHERE RE.REMISION_EVENTOID IS NULL
            {$filtroConcepto}
            ORDER BY RE.REMISION_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function guardarremisionmostrador($almacenid, $tiposalida, $invdetids, $subtotales, $ivas, $totales, $clienteid = null)
    {
        $db = new FirebirdConnection();
        try {
            // Obtener sucursal del almacén
            $sqlSuc = "SELECT ALMACEN_SUCURSAL_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = {$almacenid}";
            $sucRes = $db->query($sqlSuc);
            $sucursalId = ($sucRes && count($sucRes) > 0) ? $sucRes[0]['ALMACEN_SUCURSAL_MS'] : null;

            if (!$sucursalId) throw new Exception("El almacén no tiene sucursal asociada.");

            $clienteSqlVal = ($clienteid > 0) ? $clienteid : 'NULL';

            // Obtener usuario de sesión
            $usersesionTemp = $_SESSION['ampar']['usuario'] ?? [];
            $usuarioIdVal = !empty($usersesionTemp['USUARIO_ID']) ? (int)$usersesionTemp['USUARIO_ID'] : 'NULL';

            // Insertar remisión
            $sqlRem = "
                INSERT INTO AMPAR_HIS_REMISIONES (
                    REMISION_FECHA, REMISION_FOLIO, REMISION_STATUS, REMISION_SUCURSALID, REMISION_ALMACENID, CONCEPTO_REMISION, REMISION_CLIENTEID, REMISION_USUARIOID
                ) VALUES (
                    CURRENT_TIMESTAMP,
                    (
                        SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(REMISION_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                        || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                        FROM AMPAR_HIS_REMISIONES
                    ),
                    1, -- Guardado (para permitir aceptar/cancelar después)
                    {$sucursalId},
                    {$almacenid},
                    '{$tiposalida}',
                    {$clienteSqlVal},
                    {$usuarioIdVal}
                )
            ";
            $remisionId = $db->executeconreturning($sqlRem, 'REMISION_ID');

            if (!$remisionId) throw new Exception("No se pudo crear el registro de remisión.");

            // Detalle e inventario
            $usersesion = $_SESSION['ampar']['usuario'] ?? 'sistema';
            $userId = $usersesion['USUARIO_ID'] ?? null;
            $userCorreo = $usersesion['USUARIO_CORREO'] ?? 'sistema';
            $ipuser = method_exists('bitacora', 'getip') ? bitacora::getip() : ($_SERVER['REMOTE_ADDR'] ?? null);

            $this->syncTableGenerator($db, 'AMPAR_HIS_REMISIONESARTICULOS', 'REMISIONARTICULO_ID');

            for ($i = 0; $i < count($invdetids); $i++) {
                $invdetid = $invdetids[$i];
                $subtotal = $subtotales[$i];
                $iva = $ivas[$i];
                $total = $totales[$i];

                $sqlDet = "
                    INSERT INTO AMPAR_HIS_REMISIONESARTICULOS (
                        REMISIONARTICULO_REMISIONID, REMISIONARTICULO_STOCKID, REMISIONARTICULO_SUBTOTAL, REMISIONARTICULO_IVA, REMISIONARTICULO_TOTAL
                    ) VALUES (
                        {$remisionId}, {$invdetid}, {$subtotal}, {$iva}, {$total}
                    )
                ";
                $db->execute($sqlDet);

                // No damos de baja aquí, se hará al cambiar status a 3 (Finalizada)

                // 3. Bitácora
                $infoStock = $db->query("
                    SELECT ST.STOCK_FOLIO, AR.NOMBRE, X.CLAVE_ARTICULO 
                    FROM AMPAR_HIS_STOCK ST
                    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                    LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                    WHERE ST.STOCK_ID = {$invdetid}
                ");

                $folioStock = $infoStock[0]['STOCK_FOLIO'] ?? '';
                $nombreArt = $infoStock[0]['NOMBRE'] ?? '';
                $claveArt = $infoStock[0]['CLAVE_ARTICULO'] ?? '';

                $comentario = "Baja por Remisión Mostrador ({$tiposalida}) #{$remisionId} - Art: {$nombreArt} ({$claveArt}) Folio: {$folioStock}";
                $comentarioSQL = str_replace("'", "''", $comentario);

                $sqlBit = "
                    INSERT INTO AMPAR_BITACORA (
                        BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
                        BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID
                    ) VALUES (
                        CURRENT_TIMESTAMP, '{$comentarioSQL}', " . ($userId ?: 'NULL') . ", '{$userCorreo}', " . ($ipuser ? "'{$ipuser}'" : "NULL") . ",
                        {$sucursalId}, {$almacenid}, {$invdetid}
                    )
                ";
                $db->execute($sqlBit);
            }

            $db->commit();

            // Obtener el folio generado
            $resFolio = $db->query("SELECT REMISION_FOLIO FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionId}");

            return [
                'remisionid' => $remisionId,
                'folio' => $resFolio[0]['REMISION_FOLIO'] ?? ''
            ];
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        } finally {
            $db->close();
        }
    }

    function actualizarremisionmostrador($remisionid, $idarticulos, $invdetids, $subtotales, $ivas, $totales)
    {
        $db = new FirebirdConnection();
        $remisionid = (int)$remisionid;

        try {
            if (method_exists($db, 'beginTransaction')) $db->beginTransaction();

            // 1. Restaurar stock anterior y eliminar detalle
            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 
                         WHERE STOCK_ID IN (SELECT REMISIONARTICULO_STOCKID FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_REMISIONID = {$remisionid})");
            $db->execute("DELETE FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_REMISIONID = {$remisionid}");

            // 2. Insertar nuevo detalle
            for ($i = 0; $i < count($invdetids); $i++) {
                $rawId = (string)$invdetids[$i];
                $invdetid = (int)$rawId;

                // Manejo de Equipo Capital (empieza con ec_ o es negativo)
                if (str_starts_with($rawId, 'ec_') || $invdetid < 0) {
                    $eqId = abs((int)str_replace('ec_', '', $rawId));
                    $resEq = $db->query("SELECT ARTICULO_ID, FOLIO FROM AMPAR_EQUIPOCAPITAL WHERE EQUIPOCAPITAL_ID = {$eqId}");
                    if ($resEq && !empty($resEq)) {
                        $artIdEq = (int)$resEq[0]['ARTICULO_ID'];
                        $folioEq = $resEq[0]['FOLIO'];

                        $remInfo = $db->query("SELECT REMISION_ALMACENID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
                        $almIdEq = ($remInfo && !empty($remInfo)) ? (int)$remInfo[0]['REMISION_ALMACENID'] : 1;

                        $newStockId = $this->createStockForArticle($db, $artIdEq, $almIdEq, $folioEq);
                        if ($newStockId) {
                            $invdetid = $newStockId;
                        }
                    }
                }

                $subtotal = (float)$subtotales[$i];
                $iva = (float)$ivas[$i];
                $total = (float)$totales[$i];

                $sqlDet = "
                    INSERT INTO AMPAR_HIS_REMISIONESARTICULOS (
                        REMISIONARTICULO_REMISIONID, REMISIONARTICULO_STOCKID, REMISIONARTICULO_SUBTOTAL, REMISIONARTICULO_IVA, REMISIONARTICULO_TOTAL
                    ) VALUES (
                        {$remisionid}, {$invdetid}, {$subtotal}, {$iva}, {$total}
                    )
                ";
                $db->execute($sqlDet);

                // 2.2 Reflejar baja (3 = Baja)
                $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 3 WHERE STOCK_ID = {$invdetid}");
            }

            if (method_exists($db, 'commit')) $db->commit();
            $db->close();
            return ['status' => 'success', 'message' => 'Remisión actualizada con éxito.'];
        } catch (Throwable $e) {
            if (method_exists($db, 'rollBack')) $db->rollBack();
            $db->close();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    function agregarArticuloDetalleRemision($remisionid, $invdetid, $subtotal, $iva, $total)
    {
        $db = $this->db();
        try {
            $remisionid = (int)$remisionid;
            $invdetid = (int)$invdetid;
            $subtotal = (float)$subtotal;
            $iva = (float)$iva;
            $total = (float)$total;

            // 1. Insertar detalle
            $sql = "INSERT INTO AMPAR_HIS_REMISIONESARTICULOS (
                        REMISIONARTICULO_REMISIONID, REMISIONARTICULO_STOCKID, 
                        REMISIONARTICULO_SUBTOTAL, REMISIONARTICULO_IVA, REMISIONARTICULO_TOTAL
                    ) VALUES (
                        {$remisionid}, {$invdetid}, {$subtotal}, {$iva}, {$total}
                    ) RETURNING REMISIONARTICULO_ID";
            $res = $db->query($sql);
            $newId = $res[0]['REMISIONARTICULO_ID'] ?? 0;

            // 2. Reflejar baja en stock (3 = Baja)
            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 3 WHERE STOCK_ID = {$invdetid}");

            $db->close();
            return ['status' => 'success', 'id' => $newId];
        } catch (Throwable $e) {
            $db->close();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    function actualizarPrecioDetalleRemision($detalleid, $subtotal, $iva, $total)
    {
        $db = $this->db();
        try {
            $detalleid = (int)$detalleid;
            $subtotal  = (float)$subtotal;
            $iva       = (float)$iva;
            $total     = (float)$total;
            $db->execute("UPDATE AMPAR_HIS_REMISIONESARTICULOS
                          SET REMISIONARTICULO_SUBTOTAL = {$subtotal},
                              REMISIONARTICULO_IVA      = {$iva},
                              REMISIONARTICULO_TOTAL    = {$total}
                          WHERE REMISIONARTICULO_ID = {$detalleid}");
            $db->close();
            return ['status' => 'success'];
        } catch (Throwable $e) {
            $db->close();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    function eliminarArticuloDetalleRemision($detalleid)
    {
        $db = $this->db();
        try {
            $detalleid = (int)$detalleid;

            // 1. Obtener stockid para devolverlo
            $res = $db->query("SELECT REMISIONARTICULO_STOCKID FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_ID = {$detalleid}");
            if ($res && count($res) > 0) {
                $stockid = $res[0]['REMISIONARTICULO_STOCKID'];
                // 2. Devolver stock a disponible (1)
                $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = {$stockid}");
            }

            // 3. Eliminar detalle
            $db->execute("DELETE FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_ID = {$detalleid}");

            $db->close();
            return ['status' => 'success'];
        } catch (Throwable $e) {
            $db->close();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
