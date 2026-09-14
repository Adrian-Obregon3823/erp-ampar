<?php

class recepcionesmercancia {

    // Guarda una recepción y sus detalles
    function guardarRecepcion($ocid, $usuarioid, $observaciones, $detalles, $almacenid, $archivos = [], $num_delivery_general = '') {
        $db = new FirebirdConnection();
        try {
            $db->beginTransaction();

            // 1. Insertar Cabecera de Recepción
            $sqlRecep = "
                INSERT INTO AMPAR_RECEPCION (RECEPCION_OCID, RECEPCION_USUARIOID, RECEPCION_FECHA, RECEPCION_OBSERVACIONES, RECEPCION_STATUS)
                VALUES (?, ?, CURRENT_TIMESTAMP, ?, 1)
            ";
            $idRecepcion = $db->executeconreturning($sqlRecep, 'RECEPCION_ID', [$ocid, $usuarioid, $observaciones]);

            if (!$idRecepcion) {
                throw new Exception("No se pudo generar el ID de la Recepción.");
            }

            // 2. Insertar Cabecera de Entrada (si se recibe al menos 1 articulo)
            $total_general_recibido = 0;
            foreach ($detalles as $det) {
                $total_general_recibido += isset($det['recibida']) ? (float)$det['recibida'] : 0;
            }

            $idEntrada = null;
            if ($total_general_recibido > 0) {
                // Obtener Proveedor de la OC
                $sqlProv = "SELECT OC_PROVEEDORID FROM AMPAR_OC WHERE OC_ID = ?";
                $resProv = $db->query($sqlProv, [$ocid]);
                $idProveedor = $resProv[0]['OC_PROVEEDORID'] ?? null;

                $sqlEntrada = "
                    INSERT INTO AMPAR_HIS_ES (ES_FECHA, ES_FOLIO, ES_ALMACENID, ES_CONCEPTOID, ES_MOTIVO, ES_STATUS, ES_TIPO, ES_USUARIOID, ES_IDPROVEEDOR)
                    VALUES (
                        CURRENT_TIMESTAMP, 
                        (SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(ES_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2)) FROM AMPAR_HIS_ES), 
                        ?, 1, ?, 1, 'E', ?, ?
                    )
                ";
                $motivo_entrada = "Recepcion de OC Folio: " . $ocid;
                $idEntrada = $db->executeconreturning($sqlEntrada, 'ES_ID', [$almacenid, $motivo_entrada, $usuarioid, $idProveedor]);
            }
            $folioCarpeta = 'REC-' . $idRecepcion;
            $dirUploads = __DIR__ . '/../uploads/recepciones/' . $folioCarpeta;
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }

