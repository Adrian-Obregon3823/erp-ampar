<?php
class traspasos
{

    function gettraspasos($status = '')
    {
        $db = new FirebirdConnection();
        $where = "";
        if (!empty($status)) {
            // $status puede ser una lista separada por comas, e.g., '1,2,3'
            $statusList = explode(',', $status);
            $cleanStatus = [];
            foreach ($statusList as $st) {
                $st = (int)trim($st);
                if ($st > 0) {
                    $cleanStatus[] = $st;
                }
            }
            if (count($cleanStatus) > 0) {
                $where = " WHERE TRASPASO_STATUS IN (" . implode(',', $cleanStatus) . ") ";
            }
        }
        
        $usuarioId = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
        $filtroUsuario = ($GLOBALS['isAdmin'] ?? false) ? "" : " t.TRASPASO_USUARIOID = " . (int)$usuarioId;
        
        if (!empty($filtroUsuario)) {
            $where .= empty($where) ? " WHERE $filtroUsuario " : " AND $filtroUsuario ";
        }

        $sql = "
            SELECT 
            t.*, s.*,
            A1.ALMACEN_NOMBRE ALMACEN_NOMBREDE, S1.NOMBRE SUCURSAL_NOMBREDE, A2.ALMACEN_NOMBRE ALMACEN_NOMBREA, S2.NOMBRE SUCURSAL_NOMBREA,
            A1.ALMACEN_TIPOALMACEN TIPO_DE, A2.ALMACEN_TIPOALMACEN TIPO_A, S1.SUCURSAL_ID SUCURSAL_IDDE, S2.SUCURSAL_ID SUCURSAL_IDA,
            (SELECT COUNT(*) FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_TRASPASOID = TRASPASO_ID) CANTIDAD
             FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_CONF_STATUS s ON STATUS_ID  = TRASPASO_STATUS 
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TRASPASO_DEALMACENID
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TRASPASO_AALMACENID
            LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
            LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
            $where
            ORDER BY TRASPASO_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfotraspasobyid($traspasoid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            t.*, s.*, td.*, id.*, AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO,
            (SELECT COUNT(*) FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_TRASPASOID = TRASPASO_ID) CANTIDAD,
            A1.ALMACEN_NOMBRE DEALMACEN, A2.ALMACEN_NOMBRE AALMACEN, 
            S1.SUCURSAL_ID DESUCURSALID, S2.SUCURSAL_ID ASUCURSALID, S1.NOMBRE DESUCURSAL, S2.NOMBRE ASUCURSAL,
            ED.*, U.USUARIO_NOMBRE
            FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_CONF_STATUS s ON STATUS_ID  = TRASPASO_STATUS 
            LEFT JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = TRASPASO_USUARIOID
            LEFT JOIN AMPAR_HIS_TRASPASODET td ON TRASPASODET_TRASPASOID = TRASPASO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TRASPASODET_DEALMACENID
            LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TRASPASODET_AALMACENID
            LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_STOCK id ON STOCK_ID = TRASPASODET_STOCKID
            LEFT JOIN AMPAR_HIS_ESDET ED ON ESDET_ID = STOCK_ESDETID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ED.ESDET_ARTICULOID 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE TRASPASO_ID = " . $traspasoid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function guardartraspaso($dealmacen, $aalmacen, $motivo, $articulos, $archivos = [], $requerimientoMaterialId = null, $paqueteria = null, $numeroGuia = null)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser     = $_SERVER['REMOTE_ADDR'] ?? null;
        $db = new FirebirdConnection(); // MODO TRANSACCIONAL

        try {
            // Validar parentesco entre maletas y almacenes
            $sqlCheck = "SELECT ALMACEN_ID, ALMACEN_TIPOALMACEN, ALMACEN_ALMACEN_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID IN (?, ?)";
            $almacenesData = $db->query($sqlCheck, [$dealmacen, $aalmacen]);
            $deData = null;
            $aData = null;
            if ($almacenesData && is_array($almacenesData)) {
                foreach ($almacenesData as $ad) {
                    if ($ad['ALMACEN_ID'] == $dealmacen) $deData = $ad;
                    if ($ad['ALMACEN_ID'] == $aalmacen) $aData = $ad;
                }
            }
            
            $isMaletaTransfer = false;
            
            if ($deData && $aData) {
                $isDeMaleta = ($deData['ALMACEN_TIPOALMACEN'] == 3);
                $isAMaleta = ($aData['ALMACEN_TIPOALMACEN'] == 3);
                
                if ($isDeMaleta && !$isAMaleta) {
                    $isMaletaTransfer = true;
                    if ($aData['ALMACEN_ID'] == 10) { // Destino Material Caducado
                        if ($deData['ALMACEN_ALMACEN_MS'] != 1) { // Origen no es Saltillo CEDIS (id 1)
                            throw new Exception("No está permitido traspasar maletas a Material Caducado que no pertenezcan a Saltillo CEDIS (ID 1).");
                        }
                    } else if ($deData['ALMACEN_ALMACEN_MS'] != $aData['ALMACEN_ID']) {
                        throw new Exception("La maleta de origen no pertenece al almacén destino.");
                    }
                } else if (!$isDeMaleta && $isAMaleta) {
                    $isMaletaTransfer = true;
                    if ($aData['ALMACEN_ALMACEN_MS'] != $deData['ALMACEN_ID']) {
                        throw new Exception("La maleta de destino no pertenece al almacén origen.");
                    }
                }
            }

            $statusInicial = $isMaletaTransfer ? 8 : 1;

            // Insertar cabecera del traspaso
            $sql = "
                INSERT INTO AMPAR_HIS_TRASPASO
                (
                    TRASPASO_FECHACREACION,
                    TRASPASO_FOLIO,
                    TRASPASO_MOTIVO,
                    TRASPASO_DEALMACENID,
                    TRASPASO_AALMACENID,
                    TRASPASO_STATUS,
                    TRASPASO_USUARIOID,
                    TRASPASO_REQMATERIALID,
                    TRASPASO_PAQUETERIA,
                    TRASPASO_NUMGUIA
                )
                VALUES
                (
                    CURRENT_TIMESTAMP,
                    (
                        SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(TRASPASO_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                        || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                        FROM AMPAR_HIS_TRASPASO
                    ),
                    ?,
                    ?,
                    ?,
                    " . $statusInicial . ",
                    " . $usersesion['USUARIO_ID'] . ",
                    ?,
                    ?,
                    ?
                )
            ";
            $id_insertado = $db->executeconreturning($sql, 'TRASPASO_ID', [$motivo, $dealmacen, $aalmacen, $requerimientoMaterialId ?: null, $paqueteria ?: null, $numeroGuia ?: null]);

            if (!$id_insertado) {
                throw new Exception("Error al insertar en AMPAR_HIS_TRASPASO");
            }

            // Insertar detalle
            $this->guardartraspasodet($db, $id_insertado, $dealmacen, $aalmacen, $articulos, $isMaletaTransfer);

            // Actualizar status del requerimiento de material si está vinculado
            if ($requerimientoMaterialId) {
                require_once __DIR__ . '/requerimientosmaterial.php';
                $reqMat = new requerimientosmaterial();
                $reqMat->actualizarStatusAutomatico($requerimientoMaterialId);
            }

            // Bitácora
            // Consulta Información
            $querybitacora = "
                SELECT
                T.TRASPASO_ID, T.TRASPASO_FOLIO, TD.TRASPASODET_ID, TD.TRASPASODET_TRASPASOID, 
                TD.TRASPASODET_DEALMACENID, A1.ALMACEN_NOMBRE ALMACEN_NOMBREDE, S1.SUCURSAL_ID SUCURSAL_IDDE, S1.NOMBRE SUCURSAL_NOMBREDE,
                TD.TRASPASODET_AALMACENID, A2.ALMACEN_NOMBRE ALMACEN_NOMBREA, S2.SUCURSAL_ID SUCURSAL_IDA, S2.NOMBRE SUCURSAL_NOMBREA,
                TD.TRASPASODET_STOCKID, STOCK_FOLIO, STOCK_ARTICULOID, X.CLAVE_ARTICULO,
                TD.TRASPASODET_ACTIVO                
                FROM AMPAR_HIS_TRASPASODET TD 
                LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = TD.TRASPASODET_TRASPASOID
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = T.TRASPASO_STATUS
                LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TD.TRASPASODET_DEALMACENID
                LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TD.TRASPASODET_AALMACENID
                LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
                LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
                LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ID = TD.TRASPASODET_STOCKID
                LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = STOCK_ARTICULOID
                LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                WHERE T.TRASPASO_ID = ?
            ";

            $infotraspaso = $db->query($querybitacora, [$id_insertado]);

            if ($infotraspaso === 0) {
                throw new Exception("Error al obtener datos para la bitácora");
            }

            // Procesar Archivos de Traspaso
            $folioCarpeta = !empty($infotraspaso[0]['TRASPASO_FOLIO']) ? $infotraspaso[0]['TRASPASO_FOLIO'] : $id_insertado;
            $dirUploads = __DIR__ . '/../uploads/traspasos/' . $folioCarpeta;
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }

            $ruta_factura = null;
            if (!empty($archivos['archivo_factura_traspaso']['name']) && $archivos['archivo_factura_traspaso']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_factura_traspaso']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'FAC_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($archivos['archivo_factura_traspaso']['tmp_name'], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                        $ruta_factura = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_doc2 = null;
            if (!empty($archivos['archivo_documento2_traspaso']['name']) && $archivos['archivo_documento2_traspaso']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_documento2_traspaso']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'DOC2_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($archivos['archivo_documento2_traspaso']['tmp_name'], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                        $ruta_doc2 = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_evidencia = null;
            if (!empty($archivos['archivo_evidencia_traspaso']['name']) && $archivos['archivo_evidencia_traspaso']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_evidencia_traspaso']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EVI_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($archivos['archivo_evidencia_traspaso']['tmp_name'], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                        $ruta_evidencia = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            $ruta_guia = null;
            if (!empty($archivos['archivo_guia_traspaso']['name']) && $archivos['archivo_guia_traspaso']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($archivos['archivo_guia_traspaso']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'GUI_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($archivos['archivo_guia_traspaso']['tmp_name'], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                        $ruta_guia = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($ruta_factura) || !empty($ruta_doc2) || !empty($ruta_evidencia) || !empty($ruta_guia)) {
                try {
                    $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_FACTURA VARCHAR(255)");
                } catch (Throwable $e) {
                }
                try {
                    $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_DOCUMENTO2 VARCHAR(255)");
                } catch (Throwable $e) {
                }
                try {
                    $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_EVIDENCIA VARCHAR(255)");
                } catch (Throwable $e) {
                }
                try {
                    $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_GUIA VARCHAR(255)");
                } catch (Throwable $e) {
                }
                $db->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_FACTURA = ?, TRASPASO_DOCUMENTO2 = ?, TRASPASO_EVIDENCIA = ?, TRASPASO_GUIA = ? WHERE TRASPASO_ID = ?", [$ruta_factura, $ruta_doc2, $ruta_evidencia, $ruta_guia, $id_insertado]);
            }

            $sqlBit = "
                INSERT INTO AMPAR_BITACORA
                (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
                BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
                VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)
            ";

            // Bitácora cabecera
            $comentCab = 'SOLICITUD DE TRASPASO FOLIO:<strong>' . $infotraspaso[0]['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREDE'] . '(Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREDE'] . ') A ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREA'] . ' (Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREA'] . ') Campos:' . bitacora::printarray($infotraspaso[0]);
            $db->execute($sqlBit, [
                $comentCab,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $infotraspaso[0]['SUCURSAL_IDDE'],
                $infotraspaso[0]['TRASPASODET_DEALMACENID'],
                null // BITACORA_STOCKID
            ]);

            foreach ($infotraspaso as $infot) {
                // Bitácora detalle
                $comentCab = 'ARTÍCULO:<strong>' . $infot['STOCK_FOLIO'] . '</strong> AGREGADO Y APARTADO EN SOLICITUD DE TRASPASO CON FOLIO ' . $infot['TRASPASO_FOLIO'] . ' DEL ALMACEN ' . $infot['ALMACEN_NOMBREDE'] . '(Sucursal: ' . $infot['SUCURSAL_NOMBREDE'] . ') A ALMACEN ' . $infot['ALMACEN_NOMBREA'] . ' (Sucursal: ' . $infot['SUCURSAL_NOMBREA'] . ') Campos:' . bitacora::printarray($infot);
                $db->execute($sqlBit, [
                    $comentCab,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser,
                    $infot['SUCURSAL_IDDE'],
                    $infot['TRASPASODET_DEALMACENID'],
                    $infot['TRASPASODET_STOCKID']
                ]);
            }

            // Confirmar transacción
            $db->commit();
            if ($isMaletaTransfer) {
                echo "OK_SPECIAL|" . $id_insertado;
            } else {
                echo "OK|" . $id_insertado;
            }
        } catch (Exception $e) {
            $db->rollback();
            echo "Transacción cancelada: " . $e->getMessage();
        } finally {
            $db->close();
        }
    }

    function guardartraspasodet($db, $idtraspaso, $dealmacen, $aalmacen, $articulos, $isMaletaTransfer = false)
    {
        foreach ($articulos as $articuloId) {

            // Insertar en detalle
            $sql = "
                INSERT INTO AMPAR_HIS_TRASPASODET
                (
                    TRASPASODET_TRASPASOID,
                    TRASPASODET_DEALMACENID,
                    TRASPASODET_AALMACENID,
                    TRASPASODET_STOCKID,
                    TRASPASODET_ACTIVO
                )
                VALUES
                (?, ?, ?, ?, ?)
            ";
            $id_insertadodet = $db->executeconreturning($sql, 'TRASPASODET_ID', [
                $idtraspaso,
                $dealmacen,
                $aalmacen,
                $articuloId,
                $isMaletaTransfer ? 1 : 0
            ]);

            if (!$id_insertadodet) {
                throw new Exception("Error al insertar en AMPAR_HIS_TRASPASODET para el artículo $articuloId");
            }

            // Actualizar stock
            // Apartar temporalmente
            $sql2 = "UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?";
            if (!$db->execute($sql2, [$articuloId])) {
                throw new Exception("Error al actualizar AMPAR_HIS_STOCK para $articuloId");
            }
        }
    }

    //EDICION TRASPASO
    function actualizarTraspasoId($desucursalid, $asucursalid, $dealmacenid, $aalmacenid, $traspasoid, $motivo, $cambiosJson)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        //CONSULTAINFORMACION DE TRASPASOID
        $sqlinfo = "
            SELECT 
            t.*, s.*,
            A1.ALMACEN_NOMBRE ALMACEN_NOMBREDE, S1.NOMBRE SUCURSAL_NOMBREDE, 
            A2.ALMACEN_NOMBRE ALMACEN_NOMBREA, S2.NOMBRE SUCURSAL_NOMBREA
            FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_CONF_STATUS s ON STATUS_ID  = TRASPASO_STATUS 
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TRASPASO_DEALMACENID
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TRASPASO_AALMACENID
            LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
            LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
            WHERE t.TRASPASO_ID = ?
        ";
        $infot = $db->query($sqlinfo, [$traspasoid]);

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
                        case 'motivo':
                            $fields[] = "TRASPASO_MOTIVO = ?";
                            $params[] = $motivo;
                            break;
                    }
                }
                if (!empty($fields)) {
                    // 1. Ejecutar el UPDATE
                    $sqlUpd = "UPDATE AMPAR_HIS_TRASPASO SET " . implode(", ", $fields) . " WHERE TRASPASO_ID = ?";
                    $params[] = $traspasoid ?? null;
                    $db->execute($sqlUpd, $params);

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
                    $comentarioBit = 'EDICIÓN DE SOLICITUD DE TRASPASO' .
                        ' FOLIO:<strong>' . $infot[0]['TRASPASO_FOLIO'] . '</strong> (De Sucursal:' . $infot[0]['SUCURSAL_NOMBREDE'] . ' y de Almacén:' . $infot[0]['ALMACEN_NOMBREDE'] .
                        ' A Sucursal:' . $infot[0]['SUCURSAL_NOMBREA'] . ' y a Almacén:' . $infot[0]['ALMACEN_NOMBREA'] . ')' .
                        ' Campos: ' .
                        $camposEditadosStr;

                    // 4. Guardar en bitácora
                    $db->execute($sqlBit, [
                        $comentarioBit,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $desucursalid,
                        $dealmacenid,
                        null
                    ]);
                }
            }
            // --- PROCESAR ARTÍCULOS ---
            if (!empty($cambios['articulos'])) {
                foreach ($cambios['articulos'] as $art) {
                    switch ($art['tipo']) {
                        case 'agregado':
                            $sqlInsert = "INSERT INTO AMPAR_HIS_TRASPASODET
                                (TRASPASODET_TRASPASOID, TRASPASODET_DEALMACENID, TRASPASODET_AALMACENID, TRASPASODET_STOCKID, TRASPASODET_ACTIVO)
                                VALUES (?, ?, ?, ?, 0)";
                            $db->execute($sqlInsert, [
                                $traspasoid,
                                $dealmacenid,
                                $aalmacenid,
                                $art['nuevo']['stockid'] ?? null
                            ]);
                            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?", [$art['nuevo']['stockid'] ?? null]);

                            // Agregar a Bitácota
                            $comentarioBit = 'SE AGREGÓ Y SE APARTO FOLIO:' . $art['nuevo']['folioarticulo'] . "," . ' ARTÍCULO ' . $art['nuevo']['nombre'] . ' A DETALLE DE SOLICITUD DE TRASPASO' .
                                ' FOLIO:<strong>' . $infot[0]['TRASPASO_FOLIO'] . '</strong>';

                            $db->execute($sqlBit, [
                                $comentarioBit,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $desucursalid,
                                $dealmacenid,
                                $art['nuevo']['stockid'] ?? null
                            ]);
                            break;

                        case 'eliminado':
                            $sqlDel = "DELETE FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_ID = ?";
                            $db->execute($sqlDel, [$art['id']]);

                            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 1 WHERE STOCK_ID = ?", [$art['stockid'] ?? null]);

                            // Agregar a Bitácota
                            $comentarioBit = 'SE ELIMINÓ Y SE LIBERÓ FOLIO:' . $art['folioarticulo'] . ', ARTÍCULO ' . $art['nombre'] . ' A DETALLE DE SOLICITUD DE TRASPASO' .
                                ' FOLIO:<strong>' . $infot[0]['TRASPASO_FOLIO'] . '</strong>';

                            $db->execute($sqlBit, [
                                $comentarioBit,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $desucursalid,
                                $dealmacenid,
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

    function updatestatustraspasos($id, $status, $motivo_rechazo = null)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser     = $_SERVER['REMOTE_ADDR'] ?? null;
        $db = new FirebirdConnection(true);

        //Prepara Query Bitácora
        $sqlBit = "
            INSERT INTO AMPAR_BITACORA
            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
            BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)
        ";

        //ACTUALIZO EL STATUS DEL TRASPASO
        if (($status == 5 || $status == 6) && !empty($motivo_rechazo)) {
            $sql = "
                UPDATE AMPAR_HIS_TRASPASO
                SET
                    TRASPASO_STATUS = ?,
                    TRASPASO_MOTIVO_RECHAZO = ?
                WHERE
                    TRASPASO_ID = ?
            ";
            $db->execute($sql, [$status, $motivo_rechazo, $id]);
        } else {
            $sql = "
                UPDATE AMPAR_HIS_TRASPASO
                SET
                    TRASPASO_STATUS = ?
                WHERE
                    TRASPASO_ID = ?
            ";
            $db->execute($sql, [$status, $id]);
        }

        //Consulta información
        $sqldet = "
            SELECT 
            TRASPASO_ID, TRASPASO_FOLIO, TRASPASO_FECHACREACION, TRASPASO_STATUS, S.STATUS_NOMBRE, TRASPASO_MOTIVO,
            STATUS_ID, STATUS_NOMBRE,
            TRASPASODET_ID, 
            STOCK_ID, STOCK_FOLIO, AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO,
            TRASPASODET_DEALMACENID, A1.ALMACEN_NOMBRE ALMACEN_NOMBREDE, S1.SUCURSAL_ID SUCURSAL_IDDE, S1.NOMBRE SUCURSAL_NOMBREDE,
            TRASPASODET_AALMACENID, A2.ALMACEN_NOMBRE ALMACEN_NOMBREA, S2.SUCURSAL_ID SUCURSAL_IDA, S2.NOMBRE SUCURSAL_NOMBREA,
            A1.ALMACEN_NOMBRE DEALMACEN, A2.ALMACEN_NOMBRE AALMACEN, S1.SUCURSAL_ID SUCURSAL_IDDE, S1.NOMBRE DESUCURSAL, S2.SUCURSAL_ID SUCURSAL_IDA, S2.NOMBRE ASUCURSAL
            FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_CONF_STATUS s ON STATUS_ID  = TRASPASO_STATUS 
            LEFT JOIN AMPAR_HIS_TRASPASODET td ON TRASPASODET_TRASPASOID = TRASPASO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TRASPASODET_DEALMACENID
            LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TRASPASODET_AALMACENID
            LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_STOCK st ON STOCK_ID = TRASPASODET_STOCKID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = STOCK_ARTICULOID 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE TRASPASO_ID = " . $id . "
        ";
        $infotraspaso = $db->query($sqldet);

        //SI SE CANCELA O RECHAZA SE LIBERA DE STOCK
        if ($status == 5 || $status == 6) {
            if ($infotraspaso[0]['TRASPASODET_ID'] <> "") {
                foreach ($infotraspaso as $det) {
                    $sql1 = "
                        UPDATE AMPAR_HIS_STOCK
                        set
                            STOCK_STOCKSTATUSID = 1
                        where
                            STOCK_ID = " . $det["STOCK_ID"] . "
                    ";
                    $db->execute($sql1);
                    //Guarda en bitácora
                    $comentBit = 'Se libera artículo <b>' . $det['STOCK_FOLIO'] . '</b> - ' . $det['ARTICULO_NOMBRE'] . ' (' . $det['CLAVE_ARTICULO'] . ') por cancelacion o rechazo de SOLICITUD DE TRASPASO FOLIO:<strong>' . $det['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $det['DEALMACEN'] . '(Sucursal: ' . $det['DESUCURSAL'] . ') A ALMACEN ' . $det['AALMACEN'] . ' (Sucursal: ' . $det['ASUCURSAL'] . ')';
                    $db->execute($sqlBit, [
                        $comentBit,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $det['SUCURSAL_IDDE'],
                        $det['TRASPASODET_DEALMACENID'],
                        $det['STOCK_ID']
                    ]);
                }
            }
        }

        //Guardar en bitácora
        $comentBit = 'Cambio de status de status a <b>' . $infotraspaso[0]['STATUS_NOMBRE'] . '</b> de SOLICITUD DE TRASPASO FOLIO:<strong>' . $infotraspaso[0]['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREDE'] . '(Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREDE'] . ') A ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREA'] . ' (Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREA'] . ') Campos:' . bitacora::printarray($infotraspaso[0]);
        $db->execute($sqlBit, [
            $comentBit,
            $usersesion['USUARIO_ID'],
            $usersesion['USUARIO_CORREO'],
            $ipuser,
            $infotraspaso[0]['SUCURSAL_IDDE'],
            $infotraspaso[0]['TRASPASODET_DEALMACENID'],
            null
        ]);

        $db->close();
    }

    function autorizartraspasos($traspasoid, $detalle)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser     = $_SERVER['REMOTE_ADDR'] ?? null;
        $db = new FirebirdConnection(); // MODO TRANSACCIONAL - sin autocommit

        try {

        //Prepara Query Bitácora
        $sqlBit = "
            INSERT INTO AMPAR_BITACORA
            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
            BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)
        ";

        // Determinar tipo de almacenes ANTES de hacer cualquier UPDATE
        $sqlTipos = "
            SELECT 
                A1.ALMACEN_TIPOALMACEN AS TIPO_DE,
                A2.ALMACEN_TIPOALMACEN AS TIPO_A,
                t.TRASPASO_AALMACENID
            FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = t.TRASPASO_DEALMACENID
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = t.TRASPASO_AALMACENID
            WHERE t.TRASPASO_ID = " . (int)$traspasoid . "
        ";
        $resTipos = $db->query($sqlTipos);
        $tipoA = isset($resTipos[0]['TIPO_A']) ? (int)$resTipos[0]['TIPO_A'] : 0;
        $tipoDe = isset($resTipos[0]['TIPO_DE']) ? (int)$resTipos[0]['TIPO_DE'] : 0;
        $almacenDestinoId = isset($resTipos[0]['TRASPASO_AALMACENID']) ? (int)$resTipos[0]['TRASPASO_AALMACENID'] : 0;
        $esAutoRecepcion = ($tipoA === 3 && $tipoDe !== 3);

        // Determinar status inicial: si es auto-recepción va directo a 3, si no va a 9
        $statusInicial = $esAutoRecepcion ? 3 : 9;

        //CAMBIAR EL STATUS DEL TRASPASO
        $sql4 = "
            UPDATE AMPAR_HIS_TRASPASO
            SET TRASPASO_STATUS = " . $statusInicial . "
            WHERE 
            TRASPASO_ID = " . (int)$traspasoid . "
        ";
        $db->execute($sql4);

        //CONSULTA LA INFORMACION GENERAL Y EL DETALLE DEL TRASPASO
        $sql = "
            SELECT 
            t.TRASPASO_ID, t.TRASPASO_FOLIO, t.TRASPASO_STATUS, t.TRASPASO_AALMACENID, t.TRASPASO_DEALMACENID,
            s.STATUS_NOMBRE,
            td.TRASPASODET_ID, td.TRASPASODET_DEALMACENID, td.TRASPASODET_AALMACENID, td.TRASPASODET_STOCKID,
            st.STOCK_ID, st.STOCK_FOLIO, st.STOCK_ARTICULOID,
            AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO,
            A1.ALMACEN_NOMBRE DEALMACEN, S1.SUCURSAL_ID SUCURSAL_IDDE, S1.NOMBRE DESUCURSAL, A1.ALMACEN_TIPOALMACEN TIPO_DE,
            A2.ALMACEN_NOMBRE AALMACEN, S2.SUCURSAL_ID SUCURSAL_IDA, S2.NOMBRE ASUCURSAL, A2.ALMACEN_TIPOALMACEN TIPO_A
            FROM AMPAR_HIS_TRASPASO t
            LEFT JOIN AMPAR_CONF_STATUS s ON s.STATUS_ID  = t.TRASPASO_STATUS
            LEFT JOIN AMPAR_HIS_TRASPASODET td ON td.TRASPASODET_TRASPASOID = t.TRASPASO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = t.TRASPASO_DEALMACENID
            LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = t.TRASPASO_AALMACENID
            LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_STOCK st ON st.STOCK_ID = td.TRASPASODET_STOCKID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = st.STOCK_ARTICULOID 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE t.TRASPASO_ID = " . (int)$traspasoid . "
        ";
        $infotraspaso = $db->query($sql);

        //Dividir el array en selccionados y no seleccionados
        $idsSeleccionados = array_flip($detalle);
        $seleccionados = [];
        $noSeleccionados = [];

        foreach ($infotraspaso as $row) {
            if (isset($idsSeleccionados[(int)$row['TRASPASODET_ID']])) {
                $seleccionados[] = $row;
            } else {
                $noSeleccionados[] = $row;
            }
        }

        //QUITAR EL ESTATUS DE APARTADO (2) DEL STOCK SOLO A LOS NO SELECCIONADOS Y GUARDAR EN BITÁCORA
        foreach ($noSeleccionados as $dett) {
            $sql0 = "
                UPDATE AMPAR_HIS_STOCK
                SET 
                STOCK_STOCKSTATUSID = 1
                WHERE 
                STOCK_ID = " . $dett['STOCK_ID'] . "
            ";
            $db->execute($sql0);

            //Guardar en bitácora
            $comentBit = 'Se libera artículo <b>' . $dett['STOCK_FOLIO'] . '</b> - ' . $dett['ARTICULO_NOMBRE'] . ' (' . $dett['CLAVE_ARTICULO'] . ') de SOLICITUD DE TRASPASO FOLIO:<strong>' . $dett['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $dett['DEALMACEN'] . '(Sucursal: ' . $dett['DESUCURSAL'] . ') A ALMACEN ' . $dett['AALMACEN'] . ' (Sucursal: ' . $dett['ASUCURSAL'] . ') porque no fue autorizado en el envío';
            $db->execute($sqlBit, [
                $comentBit,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $dett['SUCURSAL_IDDE'],
                $dett['TRASPASODET_DEALMACENID'],
                $dett['STOCK_ID']
            ]);
        }

        //PONER STATUS ACTIVO = 1 PARA DIFERENCIAR LOS QUE EL USUARIO AUTORIZÓ A ENVIAR (SE QUEDAN APARTADOS HASTA LA RECEPCIÓN)
        foreach ($seleccionados as $det) {

            //PONER EN ACTIVO = 1 LOS QUE SI SE TRASPASARON O LOS SELECCIONADOS
            $sql3 = "
                UPDATE AMPAR_HIS_TRASPASODET
                SET TRASPASODET_ACTIVO = 1
                WHERE 
                TRASPASODET_ID = " . $det['TRASPASODET_ID'] . "
            ";
            $db->execute($sql3);

            //Guardar en Bitácora
            $comentBit = 'Se traspasa artículo <b>' . $det['STOCK_FOLIO'] . '</b> - ' . $det['ARTICULO_NOMBRE'] . ' (' . $det['CLAVE_ARTICULO'] . ') de SOLICITUD DE TRASPASO FOLIO:<strong>' . $det['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $det['DEALMACEN'] . '(Sucursal: ' . $det['DESUCURSAL'] . ') A ALMACEN ' . $det['AALMACEN'] . ' (Sucursal: ' . $det['ASUCURSAL'] . ')';
            $db->execute($sqlBit, [
                $comentBit,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $det['SUCURSAL_IDA'],
                $det['TRASPASODET_AALMACENID'],
                $det['STOCK_ID']
            ]);
        }

        //Guardar en Bitácora
        $comentBit = 'Cambio de status de status a <b>' . $infotraspaso[0]['STATUS_NOMBRE'] . '</b> de SOLICITUD DE TRASPASO FOLIO:<strong>' . $infotraspaso[0]['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $infotraspaso[0]['DEALMACEN'] . '(Sucursal: ' . $infotraspaso[0]['DESUCURSAL'] . ') A ALMACEN ' . $infotraspaso[0]['AALMACEN'] . ' (Sucursal: ' . $infotraspaso[0]['ASUCURSAL'] . ') Campos:' . bitacora::printarray($infotraspaso[0]);
        $db->execute($sqlBit, [
            $comentBit,
            $usersesion['USUARIO_ID'],
            $usersesion['USUARIO_CORREO'],
            $ipuser,
            $det['SUCURSAL_IDA'],
            $det['TRASPASODET_AALMACENID'],
            null
        ]);

        // AUTO-RECEPCION PARA ALMACEN A MALETA
        if ($esAutoRecepcion && count($seleccionados) > 0) {
            
            // Insertar RECEPCION
            $sqlInsRec = "INSERT INTO AMPAR_RECEPCION (RECEPCION_FECHA, RECEPCION_USUARIOID, RECEPCION_TRASPASOID, RECEPCION_STATUS) VALUES (CURRENT_TIMESTAMP, ?, ?, 1)";
            $idRecepcion = $db->executeconreturning($sqlInsRec, 'RECEPCION_ID', [$usersesion['USUARIO_ID'], $traspasoid]);
            
            foreach ($seleccionados as $det) {
                // Insertar RECEPCIONDET
                $db->execute("
                    INSERT INTO AMPAR_RECEPCIONDET (RECDET_RECEPCIONID, RECDET_TRASPASODETID, RECDET_ARTICULOID, RECDET_COSTO_UNITARIO, RECDET_CANTIDAD_ESPERADA, RECDET_CANTIDAD_RECIBIDA, RECDET_CANTIDAD_RECHAZADA) 
                    VALUES (?, ?, ?, 0, 1, 1, 0)
                ", [$idRecepcion, $det['TRASPASODET_ID'], $det['STOCK_ARTICULOID']]);

                // Actualizar STOCK
                $db->execute("
                    UPDATE AMPAR_HIS_STOCK
                    SET STOCK_ALMACENIDACTUAL = ?, STOCK_STOCKSTATUSID = 1
                    WHERE STOCK_ID = ?
                ", [$almacenDestinoId, $det['STOCK_ID']]);
            }
            
            // Bitácora de auto-recepción
            $comentBitRec = 'Se auto-recepcionó el traspaso FOLIO: ' . $traspasoid . ' en recepción REC-' . $idRecepcion . ' (Almacén a Maleta)';
            $db->execute("
                INSERT INTO AMPAR_BITACORA (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_ALMACENID)
                VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)
            ", [$comentBitRec, $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $ipuser, $almacenDestinoId]);
        }

        $db->commit();
        $db->close();

        } catch (Exception $e) {
            try { $db->rollback(); } catch (Exception $ex) {}
            try { $db->close(); } catch (Exception $ex) {}
            echo "Error al autorizar traspaso: " . $e->getMessage();
        }
    }

    public function enviarRevision($traspasoid, $archivos, $paqueteria, $numeroGuia, $linkRastreo, $isAvance = false) {
        global $db;
        $errores = [];
        $dirUploads = 'C:/laragon/www/amparv3/uploads/traspasos';
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser     = $_SERVER['REMOTE_ADDR'] ?? null;
        $db = new FirebirdConnection(true);

        try {
            // Consultar folio para la carpeta
            $sqlinfo = "SELECT TRASPASO_FOLIO, TRASPASO_STATUS, TRASPASO_DEALMACENID, A1.ALMACEN_NOMBRE ALMACEN_NOMBREDE, S1.SUCURSAL_ID SUCURSAL_IDDE, S1.NOMBRE SUCURSAL_NOMBREDE, TRASPASO_AALMACENID, A2.ALMACEN_NOMBRE ALMACEN_NOMBREA, S2.SUCURSAL_ID SUCURSAL_IDA, S2.NOMBRE SUCURSAL_NOMBREA FROM AMPAR_HIS_TRASPASO LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = TRASPASO_DEALMACENID LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = TRASPASO_AALMACENID LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS WHERE TRASPASO_ID = ?";
            $infotraspaso = $db->query($sqlinfo, [$traspasoid]);

            if (!$infotraspaso || empty($infotraspaso)) {
                throw new Exception("Traspaso no encontrado.");
            }

            if ($infotraspaso[0]['TRASPASO_STATUS'] != 1 && $infotraspaso[0]['TRASPASO_STATUS'] != 6) {
                throw new Exception("El traspaso no está en un estatus válido para enviar a revisión.");
            }

            // Si estaba en estatus 6 (rechazado), debemos volver a apartar el stock de sus detalles
            if ($infotraspaso[0]['TRASPASO_STATUS'] == 6) {
                $sqlDetalles = "SELECT TRASPASODET_STOCKID FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_TRASPASOID = ?";
                $detallesTraspaso = $db->query($sqlDetalles, [$traspasoid]);
                if (!empty($detallesTraspaso)) {
                    foreach ($detallesTraspaso as $d) {
                        if (!empty($d['TRASPASODET_STOCKID'])) {
                            $db->execute("UPDATE AMPAR_HIS_STOCK SET STOCK_STOCKSTATUSID = 2 WHERE STOCK_ID = ?", [$d['TRASPASODET_STOCKID']]);
                        }
                    }
                }
            }

            $folioCarpeta = !empty($infotraspaso[0]['TRASPASO_FOLIO']) ? $infotraspaso[0]['TRASPASO_FOLIO'] : $traspasoid;
            $dirUploads = __DIR__ . '/../uploads/traspasos/' . $folioCarpeta;
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }

            $rutas = [
                'archivo_evidencia1_traspaso' => null,
                'archivo_evidencia2_traspaso' => null,
                'archivo_evidencia3_traspaso' => null,
                'archivo_evidencia4_traspaso' => null,
                'archivo_lista_empaque' => null,
                'archivo_guia_traspaso' => null
            ];

            foreach ($rutas as $inputName => &$ruta) {
                if ($inputName == 'archivo_lista_empaque' && isset($archivos[$inputName]['name']) && is_array($archivos[$inputName]['name'])) {
                    $rutasMultiples = [];
                    foreach ($archivos[$inputName]['name'] as $idx => $name) {
                        if (!empty($name) && $archivos[$inputName]['error'][$idx] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            $nombreArchivo = $inputName . '_' . $idx . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($archivos[$inputName]['tmp_name'][$idx], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                                $rutasMultiples[] = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                            }
                        }
                    }
                    if (count($rutasMultiples) > 0) {
                        $ruta = implode('|', $rutasMultiples);
                    }
                } else {
                    if (!empty($archivos[$inputName]['name']) && $archivos[$inputName]['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($archivos[$inputName]['name'], PATHINFO_EXTENSION));
                        $nombreArchivo = $inputName . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($archivos[$inputName]['tmp_name'], $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo)) {
                            $ruta = 'uploads/traspasos/' . $folioCarpeta . '/' . $nombreArchivo;
                        }
                    }
                }
            }

            // Crear columnas si no existen
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_EVIDENCIA1 VARCHAR(255)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_EVIDENCIA2 VARCHAR(255)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_EVIDENCIA3 VARCHAR(255)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_EVIDENCIA4 VARCHAR(255)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_LISTAEMPAQUE VARCHAR(4000)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ALTER COLUMN TRASPASO_LISTAEMPAQUE TYPE VARCHAR(4000)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ALTER TRASPASO_LISTAEMPAQUE TYPE VARCHAR(4000)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_GUIA VARCHAR(255)"); } catch (Throwable $e) {}
            try { $db->execute("ALTER TABLE AMPAR_HIS_TRASPASO ADD TRASPASO_LINK VARCHAR(255)"); } catch (Throwable $e) {}

            $statusUpdateStr = $isAvance ? "" : "TRASPASO_STATUS = 8,";
            $sqlUpd = "UPDATE AMPAR_HIS_TRASPASO SET 
                $statusUpdateStr
                TRASPASO_PAQUETERIA = COALESCE(NULLIF(?, ''), TRASPASO_PAQUETERIA), 
                TRASPASO_NUMGUIA = COALESCE(NULLIF(?, ''), TRASPASO_NUMGUIA), 
                TRASPASO_LINK = COALESCE(NULLIF(?, ''), TRASPASO_LINK),
                TRASPASO_EVIDENCIA1 = COALESCE(?, TRASPASO_EVIDENCIA1),
                TRASPASO_EVIDENCIA2 = COALESCE(?, TRASPASO_EVIDENCIA2),
                TRASPASO_EVIDENCIA3 = COALESCE(?, TRASPASO_EVIDENCIA3),
                TRASPASO_EVIDENCIA4 = COALESCE(?, TRASPASO_EVIDENCIA4),
                TRASPASO_LISTAEMPAQUE = COALESCE(?, TRASPASO_LISTAEMPAQUE),
                TRASPASO_GUIA = COALESCE(?, TRASPASO_GUIA)
                WHERE TRASPASO_ID = ?";

            $db->execute($sqlUpd, [
                $paqueteria,
                $numeroGuia,
                $linkRastreo,
                $rutas['archivo_evidencia1_traspaso'],
                $rutas['archivo_evidencia2_traspaso'],
                $rutas['archivo_evidencia3_traspaso'],
                $rutas['archivo_evidencia4_traspaso'],
                $rutas['archivo_lista_empaque'],
                $rutas['archivo_guia_traspaso'],
                $traspasoid
            ]);

            // Bitácora
            $sqlBit = "
                INSERT INTO AMPAR_BITACORA
                (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
                BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID)
                VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)
            ";
            if ($isAvance) {
                $comentBit = 'Se guardó un avance de evidencias para la SOLICITUD DE TRASPASO FOLIO:<strong>' . $infotraspaso[0]['TRASPASO_FOLIO'] . '</strong>.';
            } else {
                $comentBit = 'Se envia a revisión la SOLICITUD DE TRASPASO FOLIO:<strong>' . $infotraspaso[0]['TRASPASO_FOLIO'] . '</strong> DE ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREDE'] . '(Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREDE'] . ') A ALMACEN ' . $infotraspaso[0]['ALMACEN_NOMBREA'] . ' (Sucursal: ' . $infotraspaso[0]['SUCURSAL_NOMBREA'] . ') y se adjuntan evidencias y datos de paquetería.';
            }
            $db->execute($sqlBit, [
                $comentBit,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $infotraspaso[0]['SUCURSAL_IDDE'],
                $infotraspaso[0]['TRASPASO_DEALMACENID'],
                null
            ]);


            // Como cambiamos el estado, la transacción es implícita en la FirebirdConnection(true) o requerimos commit si usáramos begin. En el query original auto-commit (true).

            // 📱 Notificación WhatsApp a Admins (solo al enviar a revisión, no en avances)
            if (!$isAvance) {
                try {
                    $telAdmins = whatsapp::getTelefonosAdmins();
                    if (!empty($telAdmins)) {
                        $msg  = "📦 *Traspaso a Revisión*\n";
                        $msg .= "Folio: *" . $infotraspaso[0]['TRASPASO_FOLIO'] . "*\n";
                        $msg .= "De: " . $infotraspaso[0]['ALMACEN_NOMBREDE'] . " (" . $infotraspaso[0]['SUCURSAL_NOMBREDE'] . ")\n";
                        $msg .= "A: " . $infotraspaso[0]['ALMACEN_NOMBREA'] . " (" . $infotraspaso[0]['SUCURSAL_NOMBREA'] . ")\n";
                        $msg .= "Por favor revisa y autoriza en el sistema.";
                        whatsapp::enviarMultiple($telAdmins, $msg);
                    }
                } catch (Throwable $eWA) {
                    error_log("[WhatsApp] Error en notificación traspaso: " . $eWA->getMessage());
                }
            }

            echo ""; // Success

        } catch (Exception $e) {
            echo "Error al enviar a revisión: " . $e->getMessage();
        } finally {
            $db->close();
        }
    }
}
