<?php
class entradasalida{

    function getentradasalida(){
        $db = new FirebirdConnection();
        $usuarioId = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
        $filtroUsuario = ($GLOBALS['isAdmin'] ?? false) ? "" : " WHERE ES.ES_USUARIOID = " . (int)$usuarioId;
        
        $sql = "
            SELECT 
            ES.*, A.*, SU.*, S.*, 
            (SELECT COUNT(*) FROM AMPAR_HIS_ESDET ED WHERE ED.ESDET_ESID = ES.ES_ID) CANTIDAD,
            (
                SELECT COUNT(*)
                FROM AMPAR_HIS_ESDET ED
                WHERE ED.ESDET_ESID = ES.ES_ID
                AND ED.ESDET_CADUCIDAD IS NOT NULL
                AND ED.ESDET_CADUCIDAD <= DATEADD(YEAR, 1, ES.ES_FECHA)
            ) AS CADUCIDADMENOS1ANIO
            FROM AMPAR_HIS_ES ES 
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS 
            $filtroUsuario
            ORDER BY ES_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getgarantiaproveedor(){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            CP.*, PR.NOMBRE NOMBREPROVEEDOR, S.*, A.*, SU.NOMBRE SUCURSAL_NOMBRE
            FROM AMPAR_CADUCIDADPROV CP
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = CADUCIDADPROV_STATUS
            LEFT JOIN PROVEEDORES PR ON PROVEEDOR_ID = CADUCIDADPROV_PROVID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = CADUCIDADPROV_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            ORDER BY CADUCIDADPROV_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfoentradasalidabyid($esid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT STOCK_ID, STOCK_FOLIO, STOCK_LOTE, STOCK_CADUCIDAD, ES.*, ED.*, AR.NOMBRE ARTICULO_NOMBRE, AR.SEGUIMIENTO, CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, A.*, S.*, X.CLAVE_ARTICULO, SUCURSAL_ID, SU.NOMBRE SUCURSAL_NOMBRE, U.USUARIO_NOMBRE, PR.NOMBRE PROVEEDOR_NOMBRE, TA.TIPOALMACEN_NOMBRE, CC.CARTACANJE_RUTA_IMG, CC.CARTACANJE_NUM_DELIVERY,
            (SELECT FIRST 1 O.OC_FOLIO FROM AMPAR_RECEPCION R LEFT JOIN AMPAR_OC O ON O.OC_ID = R.RECEPCION_OCID WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))) AS OC_FOLIO_REAL,
            (SELECT FIRST 1 R.RECEPCION_FACTURA FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_FACTURA,
            (SELECT FIRST 1 R.RECEPCION_EVIDENCIA FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_EVIDENCIA,
            (SELECT FIRST 1 R.RECEPCION_LISTA_EMBARQUE FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_LISTA_EMBARQUE,
            (SELECT FIRST 1 R.RECEPCION_CARTA_CANJE FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_CARTA_CANJE,
            (SELECT FIRST 1 R.RECEPCION_DELIVERY_NUM FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_DELIVERY_NUM
            FROM AMPAR_HIS_ES ES
            LEFT JOIN AMPAR_HIS_ESDET ED on ESDET_ESID = ES_ID
            LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = ESDET_STOCKIDSALIDA
            LEFT JOIN AMPAR_CARTA_CANJE CC ON CC.CARTACANJE_ESDETID = ED.ESDET_ID
            LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
            LEFT JOIN ARTICULOS AR on ARTICULO_ID = ESDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN AMPAR_CONF_TIPOALMACEN TA ON TA.TIPOALMACEN_ID = A.ALMACEN_TIPOALMACEN
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS
            LEFT JOIN AMPAR_CAT_USUARIOS U ON USUARIO_ID = ES_USUARIOID
            LEFT JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = ES_IDPROVEEDOR
            WHERE
            ES_ID = ".$esid."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfogarantiaproveedorbyid($id){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            CP.*, CPD.*, PR.NOMBRE NOMBREPROVEEDOR, PR.EMAIL CORREOPROVEEDOR, S.*, A.*, SU.NOMBRE SUCURSAL_NOMBRE, U.*, ST.*, AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO
            FROM AMPAR_CADUCIDADPROV CP
            LEFT JOIN AMPAR_CADUCIDADPROVDET CPD ON CADUCIDADPROVDET_CADPROVID = CADUCIDADPROV_ID
            LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = CADUCIDADPROVDET_STOCKID
            LEFT JOIN ARTICULOS AR on ARTICULO_ID = STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = CADUCIDADPROV_STATUS
            LEFT JOIN PROVEEDORES PR ON PROVEEDOR_ID = CADUCIDADPROV_PROVID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = CADUCIDADPROV_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CAT_USUARIOS U ON USUARIO_ID = CADUCIDADPROV_USUARIOID
            WHERE CADUCIDADPROV_ID = ".$id."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getdsgarantiaproveedoractivosbyid($id){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            *
            FROM AMPAR_CADUCIDADPROVDETDS
            LEFT JOIN AMPAR_CAT_USUARIOS ON USUARIO_ID = CADUCIDADPROVDETDS_USUARIOID
            WHERE CADUCIDADPROVDETDS_CP = ".$id."
            AND CADUCIDADPROVDETDS_ACTIVO = 1
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getdsgarantiaproveedorinactivosbyid($id){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            *
            FROM AMPAR_CADUCIDADPROVDETDS
            LEFT JOIN AMPAR_CAT_USUARIOS ON USUARIO_ID = CADUCIDADPROVDETDS_USUARIOID
            WHERE CADUCIDADPROVDETDS_CP = ".$id."
            AND CADUCIDADPROVDETDS_ACTIVO = 0
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function uploaddsgarantiaprov($cpid,$files){

        $usersesion = $_SESSION['ampar']['usuario'];
        $nombreOriginal = $files['archivo']['name'];
        $tmp = $files['archivo']['tmp_name'];
        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        
        // Renombrar para evitar conflictos
        $nombreNuevo = uniqid('arch_', true) . '.' . strtolower($ext);
        $destino = '../uploads/garantiaproveedor/' . $nombreNuevo;
        
        if (move_uploaded_file($tmp, $destino)) {
            $db = new FirebirdConnection();
            $db->execute("INSERT INTO AMPAR_CADUCIDADPROVDETDS 
                        (CADUCIDADPROVDETDS_CP, CADUCIDADPROVDETDS_NOMBRE, CADUCIDADPROVDETDS_FECHA, CADUCIDADPROVDETDS_USUARIOID, CADUCIDADPROVDETDS_ACTIVO)
                        VALUES (?, ?, CURRENT_TIMESTAMP, ?, 1)", 
                        [$cpid, $nombreNuevo, $usersesion['USUARIO_ID']]);
            $db->close();
        } else {
            echo "Error al subir el archivo";
        }
    }

    function eliminardsgarantiaprov($id){
        $usersesion = $_SESSION['ampar']['usuario'];
        $db = new FirebirdConnection();
        $db->execute("
            UPDATE AMPAR_CADUCIDADPROVDETDS 
            SET CADUCIDADPROVDETDS_ACTIVO = 0
            WHERE CADUCIDADPROVDETDS_ID = ".$id."
        ");
        $db->close();
    }

    function finalizarfolioproveedor($id,$detalle){
        $usersesion = $_SESSION['ampar']['usuario'];
        $db = new FirebirdConnection();
        //MODIFICA EL STATUS A FINALIZADO
        $sql0 = "
                UPDATE AMPAR_CADUCIDADPROV
                SET CADUCIDADPROV_STATUS = 3
                WHERE 
                CADUCIDADPROV_ID = ".$id."
        ";
        $db->execute($sql0);
        
        foreach ($detalle as $det){
            //ACTUALIZAR STATUS DETALLE
            $sql2= "
                UPDATE AMPAR_CADUCIDADPROVDET
                SET
                CADUCIDADPROVDET_REEMPLAZADO = 1
                WHERE 
                CADUCIDADPROVDET_ID = ".$det."
            ";
            $db->execute($sql2);
        }

        $db->close();
    }

    function getinfoentradasalidasindetallebyid($esid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT ES.*, CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, STATUS_NOMBRE, A.ALMACEN_SUCURSAL_MS, A.ALMACEN_NOMBRE, SU.NOMBRE SUCURSAL_NOMBRE, PR.NOMBRE NOMBREPROVEEDOR, PR.EMAIL CORREOPROVEEDOR,
            (SELECT FIRST 1 O.OC_FOLIO FROM AMPAR_RECEPCION R LEFT JOIN AMPAR_OC O ON O.OC_ID = R.RECEPCION_OCID WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))) AS OC_FOLIO_REAL,
            (SELECT FIRST 1 R.RECEPCION_FACTURA FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_FACTURA,
            (SELECT FIRST 1 R.RECEPCION_EVIDENCIA FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_EVIDENCIA,
            (SELECT FIRST 1 R.RECEPCION_LISTA_EMBARQUE FROM AMPAR_RECEPCION R WHERE ES.ES_MOTIVO LIKE 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ABS(DATEDIFF(SECOND, R.RECEPCION_FECHA, ES.ES_FECHA)) ASC) AS RECEPCION_LISTA_EMBARQUE
            FROM AMPAR_HIS_ES ES
            LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS 
            LEFT JOIN PROVEEDORES PR ON PROVEEDOR_ID = ES_IDPROVEEDOR
            WHERE
            ES_ID = ".$esid."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfoesdetallebyesdetid($db,$esdetid){
        $sql = "
            SELECT
            ED.*, E.ES_FOLIO, E.ES_TIPO, E.ES_ALMACENID, ALMACEN_SUCURSAL_MS, X.CLAVE_ARTICULO ARTICULO_CLAVE, AR.NOMBRE ARTICULO_NOMBRE
            FROM AMPAR_HIS_ESDET ED
            LEFT JOIN AMPAR_HIS_ES E ON ES_ID = ESDET_ESID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ARTICULOS AR on ARTICULO_ID = ESDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE
            ESDET_ID = ".$esdetid."
        ";
        $result = $db->query($sql);
        return $result;
    }

    function getinfostockbyentradasalidabyid($esid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT ST.*, ES.*, ED.*, AR.NOMBRE ARTICULO_NOMBRE, CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, A.*, S.*, X.CLAVE_ARTICULO, SU.NOMBRE SUCURSAL_NOMBRE
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN AMPAR_HIS_ESDET ED on ESDET_ID = STOCK_ESDETID
            LEFT JOIN AMPAR_HIS_ES ES ON ES_ID = ESDET_ESID
            LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
            LEFT JOIN ARTICULOS AR on ARTICULO_ID = ESDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS 
            WHERE
            ES_ID = ".$esid."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfostockbyfolio($folio){
        $db = new FirebirdConnection();
        $sql = "
            SELECT ST.*, ES.*, ED.*, AR.NOMBRE ARTICULO_NOMBRE, CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, A.*, S.*, X.CLAVE_ARTICULO, SU.NOMBRE SUCURSAL_NOMBRE
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN AMPAR_HIS_ESDET ED on ESDET_ID = STOCK_ESDETID
            LEFT JOIN AMPAR_HIS_ES ES ON ES_ID = ESDET_ESID
            LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
            LEFT JOIN ARTICULOS AR on ARTICULO_ID = ESDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS 
            WHERE ST.STOCK_FOLIO = ?
        ";
        $result = $db->query($sql, [$folio]);
        $db->close();
        return $result;
    }

    //GUARDAR ENTRADA SALIDA
    function guardarentradasalida($tipo, $concepto, $almacen, $motivo, $idproveedor, $correoproveedor, $articulos, $stocks, $cantidades, $lotes, $caducidades, $caducidadesmenor1anio, $series, $invdetpadre = 'null') {
        $usersesion = $_SESSION['ampar']['usuario'];
        $db = new FirebirdConnection();
    
        // Insertar encabezado
        $sql = "
            INSERT INTO AMPAR_HIS_ES
            (
                ES_FECHA, 
                ES_FOLIO, 
                ES_ALMACENID, 
                ES_CONCEPTOID, 
                ES_MOTIVO, 
                ES_STATUS, 
                ES_TIPO, 
                ES_USUARIOID,
                ES_IDPROVEEDOR,
                ES_CORREOPROVEEDOR
            )
            VALUES (
                CURRENT_TIMESTAMP,
                (
                    SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(ES_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                    FROM AMPAR_HIS_ES
                ),
                ?, ?, ?, 1, ?, ?, ?, ?
            )
        ";
    
        $params = [$almacen, $concepto, $motivo, $tipo, $usersesion['USUARIO_ID'],$idproveedor,$correoproveedor];
        $id_insertado = $db->executeconreturning($sql, 'ES_ID', $params);
        
        if (!$id_insertado) {
            $db->close();
            echo "Error al insertar registro.";
            return false;
        }

        // Guardar detalle usando la misma conexión
        $this->guardarentradasalidadet($db, $tipo, $id_insertado, $articulos, $stocks, $lotes, $caducidades, $caducidadesmenor1anio, $series,$usersesion);

        $db->close();
        
    }

    //GUARDAR ENTRADA SALIDA DETALLE
    function guardarentradasalidadet($db, $tipo, $ides, $articulos, $stocks, $lotes, $caducidades, $caducidadesmenor1anio, $series, $usersesion) {
        $ipuser = bitacora::getip();
        try {
            // Inicia la transacción
            $db->beginTransaction();

            if ($tipo == 'R'){
                //Consulto la información detallada
                $articulosList = implode(",", $articulos);
                $sql00 = "
                    SELECT CADUCIDADPROVDET_ID, a.ARTICULO_ID, s.STOCK_ID, STOCK_ALMACENIDACTUAL, STOCK_LOTE, STOCK_CADUCIDAD, STOCK_SERIE
                    FROM AMPAR_CADUCIDADPROVDET cpd
                    LEFT JOIN AMPAR_HIS_STOCK s ON s.STOCK_ID = cpd.CADUCIDADPROVDET_STOCKID 
                    LEFT JOIN ARTICULOS a ON a.ARTICULO_ID = s.STOCK_ARTICULOID 
                    WHERE cpd.CADUCIDADPROVDET_ID in (".$articulosList.")
                ";
                $info = $db->query($sql00);
                $articulos = [];
                $stocks = [];
                $lotes = [];
                $caducidades = [];
                $series = [];

                foreach ($info as $row) {
                    $articulos[] = $row['ARTICULO_ID'];
                    $stocks[] = $row['STOCK_ID'];
                    $lotes[] = $row['STOCK_LOTE'];
                    $caducidades[] = $row['STOCK_CADUCIDAD'];
                    $series[] = $row['STOCK_SERIE'];

                    //INSERTA ENTRADAS
                    $sql0 = "
                        INSERT INTO AMPAR_HIS_ESDET
                        (ESDET_ESID, ESDET_ARTICULOID, ESDET_ACTIVO)
                        VALUES (".$ides.", ".$row['ARTICULO_ID'].", 0)
                    ";
                    
                    $db->execute($sql0);

                    // Actualiza campo reemplazado
                    $sql00 = "
                        UPDATE AMPAR_CADUCIDADPROVDET
                        SET CADUCIDADPROVDET_REEMPLAZADO = 1
                        WHERE CADUCIDADPROVDET_ID = ".$row['CADUCIDADPROVDET_ID']."
                    ";
                    $db->execute($sql00);

                    /*
                    bitacora::guardardb(
                        $db,
                        'DETALLE DE SOLICITUD DE REPOSICION'.' FOLIO:<strong>' . $infodet[0]['ES_FOLIO'] . '</strong> Campos:' . bitacora::printarray($infodet[0]),
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $infodet[0]['ALMACEN_SUCURSAL_MS'],
                        $infodet[0]['ES_ALMACENID']
                    );


                    $sqlBit = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID)
                        VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?)";

                    // Comentario general
                    $comentarioGeneral = 'SOLICITUD DE ' . (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                        ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'] . '</strong> (' . $infoes[0]['ALMACEN_NOMBRE'] . ') Campos:' .
                        bitacora::printarray($infoes[0]);
                    $db->execute($sqlBit, [
                        $comentarioGeneral,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $infoes[0]['ALMACEN_SUCURSAL_MS'] ?? null,
                        $infoes[0]['ES_ALMACENID'] ?? null
                    ]);
                    */

                }
            }

            // ── Lógica de Carta Canje y Delivery ──
            $ruta_carta_canje = null;
            $num_delivery = $_POST['num_delivery_general'] ?? null;
            if (isset($_FILES['archivo_carta_canje_general']) && $_FILES['archivo_carta_canje_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['archivo_carta_canje_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'CC_ENT_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    // Se asume que el directorio existe, de la misma forma que en recepciones
                    $dirUploads = __DIR__ . '/../uploads/recepciones/' . date('Y-m');
                    if (!is_dir($dirUploads)) { mkdir($dirUploads, 0777, true); }
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($_FILES['archivo_carta_canje_general']['tmp_name'], $rutaDestino)) {
                        $ruta_carta_canje = 'uploads/recepciones/' . date('Y-m') . '/' . $nombreArchivo;
                    }
                }
            }

            // Preparar SQL de detalle
            $sqlDetalle = "INSERT INTO AMPAR_HIS_ESDET
                (ESDET_ESID, ESDET_ARTICULOID, ESDET_LOTE, ESDET_CADUCIDAD, ESDET_SERIE, ESDET_ACTIVO, ESDET_STOCKIDSALIDA)
                VALUES (?, ?, ?, ?, ?, 0, ?)";

            for ($i = 0; $i < count($articulos); $i++) {
                $idesdet = $db->executeconreturning($sqlDetalle, 'ESDET_ID', [
                    $ides,
                    $articulos[$i],
                    $lotes[$i] ?? null,
                    $caducidades[$i] ?? null,
                    $series[$i] ?? null,
                    $stocks[$i] ?? null
                ]);

                // Actualizar estado del stock si es salida o reingreso
                if ($tipo == "S" || $tipo == "R") {
                    $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?", [$stocks[$i]]);
                }

                // Guardar en Carta Canje si la caducidad es menor a 1 año
                if (!empty($caducidadesmenor1anio[$i]) && $caducidadesmenor1anio[$i] == 1) {
                    if (!empty($ruta_carta_canje) || !empty($num_delivery)) {
                         $sqlCC = "INSERT INTO AMPAR_CARTA_CANJE (CARTACANJE_ESDETID, CARTACANJE_RUTA_IMG, CARTACANJE_NUM_DELIVERY, CARTACANJE_RECEPCIONID) VALUES (?, ?, ?, NULL)";
                         try { 
                             $db->execute($sqlCC, [$idesdet, $ruta_carta_canje, $num_delivery]); 
                         } catch (\Throwable $e) {
                             error_log("Error guardando carta canje en entrada: " . $e->getMessage());
                         }
                    }
                }
            }

            // --- BITACORA ---

            $sqlbitacora = "SELECT 
                ES.*, 
                CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, STATUS_NOMBRE, 
                A.ALMACEN_SUCURSAL_MS, A.ALMACEN_NOMBRE, 
                SU.NOMBRE SUCURSAL_NOMBRE, 
                PR.NOMBRE NOMBREPROVEEDOR, PR.EMAIL CORREOPROVEEDOR,
                ED.*, X.CLAVE_ARTICULO ARTICULO_CLAVE, AR.NOMBRE ARTICULO_NOMBRE,
                ST.STOCK_FOLIO, ST.STOCK_ID
                FROM AMPAR_HIS_ES ES
                LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
                LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
                LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
                LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS 
                LEFT JOIN PROVEEDORES PR ON PROVEEDOR_ID = ES_IDPROVEEDOR
                LEFT JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ESID = ES.ES_ID 
                LEFT JOIN ARTICULOS AR on ARTICULO_ID = ED.ESDET_ARTICULOID
                LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = ED.ESDET_STOCKIDSALIDA
                WHERE ES.ES_ID = ?";
            $infoes = $db->query($sqlbitacora, [$ides]);

            if ($infoes) {
                $sqlBit = "INSERT INTO AMPAR_BITACORA
                    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID)
                    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?)";

                // Comentario general
                $comentarioGeneral = 'SOLICITUD DE ' . (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                    ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'] . '</strong> (' . $infoes[0]['ALMACEN_NOMBRE'] . ') Campos:' .
                    bitacora::printarray($infoes[0]);
                $db->execute($sqlBit, [
                    $comentarioGeneral,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser,
                    $infoes[0]['ALMACEN_SUCURSAL_MS'] ?? null,
                    $infoes[0]['ES_ALMACENID'] ?? null
                ]);

                // Comentarios por detalle
                foreach ($infoes as $detalle) {
                    // Comentario normal
                    $comentarioDetalle = 'SE AGREGÓ ARTÍCULO '.$detalle['ARTICULO_NOMBRE'].' A DETALLE DE SOLICITUD DE ' .
                        (($detalle['ES_TIPO'] == 'E') ? 'ENTRADA' : 'SALIDA') .
                        ' FOLIO:<strong>' . $detalle['ES_FOLIO'] . '</strong> Campos:' .
                        bitacora::printarray($detalle);

                    $db->execute($sqlBit, [
                        $comentarioDetalle,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $detalle['ALMACEN_SUCURSAL_MS'] ?? null,
                        $detalle['ES_ALMACENID'] ?? null
                    ]);

                    // Segundo comentario si es salida o reingreso
                    if ($tipo == "S" || $tipo == "R") {

                        $sqlBitStock = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
                        VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)";
                        $comentarioStock = 'SE APARTÓ ARTÍCULO <b>' . $detalle['STOCK_FOLIO'] . '</b> EN ALMACÉN <b>' . $detalle['ALMACEN_NOMBRE'] . '</b>. Campos:' . bitacora::printarray($detalle);

                        $db->execute($sqlBitStock, [
                            $comentarioStock,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $detalle['ALMACEN_SUCURSAL_MS'] ?? null,
                            $detalle['ES_ALMACENID'] ?? null,
                            $detalle['STOCK_ID'] ?? null
                        ]);
                    }
                }

            }

            // Confirma la transacción
            $db->commit();

        } catch (Exception $e) {
            $db->rollback();
            throw new Exception("Error al guardar entrada/salida: " . $e->getMessage());
        }
    }

    //EDICION ENTRADA SALIDA
    function actualizarEntradasalida($sucursalid, $almacenid, $esid, $tipo, $concepto, $motivo, $idproveedor, $correoproveedor, $cambiosJson) {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        //CONSULTAINFORMACION DE ESID
        $sqlinfo = "SELECT
            ES.*,
            CI.CONCEPTOSAL_NOMBRE CONCEPTO_NOMBRE, STATUS_NOMBRE,
            A.ALMACEN_SUCURSAL_MS, A.ALMACEN_NOMBRE,
            SU.NOMBRE SUCURSAL_NOMBRE,
            PR.NOMBRE NOMBREPROVEEDOR, PR.EMAIL CORREOPROVEEDOR
            FROM AMPAR_HIS_ES ES
            LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI on CI.CONCEPTOSAL_ID = ES_CONCEPTOID
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID  = ES_STATUS
            LEFT JOIN PROVEEDORES PR ON PROVEEDOR_ID = ES_IDPROVEEDOR
            WHERE ES.ES_ID = ?
        ";
        $infoes = $db->query($sqlinfo, [$esid]);

        // Obtener la sucursal del almacén actual para la bitácora
        $sucursalParaBitacora = $infoes[0]['ALMACEN_SUCURSAL_MS'];
        $almacenParaBitacora = $infoes[0]['ES_ALMACENID'];

        $sqlBit = "INSERT INTO AMPAR_BITACORA
                    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
                    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)";

        // Decodificar cambios
        $cambios = json_decode($cambiosJson, true);

        // Validar que sea un array válido
        if (!is_array($cambios)) {
            echo "No se recibieron cambios válidos";
            return false;
        }

        // Si no hay cambios en cabecera ni en articulos, salir
        if (empty($cambios['cabecera']) && empty($cambios['articulos'])) {
            echo "No hay cambios para procesar";
            return false;
        }

        try {
            $db->beginTransaction();

            // --- ACTUALIZAR CABECERA SI HAY CAMBIOS ---
            if (!empty($cambios['cabecera'])) {
                $fields = [];
                $params = [];
                foreach ($cambios['cabecera'] as $campo => $valores) {
                    switch ($campo) {
                        case 'almacen':
                            $fields[] = "ES_ALMACENID = ?";
                            $params[] = $almacenid;
                            break;
                        case 'concepto':
                            $fields[] = "ES_CONCEPTOID = ?";
                            $params[] = $concepto; // asumimos viene el id correcto
                            break;
                        case 'motivo':
                            $fields[] = "ES_MOTIVO = ?";
                            $params[] = $motivo; // asumimos viene el id correcto
                            break;
                        case 'idproveedor':
                            $fields[] = "ES_IDPROVEEDOR = ?";
                            $params[] = $idproveedor;
                            break;
                        case 'correoproveedor':
                            $fields[] = "ES_CORREOPROVEEDOR = ?";
                            $params[] = $correoproveedor;
                            break;
                    }
                }
                if (!empty($fields)) {
                    // 1. Ejecutar el UPDATE
                    $sqlUpd = "UPDATE AMPAR_HIS_ES SET " . implode(", ", $fields) . " WHERE ES_ID = ?";
                    $params[] = $esid ?? null;
                    $db->execute($sqlUpd, $params);

                    // Si cambió el almacén, actualizar las variables para la bitácora
                    if (isset($cambios['cabecera']['almacen'])) {
                        $sqlAlmacen = "SELECT ALMACEN_SUCURSAL_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = ?";
                        $almacenInfo = $db->query($sqlAlmacen, [$almacenid]);
                        if (!empty($almacenInfo)) {
                            $sucursalParaBitacora = $almacenInfo[0]['ALMACEN_SUCURSAL_MS'];
                            $almacenParaBitacora = $almacenid;
                        }
                    }

                    // 2. Construir comentario de campos editados
                    $comentarioCampos = [];
                    if (!empty($cambios['cabecera'])) {
                        foreach ($cambios['cabecera'] as $campo => $valores) {
                            $comentarioCampos[] = "<strong>{$campo}</strong>: {$valores['original']} → {$valores['nuevo']}";
                        }
                    }

                    // Convertimos el array en string separado por comas
                    $camposEditadosStr = implode(", ", $comentarioCampos);

                    // 3. Comentario final para el historial
                    $comentarioBit = 'EDICIÓN DE SOLICITUD DE ' . (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                        ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'] . '</strong> (Sucursal:'.$infoes[0]['SUCURSAL_NOMBRE'].', Almacén:' . $infoes[0]['ALMACEN_NOMBRE'] . ') Campos: ' .
                        $camposEditadosStr;

                    // 4. Guardar en bitácora
                    $db->execute($sqlBit, [
                        $comentarioBit,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $sucursalParaBitacora,
                        $almacenParaBitacora,
                        null
                    ]);
                }
            }
            // --- PROCESAR ARTÍCULOS ---
            if (!empty($cambios['articulos'])) {
                foreach ($cambios['articulos'] as $art) {
                    switch ($art['tipo']) {
                        case 'agregado':
                            $sqlInsert = "INSERT INTO AMPAR_HIS_ESDET
                                (ESDET_ESID, ESDET_ARTICULOID, ESDET_LOTE, ESDET_CADUCIDAD, ESDET_SERIE, ESDET_ACTIVO, ESDET_STOCKIDSALIDA)
                                VALUES (?, ?, ?, ?, ?, 0, ?)";
                            $db->execute($sqlInsert, [
                                $esid,
                                $art['nuevo']['articuloid'],
                                $art['nuevo']['lote'] ?? null,
                                $art['nuevo']['caducidad'] ?? null,
                                $art['nuevo']['serie'] ?? null,
                                $art['nuevo']['invdetid'] ?? null
                            ]);

                            $complementocomentariobit = "";
                            if ($tipo == "S" || $tipo == "R") {
                                $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?", [$art['nuevo']['invdetid'] ?? null]);
                                $complementocomentariobit = " Y SE APARTO FOLIO:".$art['nuevo']['folioarticulo'].",";
                            }

                            // Agregar a Bitácota
                            $comentarioBit = 'SE AGREGÓ '.$complementocomentariobit.' ARTÍCULO '.$art['nuevo']['nombre'].' A DETALLE DE SOLICITUD DE ' .
                                (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                                ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'].'</strong> Campos: Lote: '.$art['nuevo']['lote'].', Caducidad: '.$art['nuevo']['caducidad'].', Serie:'.$art['nuevo']['serie'];

                            $db->execute($sqlBit, [
                                $comentarioBit,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $sucursalParaBitacora,
                                $almacenParaBitacora,
                                $art['nuevo']['invdetid'] ?? null
                            ]);
                            break;

                        case 'modificado':
                            $campos = [];
                            $params = [];

                            // 1. Actualizar campos en la tabla
                            foreach ($art['cambios'] as $campo => $valores) {
                                // Ignorar campos que no existan en la tabla AMPAR_HIS_ESDET
                                if (in_array(strtolower($campo), ['nombre', 'folioarticulo'])) continue;
                                
                                $campos[] = "ESDET_" . strtoupper($campo) . " = ?";
                                $params[] = $valores['nuevo'];
                            }

                            if (!empty($campos)) {
                                $sqlUpd = "UPDATE AMPAR_HIS_ESDET SET " . implode(", ", $campos) . " WHERE ESDET_ID = ?";
                                $params[] = $art['id'];
                                $db->execute($sqlUpd, $params);
                            }

                            // 2. Construir comentario de campos editados
                            $comentarioCampos = [];
                            foreach ($art['cambios'] as $campo => $valores) {
                                $comentarioCampos[] = "<strong>" . strtoupper($campo) . "</strong>: {$valores['original']} → {$valores['nuevo']}";
                            }
                            $camposEditadosStr = implode(", ", $comentarioCampos);

                            // 3. Agregar a Bitácora
                            $comentarioBit = 'SE MODIFICÓ ARTÍCULO <strong>' . $art['nombre'] . '</strong> DE DETALLE DE SOLICITUD DE ' .
                                (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                                ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'] . '</strong> Campos editados: ' . $camposEditadosStr;

                            $db->execute($sqlBit, [
                                $comentarioBit,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $sucursalParaBitacora,
                                $almacenParaBitacora,
                                $art['nuevo']['stockid'] ?? null
                            ]);
                            break;

                        case 'eliminado':
                            $sqlDel = "DELETE FROM AMPAR_HIS_ESDET WHERE ESDET_ID = ?";
                            $db->execute($sqlDel, [$art['id']]);

                            $complementocomentariobit = "";
                            if ($tipo == "S" || $tipo == "R") {
                                $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = ?", [$art['invdetid'] ?? null]);
                                $complementocomentariobit = " Y SE LIBERÓ FOLIO:".$art['folioarticulo'].",";
                            }

                            // Agregar a Bitácota
                            $comentarioBit = 'SE ELIMINÓ '.$complementocomentariobit.' ARTÍCULO '.$art['nombre'].' A DETALLE DE SOLICITUD DE ' .
                                (($tipo == 'E') ? 'ENTRADA' : (($tipo == 'S') ? 'SALIDA' : 'REPOSICIÓN')) .
                                ' FOLIO:<strong>' . $infoes[0]['ES_FOLIO'].'</strong> Campos: Lote: '.$art['lote'].', Caducidad: '.$art['caducidad'].', Serie:'.$art['serie'];

                            $db->execute($sqlBit, [
                                $comentarioBit,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $sucursalParaBitacora,
                                $almacenParaBitacora,
                                $art['stockid'] ?? null
                            ]);
                            break;
                    }
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            echo "Error al actualizar: " . $e->getMessage();
        } finally {
            $db->close();
        }
    }

    function old_guardarentradasalidadet($db, $tipo, $ides, $articulos, $stocks, $lotes, $caducidades, $caducidadesmenor1anio, $series) {
        $usersesion = $_SESSION['ampar']['usuario'];

        if ($tipo == 'R'){
            //Consulto la información detallada
            $articulosList = implode(",", $articulos);
            $sql00 = "
                SELECT CADUCIDADPROVDET_ID, a.ARTICULO_ID, s.STOCK_ID, STOCK_ALMACENIDACTUAL, STOCK_LOTE, STOCK_CADUCIDAD, STOCK_SERIE
                FROM AMPAR_CADUCIDADPROVDET cpd
                LEFT JOIN AMPAR_HIS_STOCK s ON s.STOCK_ID = cpd.CADUCIDADPROVDET_STOCKID 
                LEFT JOIN ARTICULOS a ON a.ARTICULO_ID = s.STOCK_ARTICULOID 
                WHERE cpd.CADUCIDADPROVDET_ID in (".$articulosList.")
            ";
            $info = $db->query($sql00);

            $articulos = [];
            $stocks = [];
            $lotes = [];
            $caducidades = [];
            $series = [];

            foreach ($info as $row) {
                $articulos[] = $row['ARTICULO_ID'];
                $stocks[] = $row['STOCK_ID'];
                $lotes[] = $row['STOCK_LOTE'];
                $caducidades[] = $row['STOCK_CADUCIDAD'];
                $series[] = $row['STOCK_SERIE'];

                //INSERTA ENTRADAS
                $sql0 = "
                    INSERT INTO AMPAR_HIS_ESDET
                    (ESDET_ESID, ESDET_ARTICULOID, ESDET_ACTIVO)
                    VALUES (".$ides.", ".$row['ARTICULO_ID'].", 0)
                ";
                // Inserta y obtiene el ID generado
                $id_insertadodet = $db->executeconreturning($sql0, 'ESDET_ID');

                // Actualiza campo reemplazado
                $sql00 = "
                    UPDATE AMPAR_CADUCIDADPROVDET
                    SET CADUCIDADPROVDET_REEMPLAZADO = 1
                    WHERE CADUCIDADPROVDET_ID = ".$row['CADUCIDADPROVDET_ID']."
                ";
                $db->execute($sql00);
                if ($id_insertadodet) {
                    // Obtener info del detalle para la bitácora
                    $infodet = $this->getinfoesdetallebyesdetid($db, $id_insertadodet);
        
                    bitacora::guardardb(
                        $db,
                        'DETALLE DE SOLICITUD DE REPOSICION'.' FOLIO:<strong>' . $infodet[0]['ES_FOLIO'] . '</strong> Campos:' . bitacora::printarray($infodet[0]),
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $infodet[0]['ALMACEN_SUCURSAL_MS'],
                        $infodet[0]['ES_ALMACENID']
                    );
        
                } else {
                    // Aquí podrías agregar manejo de error si la inserción falla
                    echo "Error al insertar detalle para el artículo ID: " . $row['ARTICULO_ID'];
                }

            }
        }
    
        for ($i = 0; $i < count($articulos); $i++) {
            $sql1 = "
                INSERT INTO AMPAR_HIS_ESDET
                (ESDET_ESID, ESDET_ARTICULOID, ESDET_LOTE, ESDET_CADUCIDAD, ESDET_SERIE, ESDET_ACTIVO, ESDET_STOCKIDSALIDA)
                VALUES (?, ?, ?, ?, ?, 0, ?)
            ";
            
            $param1 = $ides;
            $param2 = $articulos[$i];
            $param3 = (isset($lotes[$i]) && $lotes[$i] !== '') ? $lotes[$i] : null;
            $param4 = (isset($caducidades[$i]) && $caducidades[$i] !== '') ? $caducidades[$i] : null;
            $param5 = (isset($series[$i]) && $series[$i] !== '') ? $series[$i] : null;
            $param6 = (isset($stocks[$i]) && $stocks[$i] !== '') ? $stocks[$i] : null;
    
            $params = [$param1, $param2, $param3, $param4, $param5, $param6];
    
            // Inserta y obtiene el ID generado
            $id_insertadodet = $db->executeconreturning($sql1, 'ESDET_ID', $params);
    
            if ($id_insertadodet) {
                // Obtener info del detalle para la bitácora
                $infodet = $this->getinfoesdetallebyesdetid($db, $id_insertadodet);
    
                bitacora::guardardb(
                    $db,
                    'DETALLE DE SOLICITUD DE ' . (($infodet[0]['ES_TIPO'] == 'E') ? 'ENTRADA' : 'SALIDA') . ' FOLIO:<strong>' . $infodet[0]['ES_FOLIO'] . '</strong> Campos:' . bitacora::printarray($infodet[0]),
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $infodet[0]['ALMACEN_SUCURSAL_MS'],
                    $infodet[0]['ES_ALMACENID']
                );
    
                if ($tipo == "S" or $tipo=="R") {
                    //CONSULTA INFO 
                    $sql8 = "
                        SELECT STOCK_ID, STOCK_FOLIO, X.ARTICULO_CLAVE, AR.NOMBRE ARTICULO_NOMBRE, ALMACEN_ID, ALMACEN_NOMBRE, SUCURSAL_ID, S.NOMBRE SUCURSAL_NOMBRE 
                        FROM AMPAR_HIS_STOCK
                        LEFT JOIN AMPAR_HIS_ALMACEN AL ON ALMACEN_ID = STOCK_ALMACENIDACTUAL
                        LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = AL.ALMACEN_SUCURSAL_MS
                        LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
                        LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO ARTICULO_CLAVE, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                        WHERE STOCK_ID = ".$stocks[$i]."
                    ";
                    $articulosstock = $db->query($sql8);
                    // Actualizar estado del stock si es salida o reposicion
                    $sql2 = "UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?";
                    $db->execute($sql2, [$stocks[$i]]);
                    //Guarda en Bitácora

                    bitacora::guardardb(
                        $db,'SE APARTÓ ARTÍCULO <b>'.$articulosstock[0]['STOCK_FOLIO'].'</b> EN ALMACÉN <b>'.$articulosstock[0]['ALMACEN_NOMBRE'].'</b>. Campos:' . bitacora::printarray($articulosstock[0]),
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $articulosstock[0]['SUCURSAL_ID'],
                        $articulosstock[0]['ALMACEN_ID'],
                        $articulosstock[0]['STOCK_ID']
                    );
                }

            } else {
                // Aquí podrías agregar manejo de error si la inserción falla
                echo "Error al insertar detalle para el artículo ID: " . $articulos[$i];
            }
                
        }
    }    

    function autorizarentradasalida($esid, $tipo, $almacenid, $detalle) {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser     = $_SERVER['REMOTE_ADDR'] ?? null;
        $db         = new FirebirdConnection();

        // Para mapear ESDET_ID => STOCK_ID insertado (entradas) y reutilizar en caducidad/bitácoras
        $stockIdsPorEsdet = [];
        $stockInfoCache   = []; // STOCK_ID => row info para bitácora

        // SQL de bitácora (manual) reutilizable
        $sqlBit = "
            INSERT INTO AMPAR_BITACORA
            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
            BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)
        ";

        try {
            $db->beginTransaction();

            // Evitar doble autorización
            $checkStatus = $db->query("SELECT ES_STATUS FROM AMPAR_HIS_ES WHERE ES_ID = ?", [$esid]);
            if ($checkStatus && isset($checkStatus[0]) && (int)$checkStatus[0]['ES_STATUS'] === 3) {
                throw new Exception("La solicitud ya se encuentra autorizada, no se puede procesar dos veces.");
            }

            // 1) Actualiza cabecera a FINALIZADO (3)
            $db->execute("UPDATE AMPAR_HIS_ES SET ES_STATUS = 3 WHERE ES_ID = ?", [$esid]);

            // 2) Trae cabecera + todos los detalles y su stock (una sola consulta)
            $sqlInfo = "
                SELECT
                    E.ES_ID, E.ES_FOLIO, E.ES_TIPO, E.ES_ALMACENID,
                    A.ALMACEN_ID, A.ALMACEN_SUCURSAL_MS, A.ALMACEN_NOMBRE,
                    SU.SUCURSAL_ID, SU.NOMBRE SUCURSAL_NOMBRE,
                    S.STATUS_NOMBRE, E.ES_IDPROVEEDOR, E.ES_CORREOPROVEEDOR,
                    PR.NOMBRE AS NOMBREPROVEEDOR,
                    ED.ESDET_ID, ED.ESDET_ESID, ED.ESDET_ARTICULOID,
                    ED.ESDET_LOTE, ED.ESDET_CADUCIDAD, ED.ESDET_SERIE,
                    ED.ESDET_STOCKIDSALIDA,
                    ST.STOCK_ID, ST.STOCK_FOLIO, ST.STOCK_STOCKSTATUSID, ST.STOCK_ALMACENIDACTUAL
                FROM AMPAR_HIS_ES E
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = E.ES_STATUS
                LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = E.ES_ALMACENID
                LEFT JOIN ampar_cat_sucursales SU       ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                LEFT JOIN PROVEEDORES PR      ON PR.PROVEEDOR_ID = E.ES_IDPROVEEDOR
                LEFT JOIN AMPAR_HIS_ESDET ED  ON ED.ESDET_ESID = E.ES_ID
                LEFT JOIN AMPAR_HIS_STOCK ST  ON ST.STOCK_ID   = ED.ESDET_STOCKIDSALIDA
                WHERE E.ES_ID = ?
                ORDER BY ED.ESDET_ID
            ";
            $infoes = $db->query($sqlInfo, [$esid]);
            
            if ($infoes === 0) {
                throw new Exception("No se encontró información de la solicitud ES_ID={$esid}");
            }

            // Header (primera fila)
            $hdr = $infoes[0];

            // Bitácora cabecera
            $comentCab = 'CAMBIO DE STATUS A <strong>'.$hdr['STATUS_NOMBRE'].'</strong> DE SOLICITUD DE '
                    . (($hdr['ES_TIPO'] == 'E') ? 'ENTRADA' : 'SALIDA')
                    . ' FOLIO:<strong>'.$hdr['ES_FOLIO'].'</strong> ('.$hdr['ALMACEN_NOMBRE'].') Campos:'
                    . bitacora::printarray($hdr);
            $db->execute($sqlBit, [
                $comentCab,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $hdr['ALMACEN_SUCURSAL_MS'],
                $hdr['ES_ALMACENID'],
                null // BITACORA_STOCKID
            ]);

            // Índices útiles: ESDET por ID, y lista de todos los ESDET_ID con dato
            $detallesPorId = [];
            $todosEsdetIds = [];
            foreach ($infoes as $row) {
                if ($row['ESDET_ID'] !== null) {
                    $detallesPorId[(int)$row['ESDET_ID']] = $row;
                    $todosEsdetIds[] = (int)$row['ESDET_ID'];
                }
            }

            // Normaliza lista de seleccionados
            $seleccionados = array_map('intval', (array)$detalle);
            $seleccionados = array_values(array_unique($seleccionados));
            $setSel        = array_flip($seleccionados);

            // 3) ENTRADA: activa detalles (batch) + inserta stock por cada seleccionado
            $detConCadMenor1Anio = []; // [{esdetid, stockid}]
            if ($tipo === "E" && !empty($seleccionados)) {
                // 3.1 batch update ESDET_ACTIVO = 1
                $ph = implode(',', array_fill(0, count($seleccionados), '?'));
                $db->execute("UPDATE AMPAR_HIS_ESDET SET ESDET_ACTIVO = 1 WHERE ESDET_ID IN ($ph)", $seleccionados);
            
                // 3.2 inserta en STOCK por cada ESDET seleccionado
                $sqlInsStock = "
                    INSERT INTO AMPAR_HIS_STOCK
                    (STOCK_FECHA, STOCK_FOLIO, STOCK_STOCKSTATUSID, STOCK_ENTRADAID,
                    STOCK_ESDETID, STOCK_ALMACENIDACTUAL, STOCK_ENTRADAFECHA,
                    STOCK_ARTICULOID, STOCK_LOTE, STOCK_CADUCIDAD, STOCK_CADUCIDADMENOR1ANIO, STOCK_SERIE)
                    SELECT
                        CURRENT_TIMESTAMP,
                        (
                            SELECT 'A' ||
                                LPAD(COALESCE(MAX(CAST(SUBSTRING(STOCK_FOLIO FROM 2 FOR 5) AS INTEGER)),0)+1, 5, '0')
                                || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE)-2000 AS VARCHAR(2))
                            FROM AMPAR_HIS_STOCK
                        ),
                        1,
                        e.ESDET_ESID,
                        e.ESDET_ID,
                        ?,              -- STOCK_ALMACENIDACTUAL
                        CURRENT_TIMESTAMP,
                        e.ESDET_ARTICULOID,
                        e.ESDET_LOTE,
                        e.ESDET_CADUCIDAD,
                        ?,              -- STOCK_CADUCIDADMENOR1ANIO (nuevo parámetro calculado)
                        e.ESDET_SERIE
                    FROM AMPAR_HIS_ESDET e
                    WHERE e.ESDET_ID = ?
                ";
            
                foreach ($seleccionados as $esdetId) {
                    if (!isset($detallesPorId[$esdetId])) continue;
            
                    $caducidadStr = $detallesPorId[$esdetId]['ESDET_CADUCIDAD'] ?? null;
                    $cadMenor1Anio = 0;
            
                    if (!empty($caducidadStr)) {
                        try {
                            $caducidad = new DateTime($caducidadStr);
                            $hoy = new DateTime();
                            $unAnioDespues = (clone $hoy)->modify('+1 year');
            
                            if ($caducidad <= $unAnioDespues) {
                                $cadMenor1Anio = 1;
                            }
                        } catch (Exception $e) {
                            // Valor por defecto es 0
                        }
                    }
            
                    // Insert + returning
                    $stockId = $db->executeconreturning($sqlInsStock, 'STOCK_ID', [$almacenid, $cadMenor1Anio, $esdetId]);
                    $stockIdsPorEsdet[$esdetId] = $stockId;

                    // Vincular Carta Canje con el nuevo STOCK_ID
                    try {
                        $db->execute("UPDATE AMPAR_CARTA_CANJE SET CARTACANJE_STOCKID = ? WHERE CARTACANJE_ESDETID = ?", [$stockId, $esdetId]);
                    } catch (Exception $ex) {
                        // Ignorar si la tabla no ha sido creada o no existe carta canje
                    }

                    // Obtener info del stock insertado para bitácora
                    $qStockInfo = "
                        SELECT
                            ST.STOCK_ID, ST.STOCK_FOLIO, ST.STOCK_FECHA, ST.STOCK_STOCKSTATUSID, STOCK_LOTE, STOCK_CADUCIDAD, STOCK_SERIE,
                            A.ALMACEN_ID, A.ALMACEN_NOMBRE,
                            SU.SUCURSAL_ID, SU.NOMBRE AS SUCURSAL_NOMBRE,
                            AR.ARTICULO_ID, AR.NOMBRE AS ARTICULO_NOMBRE
                        FROM AMPAR_HIS_STOCK ST
                        LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
                        LEFT JOIN ampar_cat_sucursales SU       ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                        LEFT JOIN ARTICULOS AR        ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                        WHERE ST.STOCK_ID = ?
                    ";
                    $stInfo = $db->query($qStockInfo, [$stockId]);
                    $stRow  = $stInfo && $stInfo !== 0 ? $stInfo[0] : ['STOCK_ID' => $stockId];
            
                    $stockInfoCache[$stockId] = $stRow;
            
                    // Bitácora: creación de artículo
                    $comentStock = 'CREACIÓN DE ARTÍCULO <b>'.$stRow['STOCK_FOLIO'].'</b> EN ALMACÉN <b>'.$stRow['ALMACEN_NOMBRE'].'</b>. Campos:'.
                                    bitacora::printarray($stRow);
                    $db->execute($sqlBit, [
                        $comentStock,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $stRow['SUCURSAL_ID'] ?? $hdr['ALMACEN_SUCURSAL_MS'],
                        $stRow['ALMACEN_ID'] ?? $almacenid,
                        $stockId
                    ]);
            
                    // Marcar para módulo de caducidad si aplica
                    if ($cadMenor1Anio === 1) {
                        $detConCadMenor1Anio[] = ['esdetid' => $esdetId, 'stockid' => $stockId];
                    }
                }
            }

            // 4) SALIDA / REPOSICIÓN: baja de seleccionados + liberar sobrantes
            if ($tipo === "S") {
                // 3.1 batch update ESDET_ACTIVO = 1
                $phaux = implode(',', array_fill(0, count($seleccionados), '?'));
                $db->execute("UPDATE AMPAR_HIS_ESDET SET ESDET_ACTIVO = 1 WHERE ESDET_ID IN ($phaux)", $seleccionados);
                // 4.1 dar de baja cada seleccionado (usa ESDET_STOCKIDSALIDA)
                $sqlBaja = "
                    UPDATE AMPAR_HIS_STOCK
                    SET STOCK_STOCKSTATUSID = 3,
                        STOCK_SALIDAID      = (SELECT ESDET_ESID FROM AMPAR_HIS_ESDET WHERE ESDET_ID = ?),
                        STOCK_SALIDAFECHA   = CURRENT_TIMESTAMP
                    WHERE STOCK_ID = (SELECT ESDET_STOCKIDSALIDA FROM AMPAR_HIS_ESDET WHERE ESDET_ID = ?)
                ";
                
                foreach ($seleccionados as $esdetId) {
                    if (!isset($detallesPorId[$esdetId])) continue;
                    $db->execute($sqlBaja, [$esdetId, $esdetId]);

                    // Info para bitácora de baja
                    $rowStockId = $db->query("SELECT ESDET_STOCKIDSALIDA AS SID FROM AMPAR_HIS_ESDET WHERE ESDET_ID = ?", [$esdetId]);
                    $sid        = ($rowStockId && $rowStockId !== 0) ? $rowStockId[0]['SID'] : null;
                    if ($sid) {
                        $qStockInfo = "
                            SELECT
                                ST.STOCK_ID, ST.STOCK_FOLIO, ST.STOCK_FECHA, ST.STOCK_STOCKSTATUSID,
                                A.ALMACEN_ID, A.ALMACEN_NOMBRE,
                                SU.SUCURSAL_ID, SU.NOMBRE AS SUCURSAL_NOMBRE,
                                AR.ARTICULO_ID, AR.NOMBRE AS ARTICULO_NOMBRE
                            FROM AMPAR_HIS_STOCK ST
                            LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
                            LEFT JOIN ampar_cat_sucursales SU       ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                            LEFT JOIN ARTICULOS AR        ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                            WHERE ST.STOCK_ID = ?
                        ";
                        $stInfo = $db->query($qStockInfo, [$sid]);
                        $stRow  = $stInfo && $stInfo !== 0 ? $stInfo[0] : ['STOCK_ID' => $sid];

                        $comentBaja = 'BAJA DE ARTÍCULO <b>'.$stRow['STOCK_FOLIO'].'</b> EN ALMACÉN <b>'.$stRow['ALMACEN_NOMBRE'].'</b>. Campos:'
                                    . bitacora::printarray($stRow);
                        $db->execute($sqlBit, [
                            $comentBaja,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $stRow['SUCURSAL_ID'] ?? $hdr['ALMACEN_SUCURSAL_MS'],
                            $stRow['ALMACEN_ID'] ?? $almacenid,
                            $sid
                        ]);
                    }
                }

                // 4.2 liberar sobrantes = stock apartado (2) que NO está en $seleccionados
                // Arma IN (?) dinámico para excluir seleccionados
                $paramsSob = [$esid];
                $condNotIn = '';
                if (!empty($seleccionados)) {
                    $phEx = implode(',', array_fill(0, count($seleccionados), '?'));
                    $condNotIn = " AND ED.ESDET_ID NOT IN ($phEx) ";
                    $paramsSob = array_merge($paramsSob, $seleccionados);
                }

                // Extraemos los IDs de detalle seleccionados
                $idsDetalle = array_column($detalle, 'ESDET_ID');

                // Corregido: usar directamente los IDs seleccionados
                $sobrantes = array_filter($infoes, function($row) use ($seleccionados) {
                    return !in_array((int)$row['ESDET_ID'], $seleccionados);
                });

                if ($sobrantes && $sobrantes !== 0) {
                    foreach ($sobrantes as $ar) {
                        $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = ?", [$ar['STOCK_ID']]);

                        $comentLib = 'SE LIBERÓ ARTÍCULO <b>'.$ar['STOCK_FOLIO'].'</b> EN ALMACÉN <b>'.$ar['ALMACEN_NOMBRE'].'</b>. Campos:'
                                . bitacora::printarray($ar);
                        $db->execute($sqlBit, [
                            $comentLib,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $ar['SUCURSAL_ID'],
                            $ar['ALMACEN_ID'],
                            $ar['STOCK_ID']
                        ]);
                    }
                }
            }

            // 5) CADUCIDAD < 1 AÑO (solo entradas)
            if (!empty($detConCadMenor1Anio)) {
                // cabecera
                $sqlCadCab = "
                    INSERT INTO AMPAR_CADUCIDADPROV
                    (CADUCIDADPROV_PROVID, CADUCIDADPROV_PROVCORREO, CADUCIDADPROV_FECHA,
                    CADUCIDADPROV_USUARIOID, CADUCIDADPROV_FOLIO, CADUCIDADPROV_STATUS, CADUCIDADPROV_ALMACENID)
                    VALUES
                    (?, ?, CURRENT_TIMESTAMP, ?,
                    (SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(CADUCIDADPROV_FOLIO FROM 1 FOR 5) AS INTEGER)),0)+1, 5, '0')
                            || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE)-2000 AS VARCHAR(2))
                    FROM AMPAR_CADUCIDADPROV),
                    9, ?)
                ";
                $cadProvId = $db->executeconreturning($sqlCadCab, 'CADUCIDADPROV_ID', [
                    $hdr['ES_IDPROVEEDOR'],
                    $hdr['ES_CORREOPROVEEDOR'],
                    $usersesion['USUARIO_ID'],
                    $hdr['ES_ALMACENID']
                ]);

                // Detalle + bitácora por cada stock
                $sqlCadDet = "
                    INSERT INTO AMPAR_CADUCIDADPROVDET
                    (CADUCIDADPROVDET_CADPROVID, CADUCIDADPROVDET_STOCKID, CADUCIDADPROVDET_REEMPLAZADO)
                    VALUES (?, ?, 0)
                ";
                // Bitácora: cabecera de caducidad (opcionalmente recuperamos folio)
                $cadHdrInfo = $db->query("SELECT * FROM AMPAR_CADUCIDADPROV WHERE CADUCIDADPROV_ID = ?", [$cadProvId]);
                $comentCadCab = 'CREACIÓN AVISO CADUCIDAD <b>'.($cadHdrInfo[0]['CADUCIDADPROV_FOLIO'] ?? $cadProvId).'</b>. Campos:'
                            . bitacora::printarray($cadHdrInfo ? $cadHdrInfo[0] : ['CADUCIDADPROV_ID' => $cadProvId]);
                $db->execute($sqlBit, [
                    $comentCadCab,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser,
                    $hdr['ALMACEN_SUCURSAL_MS'],
                    $hdr['ES_ALMACENID'],
                    null
                ]);

                foreach ($detConCadMenor1Anio as $d) {
                    $db->execute($sqlCadDet, [$cadProvId, $d['stockid']]);

                    // Bitácora detalle caducidad (usamos cache del stock)
                    $stRow = $stockInfoCache[$d['stockid']] ?? ['STOCK_ID' => $d['stockid']];
                    $comentCadDet = 'CADUCIDAD (< 1 año) ASOCIADA AL ARTÍCULO <b>'.($stRow['STOCK_FOLIO'] ?? $d['stockid']).'</b>. Campos:'
                                . bitacora::printarray($stRow);
                    $db->execute($sqlBit, [
                        $comentCadDet,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $stRow['SUCURSAL_ID'] ?? $hdr['ALMACEN_SUCURSAL_MS'],
                        $stRow['ALMACEN_ID'] ?? $hdr['ES_ALMACENID'],
                        $d['stockid']
                    ]);
                }

                //Se envía correo a Proveedor con la Lista de Artículos
                if ($infoes[0]['ES_CORREOPROVEEDOR'] <> ""){
                    $mensaje = '
                        <html>
                        <head>
                        <style>
                            body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
                            h2 { color: #444; }
                            table { border-collapse: collapse; width: 100%; margin-top: 10px; }
                            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                            th { background-color: #f4f4f4; }
                            .nota { margin-top: 15px; font-size: 13px; color: #555; }
                        </style>
                        </head>
                        <body>
                        <h2>Notificación de caducidad menor a un año</h2>
                        <p>Estimado <b>'.$infoes[0]['NOMBREPROVEEDOR'].'</b>,</p>
                        <p>
                            Durante la recepción de los artículos en almacén se detectó que algunos cuentan con una
                            <b>fecha de caducidad menor a un año</b>. 
                            Por este motivo se le notifica que, en caso de que estos no sean utilizados un mes antes 
                            de la fecha de caducidad, serán <b>devueltos y reemplazados</b> por artículos de la misma 
                            referencia o por su referencia homóloga.
                        </p>

                        <table>
                            <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Lote</th>
                                <th>Fecha de caducidad</th>
                                <th>Fecha estimada de retorno</th>
                            </tr>
                            </thead>
                            <tbody>';
                            foreach ($detConCadMenor1Anio as $a) {
                                $stRow = $stockInfoCache[$d['stockid']] ?? ['STOCK_ID' => $d['stockid']];
                                // Fecha estimada de retorno: 1 mes antes de la caducidad
                                $fechaCad = new DateTime($stRow["STOCK_CADUCIDAD"]);
                                $fechaRetorno = clone $fechaCad;
                                $fechaRetorno->modify("-1 month");

                                $mensaje .= '
                                <tr>
                                    <td>'.$stRow["ARTICULO_NOMBRE"].'</td>
                                    <td>'.$stRow["STOCK_LOTE"].'</td>
                                    <td>'.date("d/m/Y", strtotime($stRow["STOCK_CADUCIDAD"])).'</td>
                                    <td>'.$fechaRetorno->format("d/m/Y").'</td>
                                </tr>';
                            }
                        $mensaje .= '
                            </tbody>
                        </table>

                        <p class="nota">
                            Agradecemos su atención a esta notificación y quedamos atentos a cualquier aclaración.<br>
                            <br>
                            Atentamente,<br>
                            <b>Departamento de Almacén</b>
                        </p>
                        </body>
                        </html>
                    ';
                    //Enviar correo de cuestionario autodiagnóstico
                    $mail = new mail();
                    $mail->enviar($infoes[0]['ES_CORREOPROVEEDOR'],$infoes[0]['NOMBREPROVEEDOR'],'Garantía de Proveedor',$mensaje);
                }
            }

            $db->commit();
            $db->close();

        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            throw new Exception("Error en autorización de entrada/salida: ".$e->getMessage());
        }
    }

    function OLD_autorizarentradasalida($esid, $tipo, $almacenid, $detalle) {
        $detallecaducidadmenos1anio = array();
        $usersesion = $_SESSION['ampar']['usuario'];
        $db = new FirebirdConnection();
        $ipuser = $_SERVER['REMOTE_ADDR'];

        //OBTENER INFORMACION DE TODA LA ENTRA Y SALIDA
        $sql1 = "
            SELECT ES_ID, ES_FOLIO, ES_TIPO, ES_ALMACENID, ALMACEN_SUCURSAL_MS, ALMACEN_NOMBRE,
                SU.NOMBRE SUCURSAL_NOMBRE, STATUS_NOMBRE, ES_IDPROVEEDOR, ES_CORREOPROVEEDOR,
                PR.NOMBRE NOMBREPROVEEDOR, ED.*, ST.*
            FROM AMPAR_HIS_ES E
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = ES_STATUS
            LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ES_ALMACENID
            LEFT JOIN ampar_cat_sucursales SU ON SUCURSAL_ID = ALMACEN_SUCURSAL_MS 
            LEFT JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = ES_IDPROVEEDOR
            LEFT JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ESID = E.ES_ID 
            LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = ED.ESDET_STOCKIDSALIDA 
            WHERE ES_ID = ?
        ";
        $infoes = $db->query($sql1, [$esid]);

        //MODIFICA EL STATUS A FINALIZADO
        $sql0 = "UPDATE AMPAR_HIS_ES SET ES_STATUS = 3 WHERE ES_ID = ?";
        $db->execute($sql0, [$esid]);

        

        //GUARDA EN BITACORA CABECERA
        $comentarioCab = "CAMBIO DE STATUS A <strong>".$infoes[0]['STATUS_NOMBRE']."</strong> DE SOLICITUD DE "
            . (($infoes[0]['ES_TIPO'] == 'E') ? 'ENTRADA' : 'SALIDA')
            . " FOLIO:<strong>".$infoes[0]['ES_FOLIO']."</strong> (".$infoes[0]['ALMACEN_NOMBRE'].")";

        $sqlBit = "INSERT INTO AMPAR_BITACORA 
            (BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
            BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_FECHA)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $db->execute($sqlBit, [
            $comentarioCab,
            $usersesion['USUARIO_ID'],
            $usersesion['USUARIO_CORREO'],
            $ipuser,
            $infoes[0]['ALMACEN_SUCURSAL_MS'],
            $infoes[0]['ES_ALMACENID']
        ]);

        // FOREACH DETALLE
        foreach ($detalle as $det) {
            // -------- ENTRADA --------
            if ($tipo == "E") {
                //ACTUALIZA DETALLE
                $sql2 = "UPDATE AMPAR_HIS_ESDET SET ESDET_ACTIVO = 1 WHERE ESDET_ID = ?";
                $db->execute($sql2, [$det]);

                //INSERTA STOCK
                $sql3 = "
                    INSERT INTO AMPAR_HIS_STOCK (
                STOCK_FECHA,
                STOCK_FOLIO,
                STOCK_STOCKSTATUSID,
                STOCK_ENTRADAID,
                STOCK_ESDETID,
                STOCK_ALMACENIDACTUAL,
                STOCK_ENTRADAFECHA,
                STOCK_ARTICULOID,
                STOCK_LOTE,
                STOCK_CADUCIDAD,
                STOCK_CADUCIDADMENOR1ANIO,
                STOCK_SERIE
            )
            SELECT
                CURRENT_TIMESTAMP,
                (
                    'A' || LPAD(
                        COALESCE(MAX(CAST(SUBSTRING(STOCK_FOLIO FROM 2 FOR 5) AS INTEGER)), 0) + 1,
                        5,
                        '0'
                    ) || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                ) AS nuevo_folio,
                1,
                e.ESDET_ESID,
                e.ESDET_ID,
                ?,                     -- STOCK_ALMACENIDACTUAL (lo pasas como parámetro)
                CURRENT_TIMESTAMP,
                e.ESDET_ARTICULOID,
                e.ESDET_LOTE,
                e.ESDET_CADUCIDAD,
                e.ESDET_CADUCIDADMENOR1ANIO,
                e.ESDET_SERIE
            FROM AMPAR_HIS_ESDET e
            LEFT JOIN AMPAR_HIS_STOCK s ON 1=1
            WHERE e.ESDET_ID = ?
                ";
                $idStock = $db->executeconreturning($sql3, 'STOCK_ID', [$det, $det, $almacenid, $det, $det, $det, $det, $det]);

                //VERIFICA CADUCIDAD
                $resconsulta = $db->query("SELECT ESDET_CADUCIDADMENOR1ANIO FROM AMPAR_HIS_ESDET WHERE ESDET_ID = ?", [$det]);
                if ($resconsulta[0]['ESDET_CADUCIDADMENOR1ANIO'] == 1) {
                    $detallecaducidadmenos1anio[] = ['esdetid' => $det, 'stockid' => $idStock];
                }

                //GUARDA EN BITACORA STOCK
                $comentarioStock = "CREACIÓN DE ARTÍCULO ID ".$idStock." EN ENTRADA ".$esid;
                $sqlBit2 = "INSERT INTO AMPAR_BITACORA 
                    (BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO,
                    BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_FECHA)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                $db->execute($sqlBit2, [
                    $comentarioStock,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser,
                    $infoes[0]['ALMACEN_SUCURSAL_MS'],
                    $almacenid,
                    $idStock
                ]);
            }

            // -------- SALIDA --------
            if ($tipo == "S") {
                //ACTUALIZA STOCK BAJA
                $sql6 = "
                    UPDATE AMPAR_HIS_STOCK
                    SET STOCK_STOCKSTATUSID = 3,
                        STOCK_SALIDAID = (SELECT ESDET_ESID FROM AMPAR_HIS_ESDET WHERE ESDET_ID=?),
                        STOCK_SALIDAFECHA = CURRENT_TIMESTAMP
                    WHERE STOCK_ID = (SELECT ESDET_STOCKIDSALIDA FROM AMPAR_HIS_ESDET WHERE ESDET_ID=?)
                ";
                $db->execute($sql6, [$det, $det]);

                //OBTENER STOCKID SALIDA
                $rowStock = $db->query("SELECT ESDET_STOCKIDSALIDA FROM AMPAR_HIS_ESDET WHERE ESDET_ID=?", [$det]);
                $stockSalidaId = $rowStock[0]['ESDET_STOCKIDSALIDA'];

                //BITACORA SALIDA
                $comentarioSalida = "BAJA DE ARTÍCULO STOCK ".$stockSalidaId." EN SALIDA ".$esid;
                $sqlBit3 = "INSERT INTO AMPAR_BITACORA 
                    (BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO,
                    BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_FECHA)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                $db->execute($sqlBit3, [
                    $comentarioSalida,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser,
                    $infoes[0]['ALMACEN_SUCURSAL_MS'],
                    $almacenid,
                    $stockSalidaId
                ]);

                //LIBERAR SOBRANTES
                $sql8 = "
                    SELECT STOCK_ID, STOCK_FOLIO, AR.NOMBRE ARTICULO_NOMBRE, ALMACEN_ID, ALMACEN_NOMBRE, SUCURSAL_ID
                    FROM AMPAR_HIS_ESDET 
                    LEFT JOIN AMPAR_HIS_STOCK ON STOCK_ID = ESDET_STOCKIDSALIDA 
                    LEFT JOIN AMPAR_HIS_ALMACEN AL ON ALMACEN_ID = STOCK_ALMACENIDACTUAL
                    LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = AL.ALMACEN_SUCURSAL_MS
                    LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
                    WHERE ESDET_ESID = ? AND STOCK_STOCKSTATUSID = 2
                ";
                $articulossobrantes = $db->query($sql8, [$esid]);
                if (!empty($articulossobrantes)) {
                    foreach ($articulossobrantes as $ar) {
                        $sql9 = "UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = ?";
                        $db->execute($sql9, [$ar['STOCK_ID']]);

                        $comentarioLib = "SE LIBERÓ ARTÍCULO ".$ar['STOCK_FOLIO']." EN ALMACÉN ".$ar['ALMACEN_NOMBRE'];
                        $sqlBit4 = "INSERT INTO AMPAR_BITACORA 
                            (BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO,
                            BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_FECHA)
                            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                        $db->execute($sqlBit4, [
                            $comentarioLib,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $ar['SUCURSAL_ID'],
                            $ar['ALMACEN_ID'],
                            $ar['STOCK_ID']
                        ]);
                    }
                }
            }
        }

        //CADUCIDAD < 1 AÑO
        if (!empty($detallecaducidadmenos1anio)) {
            $sql10 = "
                INSERT INTO AMPAR_CADUCIDADPROV
                (CADUCIDADPROV_PROVID, CADUCIDADPROV_PROVCORREO, CADUCIDADPROV_FECHA,
                CADUCIDADPROV_USUARIOID, CADUCIDADPROV_FOLIO, CADUCIDADPROV_STATUS, CADUCIDADPROV_ALMACENID)
                VALUES
                (?, ?, CURRENT_TIMESTAMP, ?, 
                    (SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(CADUCIDADPROV_FOLIO FROM 1 FOR 5) AS INTEGER)), 0)+1, 5,'0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE)-2000 AS VARCHAR(2)) FROM AMPAR_CADUCIDADPROV),
                9, ?)
            ";
            $idCadProv = $db->executeconreturning($sql10, 'CADUCIDADPROV_ID', [
                $infoes[0]['ES_IDPROVEEDOR'],
                $infoes[0]['ES_CORREOPROVEEDOR'],
                $usersesion['USUARIO_ID'],
                $infoes[0]['ES_ALMACENID']
            ]);

            foreach ($detallecaducidadmenos1anio as $dcm1aa) {
                $sql11 = "INSERT INTO AMPAR_CADUCIDADPROVDET
                        (CADUCIDADPROVDET_CADPROVID, CADUCIDADPROVDET_STOCKID, CADUCIDADPROVDET_REEMPLAZADO)
                        VALUES (?, ?, 0)";
                $db->execute($sql11, [$idCadProv, $dcm1aa['stockid']]);
            }
        }

        $db->close();
    }

    function updatestatusentradasalida($id, $status, $motivo_rechazo = null){
        $db = new FirebirdConnection();
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip(); // IP del usuario

        try {
            $db->beginTransaction();

            // --- Actualizar cabecera ---
            if (($status == 5 || $status == 6) && !empty($motivo_rechazo)) {
                $db->execute("UPDATE AMPAR_HIS_ES SET ES_STATUS = ?, ES_MOTIVO_RECHAZO = ? WHERE ES_ID = ?", [$status, $motivo_rechazo, $id]);
            } else {
                $db->execute("UPDATE AMPAR_HIS_ES SET ES_STATUS = ? WHERE ES_ID = ?", [$status, $id]);
            }

            // --- Traer info de cabecera y detalles en un solo query ---
            $sql = "
                SELECT 
                    ES.*,
                    A.ALMACEN_SUCURSAL_MS, A.ALMACEN_NOMBRE,
                    SU.NOMBRE AS SUCURSAL_NOMBRE,
                    S.STATUS_NOMBRE,
                    ED.ESDET_STOCKIDSALIDA, ST.STOCK_ID, ST.STOCK_FOLIO,
                    AR.NOMBRE AS ARTICULO_NOMBRE,
                    CI.CONCEPTOSAL_NOMBRE AS CONCEPTO_NOMBRE
                FROM AMPAR_HIS_ES ES
                LEFT JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ESID = ES.ES_ID
                LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = ED.ESDET_STOCKIDSALIDA
                LEFT JOIN AMPAR_CONF_CONCEPTOSAL CI ON CI.CONCEPTOSAL_ID = ES.ES_CONCEPTOID
                LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ED.ESDET_ARTICULOID
                LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ES.ES_ALMACENID
                LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = ES.ES_STATUS
                WHERE ES.ES_ID = ?
            ";
            $info = $db->query($sql, [$id]);
            if (!$info) throw new Exception("No se encontró la solicitud");

            $cabecera = $info[0];

            // --- Insert bitácora cabecera ---
            $comentarioCab = 'CAMBIO DE STATUS A <strong>'.$cabecera['STATUS_NOMBRE'].'</strong> DE SOLICITUD DE '
                . (($cabecera['ES_TIPO'] == 'E') ? 'ENTRADA' : 'SALIDA')
                . ' FOLIO:<strong>' . $cabecera['ES_FOLIO'] . '</strong> (' . $cabecera['ALMACEN_NOMBRE'] . ') Campos:' 
                . bitacora::printarray($cabecera);

            $sqlBit = "
                INSERT INTO AMPAR_BITACORA
                    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID)
                VALUES
                    (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?)
            ";
            $db->execute($sqlBit, [
                $comentarioCab,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $cabecera['ALMACEN_SUCURSAL_MS'] ?? null,
                $cabecera['ES_ALMACENID'] ?? null
            ]);

            // --- Si se cancela o rechaza, liberar stock y bitácora de detalle ---
            if ($status == 5 || $status == 6) {
                foreach ($info as $detalle) {
                    if (!empty($detalle['ESDET_STOCKIDSALIDA'])) {
                        // Actualizar stock
                        $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = ?", [$detalle['ESDET_STOCKIDSALIDA']]);

                        $sqlBitStock = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO,
                        BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_FECHA)
                        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

                        // Insert bitácora detalle
                        $comentarioStock = 'SE LIBERÓ ARTÍCULO <b>'.$detalle['STOCK_FOLIO'].'</b> EN ALMACÉN <b>'.$detalle['ALMACEN_NOMBRE'].'</b>. Campos:' 
                            . bitacora::printarray($detalle);

                        $db->execute($sqlBitStock, [
                            $comentarioStock,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $detalle['ALMACEN_SUCURSAL_MS'] ?? null,
                            $detalle['ES_ALMACENID'] ?? null,
                            $detalle['ESDET_STOCKIDSALIDA']
                        ]);
                    }
                }
            }

            $db->commit();

            // 📱 Notificación WhatsApp a Admins cuando pasa a revisión (status 8)
            if ((int)$status === 8) {
                try {
                    $telAdmins = whatsapp::getTelefonosAdmins();
                    if (!empty($telAdmins)) {
                        $tipo_str = ($cabecera['ES_TIPO'] == 'E') ? 'Entrada' : 'Salida';
                        $msgWA  = "📋 *{$tipo_str} en Revisión*\n";
                        $msgWA .= "Folio: *{$cabecera['ES_FOLIO']}*\n";
                        $msgWA .= "Almacén: " . ($cabecera['ALMACEN_NOMBRE'] ?? '') . "\n";
                        $msgWA .= "Por favor revisa y autoriza en el sistema.";
                        whatsapp::enviarMultiple($telAdmins, $msgWA);
                    }
                } catch (Throwable $eWA) {
                    error_log("[WhatsApp] Error en notificación ES: " . $eWA->getMessage());
                }
            }

            if (($status == 5 || $status == 6) && !empty($cabecera['ES_USUARIOID'])) {
                $accionStr = ($status == 6) ? "Rechazada" : "Cancelada";
                $ocFolioStr = "";
                if (!empty($cabecera['ES_MOTIVO']) && preg_match('/OC\s*Folio:\s*(\d+)/i', $cabecera['ES_MOTIVO'], $m)) {
                    $ocId = $m[1];
                    try {
                        $resOc = $db->query("SELECT OC_FOLIO FROM AMPAR_OC WHERE OC_ID = ?", [$ocId]);
                        if (!empty($resOc) && !empty($resOc[0]['OC_FOLIO'])) {
                            $ocFolioStr = " | OC: " . $resOc[0]['OC_FOLIO'];
                        } else {
                            $ocFolioStr = " | OC: " . $ocId;
                        }
                    } catch (Throwable $e) {}
                }
                $tituloNotif = "Entrada " . $accionStr . ": " . $cabecera['ES_FOLIO'] . $ocFolioStr;
                $msgNotif = !empty($motivo_rechazo) ? "Motivo: " . $motivo_rechazo : $accionStr . " en inspección de almacén.";
                try {
                    if (!class_exists('notificaciones')) { @include_once(__DIR__ . '/notificaciones.php'); }
                    if (class_exists('notificaciones')) {
                        $tipoNotificacion = 'ENTRADA';
                        $eventoidNotificacion = $id;

                        // Si es rechazo de Recepcion OC, buscamos el ID de Recepcion
                        if ($status == 6 && !empty($ocId)) {
                            try {
                                $resRec = $db->query("SELECT FIRST 1 RECEPCION_ID FROM AMPAR_RECEPCION WHERE RECEPCION_OCID = ? ORDER BY RECEPCION_ID DESC", [$ocId]);
                                if (!empty($resRec) && !empty($resRec[0]['RECEPCION_ID'])) {
                                    $tipoNotificacion = 'RECEPCION_RECHAZADA';
                                    $eventoidNotificacion = $resRec[0]['RECEPCION_ID'];
                                }
                            } catch (Throwable $e) {}
                        }

                        notificaciones::crear($cabecera['ES_USUARIOID'], $eventoidNotificacion, $tipoNotificacion, $tituloNotif, $msgNotif);
                    }
                } catch (Throwable $e) {}

                try {
                    $resUser = $db->query("SELECT USUARIO_CORREO, USUARIO_NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?", [$cabecera['ES_USUARIOID']]);
                    if (!empty($resUser)) {
                        $userMail = $resUser[0]['USUARIO_CORREO'] ?? '';
                        $userName = $resUser[0]['USUARIO_NOMBRE'] ?? '';
                        
                        if (!empty($userMail)) {
                            if (!class_exists('email')) { @include_once(__DIR__ . '/email.php'); }
                            $emailClass = new email();
                            
                            $msgHtml = "
                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                <h2 style='color: #dc3545;'>Solicitud de Entrada " . $accionStr . "</h2>
                                <p>Hola <strong>{$userName}</strong>,</p>
                                <p>Te informamos que tu solicitud de entrada ha sido <strong>" . strtolower($accionStr) . "</strong>.</p>
                                <ul>
                                    <li><strong>Folio de Entrada:</strong> {$cabecera['ES_FOLIO']}</li>
                                    " . ($ocFolioStr ? "<li><strong>Orden de Compra:</strong> " . trim(str_replace('| OC:', '', $ocFolioStr)) . "</li>" : "") . "
                                </ul>
                                <p><strong>Motivo del " . ($status == 6 ? "rechazo" : "cancelación") . ":</strong><br>
                                <div style='background: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545; white-space: pre-line;'>" . htmlspecialchars($msgNotif) . "</div></p>
                                <br>
                                " . ($status == 6 ? "<p><em>Puedes ingresar al sistema para corregir los documentos y reenviar la solicitud.</em></p>" : "") . "
                                <p>Saludos cordiales,<br>El equipo de AMPAR</p>
                            </div>";
                            
                            $emailClass->sendEmail($userMail, "Solicitud de Entrada " . $accionStr . " - " . $cabecera['ES_FOLIO'], $msgHtml);
                        }
                    }
                } catch (Throwable $e) {}
            }
            
            return "";

        } catch (Exception $e) {
            $db->rollBack();
            return $e->getMessage();
        }
    }

}
?>