            $ruta_factura = null;
            if (!empty($archivos['archivo_factura_general']['name']) && $archivos['archivo_factura_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_factura_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'FAC_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_factura_general']['tmp_name'], $rutaDestino)) {
                        $ruta_factura = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_doc2 = null;
            if (!empty($archivos['archivo_documento2_general']['name']) && $archivos['archivo_documento2_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_documento2_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'DOC2_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_documento2_general']['tmp_name'], $rutaDestino)) {
                        $ruta_doc2 = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_evidencia = null;
            if (!empty($archivos['archivo_evidencia_general']['name']) && $archivos['archivo_evidencia_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_evidencia_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EVI_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_evidencia_general']['tmp_name'], $rutaDestino)) {
                        $ruta_evidencia = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_lista_embarque = null;
            if (!empty($archivos['archivo_lista_embarque_general']['name']) && $archivos['archivo_lista_embarque_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_lista_embarque_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EMB_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_lista_embarque_general']['tmp_name'], $rutaDestino)) {
                        $ruta_lista_embarque = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_carta_canje = null;
            if (!empty($archivos['archivo_carta_canje_general']['name']) && $archivos['archivo_carta_canje_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_carta_canje_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'CC_GEN_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_carta_canje_general']['tmp_name'], $rutaDestino)) {
                        $ruta_carta_canje = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($ruta_factura) || !empty($ruta_doc2) || !empty($ruta_evidencia) || !empty($ruta_lista_embarque) || !empty($ruta_carta_canje) || !empty($num_delivery_general)) {
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_FACTURA VARCHAR(255)"); } catch (Throwable $e) {}
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_DOCUMENTO2 VARCHAR(255)"); } catch (Throwable $e) {}
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_EVIDENCIA VARCHAR(255)"); } catch (Throwable $e) {}
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_LISTA_EMBARQUE VARCHAR(255)"); } catch (Throwable $e) {}
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_CARTA_CANJE VARCHAR(255)"); } catch (Throwable $e) {}
                try { $db->execute("ALTER TABLE AMPAR_RECEPCION ADD RECEPCION_DELIVERY_NUM VARCHAR(255)"); } catch (Throwable $e) {}
                $db->execute("UPDATE AMPAR_RECEPCION SET RECEPCION_FACTURA = ?, RECEPCION_DOCUMENTO2 = ?, RECEPCION_EVIDENCIA = ?, RECEPCION_LISTA_EMBARQUE = ?, RECEPCION_CARTA_CANJE = ?, RECEPCION_DELIVERY_NUM = ? WHERE RECEPCION_ID = ?", [$ruta_factura, $ruta_doc2, $ruta_evidencia, $ruta_lista_embarque, $ruta_carta_canje, $num_delivery_general, $idRecepcion]);
            }

            // 3. Insertar Detalles y Actualizar Stock/Entradas
            // Con el nuevo flujo de lotes, cada detalle tiene un campo 'cantidad' que indica cuántas unidades tiene ese lote/serie
            $articulos_agrupados = [];
            foreach ($detalles as $idx => $det) {
                // Ignorar filas donde el checkbox no fue marcado
                if (empty($det['recibida'])) {
                    continue;
                }

                $art_id = $det['articuloid'];
                $cant_det = isset($det['cantidad']) ? (int)$det['cantidad'] : 1;
                if ($cant_det < 1) $cant_det = 1;

                if (!isset($articulos_agrupados[$art_id])) {
                    $articulos_agrupados[$art_id] = [
                        'esperada'  => (float)($det['esperada'] ?? 1),
                        'recibida'  => 0,
                        'rechazada' => 0,
                        'motivo'    => $det['motivo'] ?? '',
                        'costo'     => (float)($det['costo'] ?? 0),
                        'unidades'  => [] // para guardar lote/serie/caducidad con su cantidad
                    ];
                }
                $articulos_agrupados[$art_id]['recibida'] += $cant_det;
                $articulos_agrupados[$art_id]['unidades'][] = [
                    'lote'      => $det['lote'] ?? null,
                    'serie'     => $det['serie'] ?? null,
                    'caducidad' => $det['caducidad'] ?? null,
                    'cantidad'  => $cant_det
                ];
            }

            foreach ($articulos_agrupados as $art_id => $grupo) {
                $sqlDet = "
                    INSERT INTO AMPAR_RECEPCIONDET (RECDET_RECEPCIONID, RECDET_ARTICULOID, RECDET_CANTIDAD_ESPERADA, RECDET_CANTIDAD_RECIBIDA, RECDET_CANTIDAD_RECHAZADA, RECDET_MOTIVO_RECHAZO, RECDET_COSTO_UNITARIO)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";
                $db->execute($sqlDet, [
                    $idRecepcion,
                    $art_id,
                    $grupo['esperada'],
                    $grupo['recibida'],
                    $grupo['rechazada'],
                    $grupo['motivo'],
                    $grupo['costo']
                ]);

                // Crear entradas individuales (ESDET) por cada unidad de cada lote recibido
                if ($grupo['recibida'] > 0 && $idEntrada) {
                    foreach ($grupo['unidades'] as $unidad) {
                        $unidad_cant = (int)($unidad['cantidad'] ?? 1);
                        if ($unidad_cant < 1) $unidad_cant = 1;
                        // Insertar un ESDET por cada unidad individual dentro del lote
                        for ($u = 0; $u < $unidad_cant; $u++) {
                            $sqlEsDet = "
                                INSERT INTO AMPAR_HIS_ESDET (ESDET_ESID, ESDET_ARTICULOID, ESDET_LOTE, ESDET_SERIE, ESDET_CADUCIDAD, ESDET_ACTIVO)
                                VALUES (?, ?, ?, ?, ?, 0)
                            ";
                            $idEsDet = $db->executeconreturning($sqlEsDet, 'ESDET_ID', [
                                $idEntrada,
                                $art_id,
                                $unidad['lote'],
                                $unidad['serie'],
                                $unidad['caducidad']
                            ]);

                            if (!empty($unidad['caducidad'])) {
                                $fechaCaducidad = strtotime($unidad['caducidad']);
                                $unAnioDespues = strtotime('+1 year');
                                if ($fechaCaducidad < $unAnioDespues) {
                                    if (!empty($ruta_carta_canje) || !empty($num_delivery_general)) {
                                        $sqlCC = "
                                            INSERT INTO AMPAR_CARTA_CANJE (CARTACANJE_ESDETID, CARTACANJE_RUTA_IMG, CARTACANJE_NUM_DELIVERY, CARTACANJE_STOCKID, CARTACANJE_RECEPCIONID)
                                            VALUES (?, ?, ?, NULL, ?)
                                        ";
                                        try {
                                            $db->execute($sqlCC, [$idEsDet, $ruta_carta_canje, $num_delivery_general, $idRecepcion]);
                                        } catch (Exception $ex) {}
                                    }
                                }
                            }
                        }
                    }
                }
            }


            // 3. Verificar estado de la OC y actualizar si es necesario
            // Primero, obtenemos el total pedido de la OC
            $sqlTotalOC = "SELECT SUM(OCDET_CANTIDAD) AS TOTAL_PEDIDO FROM AMPAR_OCDET WHERE OCDET_OCID = ?";
            $resTotalOC = $db->query($sqlTotalOC, [$ocid]);
            $totalPedido = $resTotalOC[0]['TOTAL_PEDIDO'];

            // Segundo, obtenemos el total histórico recibido para esta OC
            $sqlTotalRecibido = "
                SELECT SUM(RD.RECDET_CANTIDAD_RECIBIDA) AS TOTAL_RECIBIDO 
                FROM AMPAR_RECEPCIONDET RD
                JOIN AMPAR_RECEPCION R ON RD.RECDET_RECEPCIONID = R.RECEPCION_ID
                WHERE R.RECEPCION_OCID = ?
            ";
            $resTotalRecibido = $db->query($sqlTotalRecibido, [$ocid]);
            $totalRecibido = $resTotalRecibido[0]['TOTAL_RECIBIDO'] ?? 0;

            // Decidir nuevo estado
            $nuevoStatus = 1; // Pendiente (por default 1 en AMPAR_CONF_STATUS)
            if ($totalRecibido > 0 && $totalRecibido < $totalPedido) {
                $nuevoStatus = 27; // Parcial
            } else if ($totalRecibido >= $totalPedido) {
                $nuevoStatus = 3; // Completo / Finalizado
            }
            
            $sqlUpdOC = "UPDATE AMPAR_OC SET OC_STATUS = ? WHERE OC_ID = ?";
            $db->execute($sqlUpdOC, [$nuevoStatus, $ocid]);

            // Enviar notificación de recepción al usuario creador de la OC (o al usuario actual como fallback)
            try {
                $resOcInfo = $db->query("SELECT OC_USUARIOID, OC_FOLIO FROM AMPAR_OC WHERE OC_ID = ?", [$ocid]);
                $ocUsuarioId = ($resOcInfo && !empty($resOcInfo[0]['OC_USUARIOID'])) ? (int)$resOcInfo[0]['OC_USUARIOID'] : (int)$usuarioid;
                $ocFolio = ($resOcInfo && !empty($resOcInfo[0]['OC_FOLIO'])) ? $resOcInfo[0]['OC_FOLIO'] : $ocid;
                if ($ocUsuarioId > 0 && class_exists('notificaciones')) {
                    $tituloNotif = "Recepción REC-" . $idRecepcion . " | OC: " . $ocFolio;
                    $mensajeNotif = "El material de tu Orden de Compra ha sido recepcionado en almacén.";
                    notificaciones::crear($ocUsuarioId, $idRecepcion, 'RECEPCION_OC', $tituloNotif, $mensajeNotif);
                    
                    // Notificación por WhatsApp
                    require_once(__DIR__ . '/whatsapp.php');
                    $telOcUser = whatsapp::getTelefonoUsuario($ocUsuarioId);
                    if ($telOcUser) {
                        $msgWA = "¡Hola! 📦\n*$tituloNotif*\n$mensajeNotif";
                        whatsapp::enviar($telOcUser, $msgWA);
                    }
                }
            } catch (Throwable $tn) {
                // No interrumpir el flujo si falla la notificación
            }

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollback();
            return "Error al guardar recepción: " . $e->getMessage();
        } finally {
            $db->close();
        }
    }

    function getRecepcionesByOC($ocid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT R.*, U.USUARIO_NOMBRE
            FROM AMPAR_RECEPCION R
            LEFT JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = R.RECEPCION_USUARIOID
            WHERE RECEPCION_OCID = ?
            ORDER BY RECEPCION_ID DESC
        ";
        $result = $db->query($sql, [$ocid]);
        $db->close();
        return $result;
    }

    function getTodasLasRecepciones() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                R.*, 
                U.USUARIO_NOMBRE, 
                O.OC_FOLIO, 
                T.TRASPASO_FOLIO,
                (SELECT SUM(RECDET_CANTIDAD_RECIBIDA) FROM AMPAR_RECEPCIONDET WHERE RECDET_RECEPCIONID = R.RECEPCION_ID) AS TOTAL_ARTICULOS,
                (SELECT FIRST 1 ES_STATUS FROM AMPAR_HIS_ES ES WHERE ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ES_ID DESC) AS ES_STATUS,
                (SELECT FIRST 1 ES_ID FROM AMPAR_HIS_ES ES WHERE ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ES_ID DESC) AS ES_ID,
                (SELECT FIRST 1 ES_MOTIVO_RECHAZO FROM AMPAR_HIS_ES ES WHERE ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50)) ORDER BY ES_ID DESC) AS ES_MOTIVO_RECHAZO
            FROM AMPAR_RECEPCION R
            LEFT JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = R.RECEPCION_USUARIOID
            LEFT JOIN AMPAR_OC O ON O.OC_ID = R.RECEPCION_OCID
            LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = R.RECEPCION_TRASPASOID
            " . (($GLOBALS['isAdmin'] ?? false) ? "" : "WHERE (R.RECEPCION_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0) . " OR O.OC_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0) . " OR T.TRASPASO_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0) . ")") . "
            ORDER BY R.RECEPCION_FECHA DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getRecepcionDetalleById($recepcionid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT RD.*, AR.NOMBRE AS ARTICULO_NOMBRE, X.CLAVE_ARTICULO,
                   S.STOCK_FOLIO, S.STOCK_LOTE, S.STOCK_SERIE, S.STOCK_CADUCIDAD,
                   (SELECT FIRST 1 CC.CARTACANJE_RUTA_IMG 
                    FROM AMPAR_CARTA_CANJE CC 
                    JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ID = CC.CARTACANJE_ESDETID 
                    JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID 
                    JOIN AMPAR_RECEPCION R ON ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))
                    WHERE R.RECEPCION_ID = RD.RECDET_RECEPCIONID AND ED.ESDET_ARTICULOID = RD.RECDET_ARTICULOID AND CC.CARTACANJE_RUTA_IMG IS NOT NULL AND CC.CARTACANJE_RUTA_IMG <> '') AS RUTA_IMG,
                   (SELECT FIRST 1 CC.CARTACANJE_NUM_DELIVERY 
                    FROM AMPAR_CARTA_CANJE CC 
                    JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ID = CC.CARTACANJE_ESDETID 
                    JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID 
                    JOIN AMPAR_RECEPCION R ON ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))
                    WHERE R.RECEPCION_ID = RD.RECDET_RECEPCIONID AND ED.ESDET_ARTICULOID = RD.RECDET_ARTICULOID AND CC.CARTACANJE_NUM_DELIVERY IS NOT NULL AND CC.CARTACANJE_NUM_DELIVERY <> '') AS NUM_DELIVERY
            FROM AMPAR_RECEPCIONDET RD
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = RD.RECDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_TRASPASODET TD ON TD.TRASPASODET_ID = RD.RECDET_TRASPASODETID
            LEFT JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = TD.TRASPASODET_STOCKID
            WHERE RECDET_RECEPCIONID = ?
        ";
        $result = $db->query($sql, [$recepcionid]);
        $db->close();
        return $result;
    }

    function getDocumentosUnidadesPorRecepcion($recepcionid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT ED.ESDET_ARTICULOID, ED.ESDET_ID, ED.ESDET_LOTE, ED.ESDET_SERIE, CC.CARTACANJE_RUTA_IMG, CC.CARTACANJE_NUM_DELIVERY
            FROM AMPAR_HIS_ES ES
            JOIN AMPAR_RECEPCION R ON ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))
            JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ESID = ES.ES_ID
            JOIN AMPAR_CARTA_CANJE CC ON CC.CARTACANJE_ESDETID = ED.ESDET_ID
            WHERE R.RECEPCION_ID = ? AND (CC.CARTACANJE_RUTA_IMG IS NOT NULL OR CC.CARTACANJE_NUM_DELIVERY IS NOT NULL)
        ";
        $result = $db->query($sql, [$recepcionid]);
        $db->close();
        
        $docs = [];
        if (is_array($result)) {
            foreach ($result as $row) {
                $artId = $row['ESDET_ARTICULOID'];
                if (!isset($docs[$artId])) {
                    $docs[$artId] = ['cartas' => [], 'deliveries' => []];
                }
                if (!empty($row['CARTACANJE_RUTA_IMG'])) {
                    $docs[$artId]['cartas'][] = [
                        'ruta' => $row['CARTACANJE_RUTA_IMG'],
                        'lote' => $row['ESDET_LOTE'],
                        'serie' => $row['ESDET_SERIE']
                    ];
                }
                if (!empty($row['CARTACANJE_NUM_DELIVERY'])) {
                    $docs[$artId]['deliveries'][] = [
                        'val' => $row['CARTACANJE_NUM_DELIVERY'],
                        'lote' => $row['ESDET_LOTE'],
                        'serie' => $row['ESDET_SERIE']
                    ];
                }
            }
        }
        return $docs;
    }

    function getRecepcionById($recepcionid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT R.*, U.USUARIO_NOMBRE, O.OC_FOLIO, T.TRASPASO_FOLIO
            FROM AMPAR_RECEPCION R
            LEFT JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = R.RECEPCION_USUARIOID
            LEFT JOIN AMPAR_OC O ON O.OC_ID = R.RECEPCION_OCID
            LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = R.RECEPCION_TRASPASOID
            WHERE RECEPCION_ID = ?
        ";
        $result = $db->query($sql, [$recepcionid]);
        $db->close();
        return $result ? $result[0] : null;
    }
    function actualizarDocumentosRecepcion($recepcionId, $esId, $archivos, $num_delivery_general = '') {
        $db = new FirebirdConnection();
        try {
            $db->beginTransaction();

            $folioCarpeta = 'REC-' . $recepcionId;
            $dirUploads = __DIR__ . '/../uploads/recepciones/' . $folioCarpeta;
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }

            $camposActualizar = [];
            $valoresActualizar = [];

            if (!empty($archivos['archivo_factura_general']['name']) && $archivos['archivo_factura_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_factura_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'FAC_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_factura_general']['tmp_name'], $rutaDestino)) {
                        $camposActualizar[] = "RECEPCION_FACTURA = ?";
                        $valoresActualizar[] = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($archivos['archivo_evidencia_general']['name']) && $archivos['archivo_evidencia_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_evidencia_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EVI_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_evidencia_general']['tmp_name'], $rutaDestino)) {
                        $camposActualizar[] = "RECEPCION_EVIDENCIA = ?";
                        $valoresActualizar[] = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($archivos['archivo_lista_embarque_general']['name']) && $archivos['archivo_lista_embarque_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_lista_embarque_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EMB_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_lista_embarque_general']['tmp_name'], $rutaDestino)) {
                        $camposActualizar[] = "RECEPCION_LISTA_EMBARQUE = ?";
                        $valoresActualizar[] = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($archivos['archivo_carta_canje_general']['name']) && $archivos['archivo_carta_canje_general']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_carta_canje_general']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'CC_GEN_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($archivos['archivo_carta_canje_general']['tmp_name'], $rutaDestino)) {
                        $camposActualizar[] = "RECEPCION_CARTA_CANJE = ?";
                        $ruta_cc = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                        $valoresActualizar[] = $ruta_cc;
                        
                        // Update CARTA_CANJE table
                        $sqlUpdCC = "UPDATE AMPAR_CARTA_CANJE CC SET CC.CARTACANJE_RUTA_IMG = ? WHERE CC.CARTACANJE_ESDETID IN (
                            SELECT ED.ESDET_ID FROM AMPAR_HIS_ESDET ED JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID
                            JOIN AMPAR_RECEPCION R ON ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))
                            WHERE R.RECEPCION_ID = ?
                        )";
                        $db->execute($sqlUpdCC, [$ruta_cc, $recepcionId]);
                    }
                }
            }

            if (!empty($num_delivery_general)) {
                $camposActualizar[] = "RECEPCION_DELIVERY_NUM = ?";
                $valoresActualizar[] = $num_delivery_general;

                // Update CARTA_CANJE table
                $sqlUpdDel = "UPDATE AMPAR_CARTA_CANJE CC SET CC.CARTACANJE_NUM_DELIVERY = ? WHERE CC.CARTACANJE_ESDETID IN (
                    SELECT ED.ESDET_ID FROM AMPAR_HIS_ESDET ED JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID
                    JOIN AMPAR_RECEPCION R ON ES.ES_MOTIVO = 'Recepcion de OC Folio: ' || CAST(R.RECEPCION_OCID AS VARCHAR(50))
                    WHERE R.RECEPCION_ID = ?
                )";
                $db->execute($sqlUpdDel, [$num_delivery_general, $recepcionId]);
            }

            if (!empty($camposActualizar)) {
                $valoresActualizar[] = $recepcionId;
                $sqlUpd = "UPDATE AMPAR_RECEPCION SET " . implode(", ", $camposActualizar) . " WHERE RECEPCION_ID = ?";
                $db->execute($sqlUpd, $valoresActualizar);
            }

            // Cambiar status a 8 (En Revisión) para que se vuelva a revisar
            if (!empty($esId)) {
                $db->execute("UPDATE AMPAR_HIS_ES SET ES_STATUS = 8, ES_MOTIVO_RECHAZO = NULL WHERE ES_ID = ?", [$esId]);
            }

            $db->commit();
            return "";

        } catch (Exception $e) {
            $db->rollback();
            return "Error al actualizar documentos: " . $e->getMessage();
        } finally {
            $db->close();
        }
    }

}
?>
