<?php

class dashboardinventario
{
    function getAlmacenScopeCondition($alias, $almacenId)
    {
        $almacenSeleccionado = (int)$almacenId;

        return " AND (" . $alias . ".STOCK_ALMACENIDACTUAL = " . $almacenSeleccionado . " OR " . $alias . ".STOCK_ALMACENIDACTUAL IN (
            SELECT A.ALMACEN_ID
            FROM AMPAR_HIS_ALMACEN A
            WHERE A.ALMACEN_ALMACEN_MS = " . $almacenSeleccionado . "
              AND A.ALMACEN_TIPOALMACEN = 3
              AND A.DELETED_AT IS NULL
        ))";
    }

    function getChartData($db, $almacenSeleccionado, $stockRegistrado, $stockFisico, $unidadesObsoletas, $etapa6m, $etapa3m, $etapa2m, $etapa1s, $caducado, $totalArticulos = 0, $articulosObsoletos = 0)
    {
        $filtroEs = ($almacenSeleccionado === 'global') ? '' : " AND ES.ES_ALMACENID = " . (int)$almacenSeleccionado;
        $filtroStock = ($almacenSeleccionado === 'global') ? '' : $this->getAlmacenScopeCondition('ST', (int)$almacenSeleccionado);

        $filtroEsRem = str_replace("ES.ES_ALMACENID", "RE.REMISION_ALMACENID", $filtroEs);

        $sqlMovimientos = "
            SELECT
                EXTRACT(YEAR FROM FECHA) AS ANIO,
                EXTRACT(MONTH FROM FECHA) AS MES,
                SUM(ENTRADAS) AS ENTRADAS,
                SUM(SALIDAS) AS SALIDAS,
                SUM(SOLICITUDES) AS SOLICITUDES,
                SUM(SIN_STOCK) AS SIN_STOCK
            FROM (
                SELECT CAST(ES.ES_FECHA AS DATE) AS FECHA, 
                       CASE WHEN ES.ES_TIPO = 'E' AND ES.ES_STATUS = 3 THEN 1 ELSE 0 END AS ENTRADAS,
                       0 AS SALIDAS, 0 AS SOLICITUDES, 0 AS SIN_STOCK
                FROM AMPAR_HIS_ESDET ED
                JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID
                WHERE CAST(ES.ES_FECHA AS DATE) >= DATEADD(-180 DAY TO CURRENT_DATE) AND ES.ES_TIPO = 'E'
                " . $filtroEs . "
                
                UNION ALL
                
                SELECT CAST(RE.REMISION_FECHA AS DATE) AS FECHA, 
                       0 AS ENTRADAS,
                       CASE WHEN RE.REMISION_STATUS = 3 THEN 1 ELSE 0 END AS SALIDAS,
                       1 AS SOLICITUDES,
                       0 AS SIN_STOCK
                FROM AMPAR_HIS_REMISIONESARTICULOS RA
                JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                WHERE CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-180 DAY TO CURRENT_DATE)
                " . $filtroEsRem . "
            )
            GROUP BY EXTRACT(YEAR FROM FECHA), EXTRACT(MONTH FROM FECHA)
        ";

        $sqlCaducadosMensual = "
            SELECT
                EXTRACT(YEAR FROM ST.STOCK_CADUCIDAD) AS ANIO,
                EXTRACT(MONTH FROM ST.STOCK_CADUCIDAD) AS MES,
                COUNT(*) AS CADUCADOS
            FROM AMPAR_HIS_STOCK ST
            WHERE ST.STOCK_STOCKSTATUSID IN (1,2)
            AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND ST.STOCK_CADUCIDAD < CURRENT_DATE
            AND ST.STOCK_CADUCIDAD >= DATEADD(-180 DAY TO CURRENT_DATE)
            " . $filtroStock . "
            GROUP BY EXTRACT(YEAR FROM ST.STOCK_CADUCIDAD), EXTRACT(MONTH FROM ST.STOCK_CADUCIDAD)
        ";

        $movimientos = $db->query($sqlMovimientos);
        $caducadosMensual = $db->query($sqlCaducadosMensual);

        $movByMonth = [];
        if (is_array($movimientos)) {
            foreach ($movimientos as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ANIO'], (int)$row['MES']);
                $movByMonth[$key] = [
                    'entradas' => (int)($row['ENTRADAS'] ?? 0),
                    'salidas' => (int)($row['SALIDAS'] ?? 0),
                    'solicitudes' => (int)($row['SOLICITUDES'] ?? 0),
                    'sin_stock' => (int)($row['SIN_STOCK'] ?? 0),
                ];
            }
        }

        $cadByMonth = [];
        if (is_array($caducadosMensual)) {
            foreach ($caducadosMensual as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ANIO'], (int)$row['MES']);
                $cadByMonth[$key] = (int)($row['CADUCADOS'] ?? 0);
            }
        }

        // ===== IRA (Inventory Record Accuracy) a partir de Escaneos =====
        $sqlEscaneosIRA = "
            SELECT
                EXTRACT(YEAR FROM h.ESCANEO_FECHA) AS ANIO,
                EXTRACT(MONTH FROM h.ESCANEO_FECHA) AS MES,
                h.ESCANEO_DETALLES AS DETALLES_JSON
            FROM AMPAR_HIS_ESCANEO h
        ";
        if ($almacenSeleccionado !== 'global') {
            $sqlEscaneosIRA .= "
            JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_FOLIO = h.ESCANEO_FOLIO 
            WHERE CAST(h.ESCANEO_FECHA AS DATE) >= DATEADD(-180 DAY TO CURRENT_DATE)
            AND (a.ALMACEN_ID = " . (int)$almacenSeleccionado . " OR a.ALMACEN_ALMACEN_MS = " . (int)$almacenSeleccionado . ")
            ";
        } else {
            $sqlEscaneosIRA .= "
            WHERE CAST(h.ESCANEO_FECHA AS DATE) >= DATEADD(-180 DAY TO CURRENT_DATE)
            ";
        }
        
        $escaneosDb = $db->query($sqlEscaneosIRA);
        $iraByMonth = [];
        
        if (is_array($escaneosDb)) {
            foreach ($escaneosDb as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ANIO'], (int)$row['MES']);
                if (!isset($iraByMonth[$key])) {
                    $iraByMonth[$key] = ['total' => 0, 'correctos' => 0];
                }
                
                $jsonStr = $row['DETALLES_JSON'] ?? '';
                if ($jsonStr) {
                    $detalles = json_decode($jsonStr, true);
                    if (is_array($detalles)) {
                        $cCount = count($detalles['correctos'] ?? []);
                        $fCount = count($detalles['faltantes'] ?? []);
                        $eCount = count($detalles['extras'] ?? $detalles['sobrantes'] ?? []);
                        
                        $iraByMonth[$key]['correctos'] += $cCount;
                        $iraByMonth[$key]['total'] += ($cCount + $fCount + $eCount);
                    }
                }
            }
        }

        $monthNames = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $labels = [];
        $entradas = [];
        $salidas = [];
        $solicitudes = [];
        $sinStock = [];
        $caducados = [];
        $roturaSerie = [];
        $rotacionSerie = [];
        $diasInvSerie = [];
        $precisionSerie = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = strtotime('-' . $i . ' month');
            $anio = (int)date('Y', $fecha);
            $mes = (int)date('n', $fecha);
            $key = sprintf('%04d-%02d', $anio, $mes);

            $mov = $movByMonth[$key] ?? ['entradas' => 0, 'salidas' => 0, 'solicitudes' => 0, 'sin_stock' => 0];
            $ent = (int)$mov['entradas'];
            $sal = (int)$mov['salidas'];
            $sol = (int)$mov['solicitudes'];
            $sin = (int)$mov['sin_stock'];
            $cad = (int)($cadByMonth[$key] ?? 0);

            $labels[] = $monthNames[$mes] ?? (string)$mes;
            $entradas[] = $ent;
            $salidas[] = $sal;
            $solicitudes[] = $sol;
            $sinStock[] = $sin;
            $caducados[] = $cad;

            $roturaSerie[] = $sol > 0 ? round(($sin / $sol) * 100, 1) : 0;
            $rotacionSerie[] = $stockRegistrado > 0 ? round($sal / $stockRegistrado, 3) : 0;
            $diasInvSerie[] = $sal > 0 ? round($stockFisico / max($sal / 30, 1), 1) : $stockFisico;
            
            // Calculo de IRA
            $iraData = $iraByMonth[$key] ?? ['total' => 0, 'correctos' => 0];
            if ($iraData['total'] > 0) {
                $precisionSerie[] = round(($iraData['correctos'] / $iraData['total']) * 100, 1);
            } else {
                $precisionSerie[] = 100; // Si no hay escaneos ese mes, 100%
            }
        }

        $articulosSanos = max($totalArticulos - $articulosObsoletos, 0);

        return [
            'labels' => $labels,
            'entradas' => $entradas,
            'salidas' => $salidas,
            'solicitudes' => $solicitudes,
            'sinStock' => $sinStock,
            'caducadosMensual' => $caducados,
            'sparklines' => [
                'rotacion' => $rotacionSerie,
                'diasInventario' => $diasInvSerie,
                'precision' => $precisionSerie,
                'obsoleto' => $caducados,
                'rotura' => $roturaSerie,
                'stock' => $entradas,
            ],
            'donutSalud' => [
                'stockSano' => $articulosSanos,
                'obsoleto' => $articulosObsoletos,
            ],
            'funnelCaducidad' => [
                '6m' => (int)$etapa6m,
                '3m' => (int)$etapa3m,
                '2m' => (int)$etapa2m,
                '1s' => (int)$etapa1s,
                'caducado' => (int)$caducado,
            ],
        ];
    }

    function evaluarAccionInteligenteStock($db, $stockId)
    {
        $stockId = (int)$stockId;
        if ($stockId <= 0) {
            return ['ok' => false, 'message' => 'Stock ID inválido.'];
        }

        try {
            // VALIDACIÓN PREVIA: Verificar si este stock ya está en un traspaso en progreso
            $sqlTraspaso = "
                SELECT FIRST 1 TD.TRASPASODET_ID
                FROM AMPAR_HIS_TRASPASODET TD
                LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = TD.TRASPASODET_TRASPASOID
                WHERE TD.TRASPASODET_STOCKID = ?
                  AND COALESCE(T.TRASPASO_STATUS, 0) IN (1, 2)
            ";
            $traspasoExistente = $db->query($sqlTraspaso, [$stockId]);
            if ($traspasoExistente && count($traspasoExistente) > 0) {
                return ['ok' => false, 'message' => 'Este artículo ya está en traspaso.'];
            }

            $sqlA = "
                SELECT ST.STOCK_ARTICULOID, ST.STOCK_CADUCIDAD, ST.STOCK_ALMACENIDACTUAL,
                       A.ALMACEN_ALMACEN_MS, A.ALMACEN_TIPOALMACEN, A.ALMACEN_NOMBRE,
                       AR.NOMBRE AS ARTICULO_NOMBRE
                FROM AMPAR_HIS_STOCK ST
                LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
                LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                WHERE ST.STOCK_ID = ? AND ST.STOCK_STOCKSTATUSID = 1
            ";

            $itemA = $db->query($sqlA, [$stockId]);
            if (!$itemA || count($itemA) === 0) {
                return ['ok' => false, 'message' => 'El artículo ya no se encuentra físicamente en el almacén.'];
            }

            $itemA = $itemA[0];
            if ((int)($itemA['ALMACEN_TIPOALMACEN'] ?? 0) === 3) {
                return ['ok' => false, 'message' => 'La acción inteligente solo aplica para artículos que no están en maleta.'];
            }

            $articuloId = (int)$itemA['STOCK_ARTICULOID'];
            $almacenPadreId = $itemA['ALMACEN_ALMACEN_MS'] ? $itemA['ALMACEN_ALMACEN_MS'] : $itemA['STOCK_ALMACENIDACTUAL'];
            $caducidadA = $itemA['STOCK_CADUCIDAD'];
            $nombArticulo = $itemA['ARTICULO_NOMBRE'];

            $sqlFaltante = "
                SELECT M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID,
                       TMD.TIPOMALETADET_CANTIDADSUGERIDA,
                       (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S
                        WHERE S.STOCK_ALMACENIDACTUAL = M.ALMACEN_ID
                          AND S.STOCK_ARTICULOID = ?
                          AND S.STOCK_STOCKSTATUSID IN (1, 2)) AS ACTUAL
                FROM AMPAR_HIS_ALMACEN M
                JOIN AMPAR_HIS_TIPOMALETADET TMD ON TMD.TIPOMALETADET_TIPOMALETAID = M.ALMACEN_TIPOMALETAID
                WHERE M.ALMACEN_TIPOALMACEN = 3
                  AND M.ALMACEN_ALMACEN_MS = ?
                  AND M.DELETED_AT IS NULL
                  AND TMD.TIPOMALETADET_ARTICULOID = ?
            ";
            $maletas = $db->query($sqlFaltante, [$articuloId, $almacenPadreId, $articuloId]) ?: [];

            foreach ($maletas as $m) {
                if ((int)($m['ACTUAL'] ?? 0) < (int)($m['TIPOMALETADET_CANTIDADSUGERIDA'] ?? 0)) {
                    return [
                        'ok' => true,
                        'suggestion' => 'transfer',
                        'message' => "La maleta <strong>{$m['MALETA_NOMBRE']}</strong> tiene un faltante del artículo {$nombArticulo}.",
                        'data' => [
                            'target_maleta_id' => (int)$m['MALETA_ID']
                        ]
                    ];
                }
            }

            $sqlSwap = "
                SELECT FIRST 1 S.STOCK_ID AS SWAP_STOCK_ID, S.STOCK_CADUCIDAD AS SWAP_CADUCIDAD,
                       M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID
                FROM AMPAR_HIS_STOCK S
                JOIN AMPAR_HIS_ALMACEN M ON M.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                WHERE M.ALMACEN_TIPOALMACEN = 3
                  AND M.ALMACEN_ALMACEN_MS = ?
                  AND S.STOCK_ARTICULOID = ?
                  AND S.STOCK_STOCKSTATUSID = 1
                  AND S.STOCK_CADUCIDAD > ?
                ORDER BY S.STOCK_CADUCIDAD DESC
            ";
            $swapResult = $db->query($sqlSwap, [$almacenPadreId, $articuloId, $caducidadA]);

            if ($swapResult && count($swapResult) > 0) {
                $swapData = $swapResult[0];
                return [
                    'ok' => true,
                    'suggestion' => 'swap',
                    'message' => "La maleta <strong>{$swapData['MALETA_NOMBRE']}</strong> ya tiene el artículo {$nombArticulo}.",
                    'data' => [
                        'target_maleta_id' => (int)$swapData['MALETA_ID'],
                        'swap_stock_id' => (int)$swapData['SWAP_STOCK_ID']
                    ]
                ];
            }

            return ['ok' => false, 'message' => 'No se encontraron maletas que necesiten este artículo o que tengan uno con mayor caducidad.'];

            // Cerramos el try de forma segura con un catch en lugar del finally
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Ocurrió un error en la evaluación: ' . $e->getMessage()];
        }
    }

    function puedeAccionInteligenteStock($db, $stockId)
    {
        static $cache = [];
        $stockId = (int)$stockId;

        if ($stockId <= 0) {
            return false;
        }

        if (array_key_exists($stockId, $cache)) {
            return $cache[$stockId];
        }

        $resultado = $this->evaluarAccionInteligenteStock($db, $stockId);
        $cache[$stockId] = !empty($resultado['ok']);

        return $cache[$stockId];
    }

    function getDashboardData($db, $periodoDias = 90, $limiteCaducidad = 8, $almacenId = 'global')
    {
        $periodoDias = (int)$periodoDias;
        if (!in_array($periodoDias, [30, 60, 90, 180, 365], true)) {
            $periodoDias = 90;
        }

        $limiteCaducidad = (int)$limiteCaducidad;
        if ($limiteCaducidad <= 0) {
            $limiteCaducidad = 8;
        }

        $almacenSeleccionado = 'global';
        if ($almacenId !== 'global' && $almacenId !== '' && $almacenId !== null) {
            $almacenSeleccionado = (int)$almacenId;
            if ($almacenSeleccionado <= 0) {
                $almacenSeleccionado = 'global';
            }
        }

        $filtroStock = ($almacenSeleccionado === 'global') ? '' : $this->getAlmacenScopeCondition('ST', $almacenSeleccionado);
        $filtroEs = ($almacenSeleccionado === 'global') ? '' : " AND ES.ES_ALMACENID = " . $almacenSeleccionado;

        $filtroEsRem = str_replace("ES.ES_ALMACENID", "RE.REMISION_ALMACENID", $filtroEs);

        $sqlIndicadores = "
            SELECT
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK ST WHERE ST.STOCK_STOCKSTATUSID IN (1,2) " . $filtroStock . ") AS STOCK_REGISTRADO,
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK ST WHERE ST.STOCK_STOCKSTATUSID = 1 " . $filtroStock . ") AS STOCK_FISICO,
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK ST WHERE ST.STOCK_STOCKSTATUSID = 2 " . $filtroStock . ") AS STOCK_TRANSITO,
                (SELECT COUNT(DISTINCT ST.STOCK_ARTICULOID) FROM AMPAR_HIS_STOCK ST WHERE ST.STOCK_STOCKSTATUSID IN (1,2) " . $filtroStock . ") AS TOTAL_ARTICULOS,

                (SELECT COUNT(*)
                 FROM AMPAR_HIS_REMISIONESARTICULOS RA
                 JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                 WHERE RE.REMISION_STATUS = 3
                 AND CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-" . $periodoDias . " DAY TO CURRENT_DATE)
                 " . $filtroEsRem . "
                ) AS SALIDAS_PERIODO,

                (SELECT COUNT(*)
                 FROM AMPAR_HIS_REMISIONESARTICULOS RA
                 JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                 WHERE CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-" . $periodoDias . " DAY TO CURRENT_DATE)
                 " . $filtroEsRem . "
                ) AS SOLICITUDES_PERIODO,

                (SELECT COUNT(*)
                 FROM AMPAR_HIS_ESDET ED
                 LEFT JOIN AMPAR_HIS_ES ES ON ES.ES_ID = ED.ESDET_ESID
                 WHERE ES.ES_TIPO = 'S'
                 AND ES.ES_FECHA >= DATEADD(-" . $periodoDias . " DAY TO CURRENT_DATE)
                 AND (ED.ESDET_STOCKIDSALIDA IS NULL OR ED.ESDET_STOCKIDSALIDA = 0)
                 " . $filtroEs . "
                ) AS SOLICITUDES_SIN_STOCK,

                (SELECT COUNT(*)
                 FROM AMPAR_HIS_STOCK ST
                  WHERE ST.STOCK_STOCKSTATUSID = 1
                      " . $filtroStock . "
                 AND NOT EXISTS (
                    SELECT 1
                    FROM AMPAR_HIS_REMISIONESARTICULOS RA
                    JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                    JOIN AMPAR_HIS_STOCK S2 ON S2.STOCK_ID = RA.REMISIONARTICULO_STOCKID
                    WHERE S2.STOCK_ARTICULOID = ST.STOCK_ARTICULOID
                    AND RE.REMISION_STATUS = 3
                    AND CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-" . $periodoDias . " DAY TO CURRENT_DATE)
                          " . $filtroEsRem . "
                 )
                ) AS UNIDADES_OBSOLETAS,

                (SELECT COUNT(DISTINCT ST.STOCK_ARTICULOID)
                 FROM AMPAR_HIS_STOCK ST
                  WHERE ST.STOCK_STOCKSTATUSID = 1
                      " . $filtroStock . "
                 AND NOT EXISTS (
                    SELECT 1
                    FROM AMPAR_HIS_REMISIONESARTICULOS RA
                    JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
                    JOIN AMPAR_HIS_STOCK S2 ON S2.STOCK_ID = RA.REMISIONARTICULO_STOCKID
                    WHERE S2.STOCK_ARTICULOID = ST.STOCK_ARTICULOID
                    AND RE.REMISION_STATUS = 3
                    AND CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-" . $periodoDias . " DAY TO CURRENT_DATE)
                          " . $filtroEsRem . "
                 )
                ) AS ARTICULOS_OBSOLETOS,

                (SELECT SUM(CASE WHEN DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 90 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 180 THEN 1 ELSE 0 END)
                 FROM AMPAR_HIS_STOCK ST
                 WHERE ST.STOCK_STOCKSTATUSID = 1
                 AND ST.STOCK_CADUCIDAD IS NOT NULL
                 " . $filtroStock . "
                ) AS ETAPA_6M,

                (SELECT SUM(CASE WHEN DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 60
                                 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 90 THEN 1 ELSE 0 END)
                 FROM AMPAR_HIS_STOCK ST
                 WHERE ST.STOCK_STOCKSTATUSID = 1
                 AND ST.STOCK_CADUCIDAD IS NOT NULL
                 " . $filtroStock . "
                ) AS ETAPA_3M,

                (SELECT SUM(CASE WHEN DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 7
                                 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 60 THEN 1 ELSE 0 END)
                 FROM AMPAR_HIS_STOCK ST
                 WHERE ST.STOCK_STOCKSTATUSID = 1
                 AND ST.STOCK_CADUCIDAD IS NOT NULL
                 " . $filtroStock . "
                ) AS ETAPA_2M,

                (SELECT SUM(CASE WHEN DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 0
                                 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 7 THEN 1 ELSE 0 END)
                 FROM AMPAR_HIS_STOCK ST
                 WHERE ST.STOCK_STOCKSTATUSID = 1
                 AND ST.STOCK_CADUCIDAD IS NOT NULL
                 " . $filtroStock . "
                ) AS ETAPA_1S,

                (SELECT SUM(CASE WHEN DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 0 THEN 1 ELSE 0 END)
                 FROM AMPAR_HIS_STOCK ST
                 WHERE ST.STOCK_STOCKSTATUSID = 1
                 AND ST.STOCK_CADUCIDAD IS NOT NULL
                 " . $filtroStock . "
                ) AS CADUCADO
            FROM RDB\$DATABASE
        ";

        $indicadoresData = $db->query($sqlIndicadores);
        $indicadores = $indicadoresData[0] ?? [];

        $stockRegistrado = (int)($indicadores['STOCK_REGISTRADO'] ?? 0);
        $stockFisico = (int)($indicadores['STOCK_FISICO'] ?? 0);
        $stockTransito = (int)($indicadores['STOCK_TRANSITO'] ?? 0);
        $salidasPeriodo = (int)($indicadores['SALIDAS_PERIODO'] ?? 0);
        $solicitudesPeriodo = (int)($indicadores['SOLICITUDES_PERIODO'] ?? 0);
        $solicitudesSinStock = (int)($indicadores['SOLICITUDES_SIN_STOCK'] ?? 0);
        $unidadesObsoletas = (int)($indicadores['UNIDADES_OBSOLETAS'] ?? 0);
        $articulosObsoletos = (int)($indicadores['ARTICULOS_OBSOLETOS'] ?? 0);
        $totalArticulos = (int)($indicadores['TOTAL_ARTICULOS'] ?? 0);

        $etapa6m = (int)($indicadores['ETAPA_6M'] ?? 0);
        $etapa3m = (int)($indicadores['ETAPA_3M'] ?? 0);
        $etapa2m = (int)($indicadores['ETAPA_2M'] ?? 0);
        $etapa1s = (int)($indicadores['ETAPA_1S'] ?? 0);
        $caducado = (int)($indicadores['CADUCADO'] ?? 0);

        $sqlCaducadoArticulos = "
            SELECT COUNT(DISTINCT ST.STOCK_ARTICULOID) AS CADUCADO_ARTICULOS
            FROM AMPAR_HIS_STOCK ST
            WHERE ST.STOCK_STOCKSTATUSID = 1
            AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND ST.STOCK_CADUCIDAD < CURRENT_DATE
            " . $filtroStock . "
        ";
        $caducadoArticulosData = $db->query($sqlCaducadoArticulos);
        $caducadoArticulos = (int)($caducadoArticulosData[0]['CADUCADO_ARTICULOS'] ?? 0);

        $inventarioPromedio = max($stockRegistrado, 1);
        $tasaRotacion = $inventarioPromedio > 0 ? ($salidasPeriodo / $inventarioPromedio) : 0;

        // Días de Inventario: ventana fija de 90 días
        $sqlSalidasFijas = "
            SELECT COUNT(*) AS SALIDAS_90D
            FROM AMPAR_HIS_REMISIONESARTICULOS RA
            JOIN AMPAR_HIS_REMISIONES RE ON RE.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
            WHERE RE.REMISION_STATUS = 3
            AND CAST(RE.REMISION_FECHA AS DATE) >= DATEADD(-90 DAY TO CURRENT_DATE)
            " . $filtroEsRem . "
        ";
        $salidasFijasData = $db->query($sqlSalidasFijas);
        $salidas90d = (int)($salidasFijasData[0]['SALIDAS_90D'] ?? 0);
        $consumoDiario = $salidas90d / 90;
        $diasInventario = $consumoDiario > 0 ? ($stockFisico / $consumoDiario) : 0;

        $precisionRegistro = $stockRegistrado > 0 ? (($stockFisico / $stockRegistrado) * 100) : 0;
        $roturaStock = $solicitudesPeriodo > 0 ? (($solicitudesSinStock / $solicitudesPeriodo) * 100) : 0;

        $sqlTopCaducidad = "
            SELECT FIRST " . $limiteCaducidad . "
                ST.STOCK_ARTICULOID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO AS SKU,
                COUNT(*) AS CANTIDAD,
                MIN(ST.STOCK_CADUCIDAD) AS PROXIMA_CADUCIDAD,
                DATEDIFF(DAY, CURRENT_DATE, MIN(ST.STOCK_CADUCIDAD)) AS DIAS_RESTANTES,
                LIST(ST.STOCK_ID || '|' || COALESCE(AL.ALMACEN_NOMBRE, 'SIN ALMACÉN') || '|' || COALESCE(ST.STOCK_FOLIO, 'S/F') || '|' || COALESCE(ST.STOCK_LOTE, 'S/L'), ',') AS STOCK_IDS,
                LIST(CASE WHEN COALESCE(AL.ALMACEN_TIPOALMACEN, 0) <> 3 THEN ST.STOCK_ID || '|' || COALESCE(AL.ALMACEN_NOMBRE, 'SIN ALMACÉN') || '|' || COALESCE(ST.STOCK_FOLIO, 'S/F') || '|' || COALESCE(ST.STOCK_LOTE, 'S/L') END, ',') AS STOCK_IDS_ACCIONABLES,
                MIN(CASE WHEN COALESCE(AL.ALMACEN_TIPOALMACEN, 0) <> 3 THEN ST.STOCK_ID END) AS STOCK_ID_ACCIONABLE
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            WHERE ST.STOCK_STOCKSTATUSID = 1
            AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND ST.STOCK_CADUCIDAD >= CURRENT_DATE
            AND ST.STOCK_CADUCIDAD <= DATEADD(" . $periodoDias . " DAY TO CURRENT_DATE)
            " . $filtroStock . "
            GROUP BY ST.STOCK_ARTICULOID, AR.NOMBRE, X.CLAVE_ARTICULO, CAST(ST.STOCK_CADUCIDAD AS DATE)
            ORDER BY MIN(ST.STOCK_CADUCIDAD) ASC, AR.NOMBRE ASC
        ";

        $topCaducidad = $db->query($sqlTopCaducidad);

        if (is_array($topCaducidad)) {
            foreach ($topCaducidad as &$rowTop) {
                $rowTop['PUEDE_ACCION'] = false;

                $stockIds = [];
                $stockIdsAccionables = [];

                $stockIdsRaw = trim((string)($rowTop['STOCK_IDS'] ?? ''));
                if ($stockIdsRaw !== '') {
                    foreach (explode(',', $stockIdsRaw) as $stockRow) {
                        $stockParts = explode('|', $stockRow);
                        $stockId = (int)($stockParts[0] ?? 0);
                        if ($stockId > 0) {
                            $stockIds[] = $stockRow;
                        }
                    }
                }

                foreach ($stockIds as $stockRow) {
                    $stockParts = explode('|', $stockRow);
                    $stockId = (int)($stockParts[0] ?? 0);
                    if ($stockId <= 0) {
                        continue;
                    }

                    $previewAccion = $this->evaluarAccionInteligenteStock($db, $stockId);
                    if (!empty($previewAccion['ok'])) {
                        $stockIdsAccionables[] = $stockRow;
                    }
                }

                $rowTop['STOCK_IDS_ACCIONABLES'] = implode(',', $stockIdsAccionables);
                $rowTop['PUEDE_ACCION'] = !empty($stockIdsAccionables);
            }
            unset($rowTop);
        }

        $sqlDetalleCaducados = "
            SELECT
                ST.STOCK_ID AS INVDETID,
                ST.STOCK_FECHA,
                ST.STOCK_LOTE AS LOTE,
                ST.STOCK_CADUCIDAD AS CADUCIDAD,
                ST.STOCK_SERIE AS SERIE,
                ST.STOCK_ALMACENIDACTUAL AS ALMACEN_ID,
                AL.ALMACEN_NOMBRE,
                AR.ARTICULO_ID AS ID,
                AR.NOMBRE,
                X.CLAVE_ARTICULO
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            WHERE ST.STOCK_STOCKSTATUSID = 1
            AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND ST.STOCK_CADUCIDAD < CURRENT_DATE
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC, AR.NOMBRE ASC
        ";
        $detalleCaducados = $db->query($sqlDetalleCaducados);

        $chartData = $this->getChartData(
            $db,
            $almacenSeleccionado,
            $stockRegistrado,
            $stockFisico,
            $unidadesObsoletas,
            $etapa6m,
            $etapa3m,
            $etapa2m,
            $etapa1s,
            $caducado,
            $totalArticulos,
            $articulosObsoletos
        );

        return [
            'periodoDias' => $periodoDias,
            'almacenId' => $almacenSeleccionado,
            'stockRegistrado' => $stockRegistrado,
            'stockFisico' => $stockFisico,
            'stockTransito' => $stockTransito,
            'salidasPeriodo' => $salidasPeriodo,
            'solicitudesPeriodo' => $solicitudesPeriodo,
            'solicitudesSinStock' => $solicitudesSinStock,
            'unidadesObsoletas' => $unidadesObsoletas,
            'articulosObsoletos' => $articulosObsoletos,
            'totalArticulos' => $totalArticulos,
            'etapa6m' => $etapa6m,
            'etapa3m' => $etapa3m,
            'etapa2m' => $etapa2m,
            'etapa1s' => $etapa1s,
            'caducado' => $caducado,
            'caducadoArticulos' => $caducadoArticulos,
            'tasaRotacion' => $tasaRotacion,
            'diasInventario' => $diasInventario,
            'precisionRegistro' => $precisionRegistro,
            'roturaStock' => $roturaStock,
            'topCaducidad' => $topCaducidad,
            'detalleCaducados' => $detalleCaducados,
            'chartData' => $chartData,
        ];
    }

    function getArticulosPorCaducidad($db, $almacenId = 'global')
    {
        $almacenSeleccionado = 'global';
        if ($almacenId !== 'global' && $almacenId !== '' && $almacenId !== null) {
            $almacenSeleccionado = (int)$almacenId;
            if ($almacenSeleccionado <= 0) {
                $almacenSeleccionado = 'global';
            }
        }

        $filtroStock = ($almacenSeleccionado === 'global') ? '' : $this->getAlmacenScopeCondition('ST', $almacenSeleccionado);

        // Obtener artículos de 6 meses (> 90 y <= 180 días)
        $sql6m = "
            SELECT
                ST.STOCK_ID, ST.STOCK_FOLIO AS FOLIO, ST.STOCK_CADUCIDAD AS CADUCIDAD, ST.STOCK_LOTE AS LOTE, AR.NOMBRE, AL.ALMACEN_NOMBRE, AL.ALMACEN_ID, AL.ALMACEN_TIPOALMACEN, AL.ALMACEN_ALMACEN_MS, PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                COALESCE((SELECT CLAVE_ARTICULO FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = AR.ARTICULO_ID AND ROL_CLAVE_ART_ID = 17), '') AS CLAVE_ARTICULO,
                DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) AS DIAS_RESTANTES, '6m' AS ETAPA
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
            WHERE ST.STOCK_STOCKSTATUSID = 1 AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 90 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 180
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC
        ";

        $sql3m = "
            SELECT
                ST.STOCK_ID, ST.STOCK_FOLIO AS FOLIO, ST.STOCK_CADUCIDAD AS CADUCIDAD, ST.STOCK_LOTE AS LOTE, AR.NOMBRE, AL.ALMACEN_NOMBRE, AL.ALMACEN_ID, AL.ALMACEN_TIPOALMACEN, AL.ALMACEN_ALMACEN_MS, PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                COALESCE((SELECT CLAVE_ARTICULO FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = AR.ARTICULO_ID AND ROL_CLAVE_ART_ID = 17), '') AS CLAVE_ARTICULO,
                DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) AS DIAS_RESTANTES, '3m' AS ETAPA
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
            WHERE ST.STOCK_STOCKSTATUSID = 1 AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 60 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 90
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC
        ";

        $sql2m = "
            SELECT
                ST.STOCK_ID, ST.STOCK_FOLIO AS FOLIO, ST.STOCK_CADUCIDAD AS CADUCIDAD, ST.STOCK_LOTE AS LOTE, AR.NOMBRE, AL.ALMACEN_NOMBRE, AL.ALMACEN_ID, AL.ALMACEN_TIPOALMACEN, AL.ALMACEN_ALMACEN_MS, PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                COALESCE((SELECT CLAVE_ARTICULO FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = AR.ARTICULO_ID AND ROL_CLAVE_ART_ID = 17), '') AS CLAVE_ARTICULO,
                DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) AS DIAS_RESTANTES, '2m' AS ETAPA
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
            WHERE ST.STOCK_STOCKSTATUSID = 1 AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 7 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 60
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC
        ";

        $sql1s = "
            SELECT
                ST.STOCK_ID, ST.STOCK_FOLIO AS FOLIO, ST.STOCK_CADUCIDAD AS CADUCIDAD, ST.STOCK_LOTE AS LOTE, AR.NOMBRE, AL.ALMACEN_NOMBRE, AL.ALMACEN_ID, AL.ALMACEN_TIPOALMACEN, AL.ALMACEN_ALMACEN_MS, PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                COALESCE((SELECT CLAVE_ARTICULO FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = AR.ARTICULO_ID AND ROL_CLAVE_ART_ID = 17), '') AS CLAVE_ARTICULO,
                DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) AS DIAS_RESTANTES, '1s' AS ETAPA
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
            WHERE ST.STOCK_STOCKSTATUSID = 1 AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) > 0 AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 7
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC
        ";

        $sqlCaducado = "
            SELECT
                ST.STOCK_ID, ST.STOCK_FOLIO AS FOLIO, ST.STOCK_CADUCIDAD AS CADUCIDAD, ST.STOCK_LOTE AS LOTE, AR.NOMBRE, AL.ALMACEN_NOMBRE, AL.ALMACEN_ID, AL.ALMACEN_TIPOALMACEN, AL.ALMACEN_ALMACEN_MS, PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                COALESCE((SELECT CLAVE_ARTICULO FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = AR.ARTICULO_ID AND ROL_CLAVE_ART_ID = 17), '') AS CLAVE_ARTICULO,
                DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) AS DIAS_RESTANTES, 'caducado' AS ETAPA
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
            WHERE ST.STOCK_STOCKSTATUSID = 1 AND ST.STOCK_CADUCIDAD IS NOT NULL
            AND DATEDIFF(DAY, CURRENT_DATE, ST.STOCK_CADUCIDAD) <= 0
            " . $filtroStock . "
            ORDER BY ST.STOCK_CADUCIDAD ASC
        ";

        $art6m = $db->query($sql6m) ?: [];
        $art3m = $db->query($sql3m) ?: [];
        $art2m = $db->query($sql2m) ?: [];
        $art1s = $db->query($sql1s) ?: [];
        $artCaducado = $db->query($sqlCaducado) ?: [];

        foreach (['art6m', 'art3m', 'art2m', 'art1s', 'artCaducado'] as $bucketName) {
            if (!is_array($$bucketName)) {
                $$bucketName = [];
                continue;
            }

            foreach ($$bucketName as &$item) {
                $item['PUEDE_ACCION'] = $this->puedeAccionInteligenteStock($db, $item['STOCK_ID'] ?? 0);
            }
            unset($item);
        }

        return [
            '6m' => is_array($art6m) ? $art6m : [],
            '3m' => is_array($art3m) ? $art3m : [],
            '2m' => is_array($art2m) ? $art2m : [],
            '1s' => is_array($art1s) ? $art1s : [],
            'caducado' => is_array($artCaducado) ? $artCaducado : []
        ];
    }
}
