<?php
class requerimientosmaterial
{

    private function db($autoCommit = true)
    {
        return new FirebirdConnection($autoCommit);
    }

    private function generarFolio($db = null)
    {
        $ownConn = ($db === null);
        if ($ownConn) {
            $db = $this->db(true);
        }

        $sql = "
            SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(REQMATERIAL_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0')
            || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2)) AS FOLIO
            FROM AMPAR_HIS_REQUERIMIENTOMATERIAL
        ";
        $res = $db->query($sql);
        $folio = ($res && $res != 0 && isset($res[0]['FOLIO'])) ? $res[0]['FOLIO'] : '00001-' . date('y');

        if ($ownConn) {
            $db->close();
        }

        return $folio;
    }

    public function getcatalogorequerimientos($almacenid = '', $status = '')
    {
        $db = $this->db(true);
        $sql = "
            SELECT
                RM.REQMATERIAL_ID ID,
                RM.REQMATERIAL_FOLIO FOLIO,
                RM.REQMATERIAL_FECHA FECHA,
                RM.REQMATERIAL_STATUS STATUS_ID,
                ST.STATUS_NOMBRE,
                ST.STATUS_COLOR,
                ALM.ALMACEN_NOMBRE,
                COALESCE((SELECT COUNT(*) FROM AMPAR_HIS_REQDET RMD WHERE RMD.REQMATERIALDET_REQUERIMIENTOID = RM.REQMATERIAL_ID), 0) ARTICULOS
            FROM AMPAR_HIS_REQUERIMIENTOMATERIAL RM
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = RM.REQMATERIAL_STATUS
            LEFT JOIN AMPAR_HIS_ALMACEN ALM ON ALM.ALMACEN_ID = RM.REQMATERIAL_ALMACENID
            WHERE 1 = 1
        ";

        if ($almacenid !== '') {
            $sql .= " AND RM.REQMATERIAL_ALMACENID = " . (int)$almacenid;
        }
        if ($status !== '') {
            $sql .= " AND RM.REQMATERIAL_STATUS IN (" . $status . ")";
        }

        if (!($GLOBALS['isAdmin'] ?? false)) {
            $sql .= " AND RM.REQMATERIAL_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
        }

        $sql .= " ORDER BY RM.REQMATERIAL_ID DESC";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    public function getrequerimientos($almacenid = '', $status = '')
    {
        return $this->getcatalogorequerimientos($almacenid, $status);
    }

    public function getrequerimientobyid($reqid)
    {
        $db = $this->db(true);
        $reqid = (int)$reqid;
        $sql = "
            SELECT
                RM.*, ST.STATUS_NOMBRE, ST.STATUS_COLOR,
                ALM.ALMACEN_NOMBRE, ALM.ALMACEN_FOLIO,
                RMD.REQMATERIALDET_ID,
                RMD.REQMATERIALDET_CANTIDAD,
                RMD.REQMATERIALDET_ARTICULOID,
                AR.NOMBRE ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO
            FROM AMPAR_HIS_REQUERIMIENTOMATERIAL RM
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = RM.REQMATERIAL_STATUS
            LEFT JOIN AMPAR_HIS_ALMACEN ALM ON ALM.ALMACEN_ID = RM.REQMATERIAL_ALMACENID
            LEFT JOIN AMPAR_HIS_REQDET RMD ON RMD.REQMATERIALDET_REQUERIMIENTOID = RM.REQMATERIAL_ID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = RMD.REQMATERIALDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE RM.REQMATERIAL_ID = {$reqid}
            ORDER BY RMD.REQMATERIALDET_ID
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    public function guardar($almacenId, $observaciones, $articulos, $usuarioId = null, $statusId = 1)
    {
        $db = $this->db(false);

        try {
            $almacenId = (int)$almacenId;
            $statusId = (int)$statusId;
            $usuarioId = $usuarioId !== null ? (int)$usuarioId : null;
            $observaciones = trim((string)$observaciones);

            if ($almacenId <= 0) {
                throw new Exception('Debes seleccionar un almacén.');
            }
            if (!is_array($articulos) || empty($articulos)) {
                throw new Exception('Debes agregar al menos un artículo.');
            }

            $almRes = $db->query("SELECT ALMACEN_SUCURSAL_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = ?", [$almacenId]);
            $folio = $this->generarFolio($db);

            $sql = "
                INSERT INTO AMPAR_HIS_REQUERIMIENTOMATERIAL
                (
                    REQMATERIAL_FECHA,
                    REQMATERIAL_FOLIO,
                    REQMATERIAL_STATUS,
                    REQMATERIAL_ALMACENID,
                    REQMATERIAL_OBSERVACIONES,
                    REQMATERIAL_USUARIOID
                )
                VALUES
                (
                    CURRENT_TIMESTAMP,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";
            $params = [$folio, $statusId, $almacenId, $observaciones, $usuarioId];

            $reqId = $db->executeconreturning($sql, 'REQMATERIAL_ID', $params);

            if (!$reqId) {
                throw new Exception('No se pudo crear el requerimiento.');
            }

            foreach ($articulos as $item) {
                $articuloId = (int)($item['articulo_id'] ?? 0);
                $cantidad = (float)($item['cantidad'] ?? 0);

                if ($articuloId <= 0 || $cantidad <= 0) {
                    continue;
                }

                $sqlDet = "
                    INSERT INTO AMPAR_HIS_REQDET
                    (
                        REQMATERIALDET_REQUERIMIENTOID,
                        REQMATERIALDET_ARTICULOID,
                        REQMATERIALDET_CANTIDAD
                    )
                    VALUES
                    (
                        ?, ?, ?
                    )
                ";
                $db->execute($sqlDet, [$reqId, $articuloId, $cantidad]);
            }

            $db->commit();
            $db->close();

            return [
                'reqmaterial_id' => $reqId,
                'folio' => $folio
            ];
        } catch (Throwable $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }

    public function actualizar($reqId, $almacenId, $observaciones, $articulos, $usuarioId = null, $statusId = 1)
    {
        $db = $this->db(false);

        try {
            $reqId = (int)$reqId;
            $almacenId = (int)$almacenId;
            $statusId = (int)$statusId;
            $usuarioId = $usuarioId !== null ? (int)$usuarioId : null;
            $observaciones = trim((string)$observaciones);

            if ($reqId <= 0) {
                throw new Exception('Requerimiento inválido.');
            }
            if ($almacenId <= 0) {
                throw new Exception('Debes seleccionar un almacén.');
            }
            if (!is_array($articulos) || empty($articulos)) {
                throw new Exception('Debes agregar al menos un artículo.');
            }

            // Validar que exista y esté en un estado editable (1 = Guardado, 6 = Rechazado)
            $res = $db->query("SELECT REQMATERIAL_FOLIO, REQMATERIAL_STATUS FROM AMPAR_HIS_REQUERIMIENTOMATERIAL WHERE REQMATERIAL_ID = ?", [$reqId]);
            if (empty($res)) {
                throw new Exception('Requerimiento no encontrado.');
            }
            $estadoActual = (int)$res[0]['REQMATERIAL_STATUS'];
            if ($estadoActual !== 1 && $estadoActual !== 6) {
                throw new Exception('Este requerimiento ya no se puede editar (Status: ' . $estadoActual . ').');
            }

            $folio = $res[0]['REQMATERIAL_FOLIO'];

            // Actualizar maestro
            $sql = "
                UPDATE AMPAR_HIS_REQUERIMIENTOMATERIAL SET
                    REQMATERIAL_STATUS = ?,
                    REQMATERIAL_ALMACENID = ?,
                    REQMATERIAL_OBSERVACIONES = ?
                WHERE REQMATERIAL_ID = ?
            ";
            $db->execute($sql, [$statusId, $almacenId, $observaciones, $reqId]);

            // Borrar detalle anterior
            $db->execute("DELETE FROM AMPAR_HIS_REQDET WHERE REQMATERIALDET_REQUERIMIENTOID = ?", [$reqId]);

            // Insertar nuevo detalle
            foreach ($articulos as $item) {
                $articuloId = (int)($item['articulo_id'] ?? 0);
                $cantidad = (float)($item['cantidad'] ?? 0);

                if ($articuloId <= 0 || $cantidad <= 0) {
                    continue;
                }

                $sqlDet = "
                    INSERT INTO AMPAR_HIS_REQDET
                    (
                        REQMATERIALDET_REQUERIMIENTOID,
                        REQMATERIALDET_ARTICULOID,
                        REQMATERIALDET_CANTIDAD
                    )
                    VALUES
                    (
                        ?, ?, ?
                    )
                ";
                $db->execute($sqlDet, [$reqId, $articuloId, $cantidad]);
            }

            $db->commit();
            $db->close();

            return [
                'reqmaterial_id' => $reqId,
                'folio' => $folio
            ];
        } catch (Throwable $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }

    /**
     * Actualiza el status de un requerimiento de material
     */
    public function actualizarStatus($reqid, $statusId)
    {
        $db = $this->db(true);
        $sql = "UPDATE AMPAR_HIS_REQUERIMIENTOMATERIAL SET REQMATERIAL_STATUS = ? WHERE REQMATERIAL_ID = ?";
        $success = $db->execute($sql, [(int)$statusId, (int)$reqid]);
        $db->close();
        return $success;
    }

    /**
     * Calcula la rotación de un artículo en un almacén (Salidas en los últimos 90 días)
     */
    private function calcularRotacion($db, $almacenId, $articuloId)
    {
        $sql = "
            SELECT COUNT(*) AS ROTACION
            FROM AMPAR_HIS_ESDET ED
            JOIN AMPAR_HIS_ENTRADASALIDA E ON E.ES_ID = ED.ESDET_ESID
            WHERE E.ES_ALMACENID = ?
              AND ED.ESDET_ARTICULOID = ?
              AND E.ES_FECHA >= DATEADD(-90 DAY TO CURRENT_DATE)
        ";
        try {
            $res = $db->query($sql, [$almacenId, $articuloId]);
            if (!empty($res)) {
                return (float)$res[0]['ROTACION'];
            }
        } catch (Exception $e) {}
        return 0.0;
    }

    /**
     * Busca stock disponible de los artículos requeridos en otros almacenes
     * respetando mínimos y eligiendo los lotes exactos según rotación y caducidad.
     * Devuelve UNA opción óptima combinada (un almacén por artículo).
     */
    public function obtenerSugerenciasAlmacenTraspaso($reqId)
    {
        $db = $this->db(true);
        $reqId = (int)$reqId;

        $reqRes = $db->query("SELECT REQMATERIAL_ALMACENID FROM AMPAR_HIS_REQUERIMIENTOMATERIAL WHERE REQMATERIAL_ID = ?", [$reqId]);
        if (empty($reqRes)) {
            $db->close();
            return [];
        }
        $almacen_destino_id = (int)$reqRes[0]['REQMATERIAL_ALMACENID'];

        $sqlArticulos = "
            SELECT REQMATERIALDET_ARTICULOID AS ARTICULO_ID, REQMATERIALDET_CANTIDAD AS CANTIDAD, AR.NOMBRE AS ARTICULO_NOMBRE
            FROM AMPAR_HIS_REQDET RD
            JOIN ARTICULOS AR ON AR.ARTICULO_ID = RD.REQMATERIALDET_ARTICULOID
            WHERE RD.REQMATERIALDET_REQUERIMIENTOID = ?
        ";
        $articulosReq = $db->query($sqlArticulos, [$reqId]);
        if (empty($articulosReq)) {
            $db->close();
            return [];
        }

        $opcionOptima = [
            'almacen_id'     => null,
            'almacen_nombre' => null,
            'articulos'      => []
        ];

        foreach ($articulosReq as $art) {
            $artId   = (int)$art['ARTICULO_ID'];
            $reqCant = (float)$art['CANTIDAD'];
            $artName = $art['ARTICULO_NOMBRE'];

            $rotacionDestino = $this->calcularRotacion($db, $almacen_destino_id, $artId);

            // Buscar todo el stock disponible lote a lote en otros almacenes
            $sqlStock = "
                SELECT
                    A.ALMACEN_ID,
                    A.ALMACEN_NOMBRE,
                    S.STOCK_ID,
                    S.STOCK_FOLIO,
                    S.STOCK_CADUCIDAD,
                    COALESCE((
                        SELECT M.MINIMO_CANTIDAD
                        FROM AMPAR_HIS_MINIMOS M
                        WHERE M.MINIMO_ALMACENID = A.ALMACEN_ID AND M.MINIMO_ARTICULOID = ?
                    ), 0.0) AS STOCK_MINIMO
                FROM AMPAR_HIS_STOCK S
                JOIN AMPAR_HIS_ALMACEN A ON S.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID
                WHERE S.STOCK_ARTICULOID = ?
                  AND S.STOCK_STOCKSTATUSID = 1
                  AND A.ALMACEN_ID <> ?
            ";
            $stockItems = $db->query($sqlStock, [$artId, $artId, $almacen_destino_id]);

            $stockAgrupadoPorAlmacen = [];

            if (!empty($stockItems)) {
                // Agrupar por almacén
                $conteoPorAlmacen = [];
                foreach ($stockItems as $st) {
                    $almId = $st['ALMACEN_ID'];
                    if (!isset($conteoPorAlmacen[$almId])) {
                        $conteoPorAlmacen[$almId] = ['count' => 0, 'minimo' => (float)$st['STOCK_MINIMO'], 'nombre' => $st['ALMACEN_NOMBRE'], 'items' => []];
                    }
                    $conteoPorAlmacen[$almId]['count']++;
                    $conteoPorAlmacen[$almId]['items'][] = $st;
                }

                // Para cada almacén calcular cuántos se pueden transferir sin romper mínimo
                foreach ($conteoPorAlmacen as $almId => $data) {
                    $transferibles = max(0, $data['count'] - (int)$data['minimo']);
                    if ($transferibles > 0) {
                        $rotacionOrigen = $this->calcularRotacion($db, $almId, $artId);

                        // Ordenar lotes por caducidad según lógica de rotación
                        $items = $data['items'];
                        usort($items, function ($a, $b) use ($rotacionOrigen, $rotacionDestino) {
                            $cadA  = empty($a['STOCK_CADUCIDAD']) ? '9999-12-31' : $a['STOCK_CADUCIDAD'];
                            $cadB  = empty($b['STOCK_CADUCIDAD']) ? '9999-12-31' : $b['STOCK_CADUCIDAD'];
                            $timeA = strtotime($cadA);
                            $timeB = strtotime($cadB);
                            // Si origen rota más: enviar los que caducan DESPUÉS (para que el origen consuma los más próximos)
                            // Si destino rota más: enviar los más próximos a caducar
                            return ($rotacionOrigen > $rotacionDestino) ? ($timeB - $timeA) : ($timeA - $timeB);
                        });

                        // Tomar solo la cantidad transferible y filtrar los que caducan en < 20 días
                        $itemsTransferibles = array_slice($items, 0, $transferibles);
                        $itemsFiltrados = [];
                        $now = time();
                        foreach ($itemsTransferibles as $it) {
                            if (!empty($it['STOCK_CADUCIDAD'])) {
                                $dias = (strtotime($it['STOCK_CADUCIDAD']) - $now) / 86400;
                                if ($dias < 20) continue; // No enviar si caduca muy pronto
                            }
                            $itemsFiltrados[] = $it;
                        }

                        if (count($itemsFiltrados) > 0) {
                            $stockAgrupadoPorAlmacen[] = [
                                'almacen_id'     => $almId,
                                'almacen_nombre' => $data['nombre'],
                                'items'          => $itemsFiltrados,
                                'disponible'     => count($itemsFiltrados)
                            ];
                        }
                    }
                }
            }

            // Elegir el almacén con mayor disponibilidad para este artículo
            usort($stockAgrupadoPorAlmacen, function ($a, $b) {
                return $b['disponible'] - $a['disponible'];
            });

            if (count($stockAgrupadoPorAlmacen) > 0) {
                $mejorAlmacen       = $stockAgrupadoPorAlmacen[0];
                $cantidadATransferir = min($reqCant, $mejorAlmacen['disponible']);
                $cantidadFaltanteOC  = max(0.0, $reqCant - $cantidadATransferir);

                $stockIdsSeleccionados = array_slice(array_column($mejorAlmacen['items'], 'STOCK_ID'), 0, (int)$cantidadATransferir);

                // Construir descripción de lotes con FOLIO
                $detallesLotes = [];
                $lotesUsados   = array_slice($mejorAlmacen['items'], 0, (int)$cantidadATransferir);
                foreach ($lotesUsados as $lu) {
                    $cad   = empty($lu['STOCK_CADUCIDAD']) ? 'N/A' : date('d/m/Y', strtotime($lu['STOCK_CADUCIDAD']));
                    $folio = !empty($lu['STOCK_FOLIO']) ? $lu['STOCK_FOLIO'] : ('ID:' . $lu['STOCK_ID']);

                    // Semáforo de caducidad
                    $colorCad = '#166534'; // verde
                    if (!empty($lu['STOCK_CADUCIDAD'])) {
                        $diasCad = (strtotime($lu['STOCK_CADUCIDAD']) - time()) / 86400;
                        if ($diasCad < 60)       $colorCad = '#b91c1c'; // rojo
                        elseif ($diasCad < 120)  $colorCad = '#b45309'; // naranja
                    }

                    $badge  = "<span style='display:inline-block; margin:2px; padding:3px 8px; border-radius:6px; background-color:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; font-size:0.75rem; white-space:nowrap;'>";
                    $badge .= "<strong style='color:#312e81;'>Folio: {$folio}</strong> | ";
                    $badge .= "<span style='color:{$colorCad};'>Cad: {$cad}</span>";
                    $badge .= "</span>";
                    $detallesLotes[] = $badge;
                }

                // Si todos los artículos provienen del mismo almacén, usarlo como almacén de la opción óptima
                if ($opcionOptima['almacen_id'] === null) {
                    $opcionOptima['almacen_id']     = $mejorAlmacen['almacen_id'];
                    $opcionOptima['almacen_nombre'] = $mejorAlmacen['almacen_nombre'];
                }

                $opcionOptima['articulos'][] = [
                    'articulo_id'         => $artId,
                    'articulo_nombre'     => $artName,
                    'cantidad_requerida'  => $reqCant,
                    'cantidad_a_transferir' => $cantidadATransferir,
                    'cantidad_faltante_oc'  => $cantidadFaltanteOC,
                    'almacen_origen_id'     => $mejorAlmacen['almacen_id'],
                    'almacen_origen_nombre' => $mejorAlmacen['almacen_nombre'],
                    'stock_ids'           => $stockIdsSeleccionados,
                    'lotes_desc'          => implode(" ", $detallesLotes)
                ];
            } else {
                // Sin stock en ningún almacén → todo a OC
                $opcionOptima['articulos'][] = [
                    'articulo_id'           => $artId,
                    'articulo_nombre'       => $artName,
                    'cantidad_requerida'    => $reqCant,
                    'cantidad_a_transferir' => 0,
                    'cantidad_faltante_oc'  => $reqCant,
                    'almacen_origen_id'     => 0,
                    'almacen_origen_nombre' => 'N/A',
                    'stock_ids'             => [],
                    'lotes_desc'            => ''
                ];
            }
        }

        $db->close();

        // Si hay artículos con traspaso, devolver como array con una entrada (compatibilidad con el frontend)
        $hayTraspaso = array_filter($opcionOptima['articulos'], fn($a) => $a['cantidad_a_transferir'] > 0);
        if (!empty($hayTraspaso)) {
            return [$opcionOptima];
        }
        return [];
    }

    /**
     * Obtiene el estado detallado de cumplimiento de un requerimiento (Traspasos y OCs vinculados)
     */
    public function obtenerCumplimientoDetallado($reqId)
    {
        $db = $this->db(true);
        $reqId = (int)$reqId;

        // 1. Obtener los articulos requeridos
        $sqlArticulos = "
            SELECT 
                RD.REQMATERIALDET_ID,
                RD.REQMATERIALDET_ARTICULOID AS ARTICULO_ID, 
                RD.REQMATERIALDET_CANTIDAD AS CANTIDAD_REQUERIDA, 
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO
            FROM AMPAR_HIS_REQDET RD
            JOIN ARTICULOS AR ON AR.ARTICULO_ID = RD.REQMATERIALDET_ARTICULOID
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID 
                FROM claves_articulos 
                WHERE ROL_CLAVE_ART_ID = 17
            ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE RD.REQMATERIALDET_REQUERIMIENTOID = ?
        ";
        $articulos = $db->query($sqlArticulos, [$reqId]);
        if (empty($articulos)) {
            $db->close();
            return [];
        }

        // 2. Obtener traspasos vinculados no cancelados
        $sqlTraspasos = "
            SELECT 
                T.TRASPASO_FOLIO, 
                T.TRASPASO_FECHACREACION,
                ST.STATUS_NOMBRE,
                ST.STATUS_COLOR,
                T.TRASPASO_STATUS,
                SD.STOCK_ARTICULOID AS ARTICULO_ID,
                COUNT(TD.TRASPASODET_ID) AS CANTIDAD
            FROM AMPAR_HIS_TRASPASODET TD
            JOIN AMPAR_HIS_STOCK SD ON SD.STOCK_ID = TD.TRASPASODET_STOCKID
            JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = TD.TRASPASODET_TRASPASOID
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = T.TRASPASO_STATUS
            WHERE T.TRASPASO_REQMATERIALID = ? AND T.TRASPASO_STATUS <> 5
            GROUP BY T.TRASPASO_FOLIO, T.TRASPASO_FECHACREACION, ST.STATUS_NOMBRE, ST.STATUS_COLOR, T.TRASPASO_STATUS, SD.STOCK_ARTICULOID
        ";
        $traspasos = $db->query($sqlTraspasos, [$reqId]);
        $traspasos = is_array($traspasos) ? $traspasos : [];

        // 3. Obtener ordenes de compra vinculadas no canceladas
        $sqlOCs = "
            SELECT 
                OC.OC_FOLIO, 
                OC.OC_FECHA,
                ST.STATUS_NOMBRE,
                ST.STATUS_COLOR,
                OC.OC_STATUS,
                OCD.OCDET_ARTICULOID AS ARTICULO_ID,
                SUM(OCD.OCDET_CANTIDAD) AS CANTIDAD
            FROM AMPAR_OCDET OCD
            JOIN AMPAR_OC OC ON OC.OC_ID = OCD.OCDET_OCID
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = OC.OC_STATUS
            WHERE (OC.OC_REQUERIMIENTOMATERIALID = ? OR OC.OC_ID IN (SELECT OCREQ_OCID FROM AMPAR_HIS_OC_REQ WHERE OCREQ_REQID = ?)) AND OC.OC_STATUS <> 5
            GROUP BY OC.OC_FOLIO, OC.OC_FECHA, ST.STATUS_NOMBRE, ST.STATUS_COLOR, OC.OC_STATUS, OCD.OCDET_ARTICULOID
        ";
        $ocs = $db->query($sqlOCs, [$reqId, $reqId]);
        $ocs = is_array($ocs) ? $ocs : [];

        $db->close();

        // 4. Mapear y agrupar la información
        $resultado = [];
        foreach ($articulos as $art) {
            $artId = (int)$art['ARTICULO_ID'];

            $movTraspasos = [];
            $totalTraspaso = 0;
            foreach ($traspasos as $t) {
                if ((int)$t['ARTICULO_ID'] === $artId) {
                    $movTraspasos[] = [
                        'folio' => $t['TRASPASO_FOLIO'],
                        'fecha' => $t['TRASPASO_FECHACREACION'],
                        'cantidad' => (float)$t['CANTIDAD'],
                        'status' => $t['STATUS_NOMBRE'],
                        'color' => $t['STATUS_COLOR'],
                        'status_id' => (int)$t['TRASPASO_STATUS']
                    ];
                    $totalTraspaso += (float)$t['CANTIDAD'];
                }
            }

            $movOCs = [];
            $totalOC = 0;
            foreach ($ocs as $o) {
                if ((int)$o['ARTICULO_ID'] === $artId) {
                    $movOCs[] = [
                        'folio' => $o['OC_FOLIO'],
                        'fecha' => $o['OC_FECHA'],
                        'cantidad' => (float)$o['CANTIDAD'],
                        'status' => $o['STATUS_NOMBRE'],
                        'color' => $o['STATUS_COLOR'],
                        'status_id' => (int)$o['OC_STATUS']
                    ];
                    $totalOC += (float)$o['CANTIDAD'];
                }
            }

            $resultado[] = [
                'articulo_id' => $artId,
                'articulo_nombre' => $art['ARTICULO_NOMBRE'] ?? '',
                'clave_articulo' => $art['CLAVE_ARTICULO'] ?? 'S/K',
                'cantidad_requerida' => (float)$art['CANTIDAD_REQUERIDA'],
                'cantidad_transferida' => $totalTraspaso,
                'cantidad_comprada' => $totalOC,
                'movimientos_traspasos' => $movTraspasos,
                'movimientos_ocs' => $movOCs
            ];
        }

        return $resultado;
    }

    /**
     * Recalcula automáticamente el status de un requerimiento y lo actualiza
     */
    public function actualizarStatusAutomatico($reqId)
    {
        $detalle = $this->obtenerCumplimientoDetallado($reqId);
        if (empty($detalle)) return false;

        $totalRequerido = 0;
        $totalSatisfecho = 0;
        $tieneVinculos = false;
        $todosLosVinculosFinalizados = true;

        foreach ($detalle as $item) {
            $totalRequerido += $item['cantidad_requerida'];
            // Lo satisfecho es lo que ya se traspasó o se compró
            $totalSatisfecho += min($item['cantidad_requerida'], $item['cantidad_transferida'] + $item['cantidad_comprada']);
            if ($item['cantidad_transferida'] > 0 || $item['cantidad_comprada'] > 0) {
                $tieneVinculos = true;
            }

            foreach ($item['movimientos_traspasos'] as $mt) {
                if ((int)$mt['status_id'] !== 3) {
                    $todosLosVinculosFinalizados = false;
                }
            }
            foreach ($item['movimientos_ocs'] as $mo) {
                if ((int)$mo['status_id'] !== 3) {
                    $todosLosVinculosFinalizados = false;
                }
            }
        }

        $nuevoStatus = 1; // Guardado

        $db = $this->db(true);
        $res = $db->query("SELECT REQMATERIAL_STATUS FROM AMPAR_HIS_REQUERIMIENTOMATERIAL WHERE REQMATERIAL_ID = ?", [$reqId]);
        $statusActual = !empty($res) ? (int)$res[0]['REQMATERIAL_STATUS'] : 1;

        if ($totalSatisfecho >= $totalRequerido && $totalRequerido > 0 && $todosLosVinculosFinalizados) {
            $nuevoStatus = 3; // Finalizado
        } elseif ($tieneVinculos) {
            $nuevoStatus = 2; // en Proceso
        } else {
            $nuevoStatus = ($statusActual == 19 || $statusActual == 2) ? $statusActual : 1;
        }

        if ($nuevoStatus !== $statusActual) {
            $db->execute("UPDATE AMPAR_HIS_REQUERIMIENTOMATERIAL SET REQMATERIAL_STATUS = ? WHERE REQMATERIAL_ID = ?", [$nuevoStatus, $reqId]);
        }
        $db->close();
        return $nuevoStatus;
    }
}
?>