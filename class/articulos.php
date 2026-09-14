<?php

class articulos{

    function getqueryarticulos(){
        $sql = "SELECT * FROM ARTICULOS WHERE NOMBRE LIKE ?";
        return $sql;
    }

    function getarticulos(){
        $db = new FirebirdConnection();
        $sql = $this->getqueryarticulos();
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoarticulos(){
        $db = new FirebirdConnection();
        $sql = "
            SELECT AR.ARTICULO_ID ID, CLAVE_ARTICULO, AR.NOMBRE, SEGUIMIENTO,
            (SELECT LIST(CLAVE_ARTICULO, ' ') FROM claves_articulos WHERE ARTICULO_ID = AR.ARTICULO_ID) AS TODAS_CLAVES,
            (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                FROM AMPAR_CAT_ARTPRECIO AP
                WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL) AS SUBTOTAL,
             (SELECT FIRST 1 AP.ARTPRECIO_IVA
                FROM AMPAR_CAT_ARTPRECIO AP
                WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL) AS IVA,
             (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                FROM AMPAR_CAT_ARTPRECIO AP
                WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                    AND AP.ARTPRECIO_CLIENTEID  IS NULL) AS TOTAL
            FROM ARTICULOS AR
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE ESTATUS = 'A'
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoarticulosbyalmacenid($almacenid, $filtros_str = "", $incluirMaletas = false){
        $db = new FirebirdConnection();
        $almacenid = (int)$almacenid;
        
        $statusFiltro = "IN (1,2)";
        $filtroAlmacen = "STOCK_ALMACENIDACTUAL = " . $almacenid;

        if ($incluirMaletas) {
            $filtroAlmacen = "(
                STOCK_ALMACENIDACTUAL = " . $almacenid . "
                OR STOCK_ALMACENIDACTUAL IN (
                    SELECT A2.ALMACEN_ID
                    FROM AMPAR_HIS_ALMACEN A2
                    WHERE A2.ALMACEN_ALMACEN_MS = " . $almacenid . "
                    AND A2.ALMACEN_TIPOALMACEN = 3
                    AND A2.DELETED_AT IS NULL
                )
            )";
        }

        $filtros = $filtros_str ? explode(',', $filtros_str) : [];
        $caducidadConditions = [];
        
        if (in_array('caducados', $filtros)) {
            $caducidadConditions[] = "(ST.STOCK_CADUCIDAD IS NOT NULL AND ST.STOCK_CADUCIDAD < CURRENT_DATE)";
        }
        if (in_array('proximos', $filtros)) {
            $caducidadConditions[] = "(ST.STOCK_CADUCIDAD IS NOT NULL AND ST.STOCK_CADUCIDAD >= CURRENT_DATE AND ST.STOCK_CADUCIDAD <= DATEADD(1 MONTH TO CURRENT_DATE))";
        }
        if (in_array('vigentes', $filtros)) {
            $caducidadConditions[] = "(ST.STOCK_CADUCIDAD IS NULL OR ST.STOCK_CADUCIDAD > DATEADD(1 MONTH TO CURRENT_DATE))";
        }
        
        if (empty($caducidadConditions)) {
            $caducidadConditions[] = "(ST.STOCK_CADUCIDAD IS NULL OR ST.STOCK_CADUCIDAD > DATEADD(1 MONTH TO CURRENT_DATE))";
        }

        $filtroCaducidad = "";
        if (count($caducidadConditions) > 0) {
            $filtroCaducidad = " AND (" . implode(" OR ", $caducidadConditions) . ")";
        }

        $sql = "
            SELECT 
            ST.STOCK_FOLIO, AR.ARTICULO_ID ID, NOMBRE, STOCK_LOTE LOTE, STOCK_CADUCIDAD CADUCIDAD, STOCK_SERIE SERIE, STOCK_ID INVDETID, X.CLAVE_ARTICULO, STOCK_FECHA, AR.SEGUIMIENTO
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE
            STOCK_STOCKSTATUSID ".$statusFiltro."
            AND ".$filtroAlmacen.$filtroCaducidad."
            ORDER BY NOMBRE, STOCK_FECHA DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogolineaarticulos($grupo_linea_id = null, $search = null, $division_id = null){
        $db = new FirebirdConnection();
        $sql = "SELECT L.LINEA_ARTICULO_ID ID, L.NOMBRE FROM lineas_articulos L";
        
        if ($division_id) {
            $sql .= " INNER JOIN AMPAR_REL_LINEA_DIVISION R ON R.LINEA_ARTICULO_ID = L.LINEA_ARTICULO_ID AND R.DIVISION_ID = " . (int)$division_id;
        }

        $sql .= " WHERE COALESCE(L.OCULTO, 'N') = 'N'";
        
        if ($grupo_linea_id) {
            $sql .= " AND L.GRUPO_LINEA_ID = " . (int)$grupo_linea_id;
        }
        
        if ($search) {
            $search_clean = str_replace("'", "''", $search);
            $sql .= " AND UPPER(L.NOMBRE) LIKE UPPER('%" . $search_clean . "%')";
        }

        $sql .= " ORDER BY L.NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }
    
    function getcatalogoumedida(){
        $db = new FirebirdConnection();
        $sql = "SELECT UNIDAD_VENTA_ID ID, UNIDAD_VENTA NOMBRE FROM UNIDADES_VENTA";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function buscarcvearticulo($clave){
        $db = new FirebirdConnection();
        $sql = "
            SELECT A.ARTICULO_ID FROM CLAVES_ARTICULOS CA
            LEFT JOIN ARTICULOS A ON CA.ARTICULO_ID = A.ARTICULO_ID
            WHERE CLAVE_ARTICULO = '".$clave."'
            AND CA.ROL_CLAVE_ART_ID = 17
        ";
        $result = $db->query($sql);
        $db->close();
        if ($result<>0){
            return $result[0]['ARTICULO_ID'];
        }else{
            return null;
        }
    }

    function getcatalogoarticulosremisionmostrador($almacenid, $tiposalida, $maletaid = 0, $clienteid = null) {
        $db = new FirebirdConnection();
        $almacenid = (int)$almacenid;
        $maletaid = (int)$maletaid;
        $clienteCond = $clienteid !== null ? (int)$clienteid : -999;
        
        // Filtro de almacén: si es procedimiento, buscamos en la maleta, si es venta directa en el almacén
        $filtroAlmacen = "";
        $useUnion = false;
        if ($tiposalida === 'PROCEDIMIENTO') {
            if ($maletaid > 0) {
                $filtroAlmacen = " AND ST.STOCK_ALMACENIDACTUAL = {$maletaid} ";
                $useUnion = true;
            } else {
                return []; // No maleta, no articulos
            }
        } else {
            $filtroAlmacen = " AND ST.STOCK_ALMACENIDACTUAL = {$almacenid} ";
        }

        $sql1 = "
            SELECT 
                ST.STOCK_FOLIO, 
                AR.ARTICULO_ID AS ID, 
                AR.NOMBRE, 
                ST.STOCK_LOTE AS LOTE, 
                ST.STOCK_CADUCIDAD AS CADUCIDAD, 
                ST.STOCK_SERIE AS SERIE, 
                ST.STOCK_ID AS INVDETID, 
                X.CLAVE_ARTICULO,
                MAL.ALMACEN_NOMBRE AS MALETA_NOMBRE,
                MAL.ALMACEN_FOLIO AS MALETA_FOLIO,
                ST.STOCK_FECHA,
                /* Precios de catálogo (por cliente con fallback a NULL) */
                COALESCE((SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS SUBTOTAL,
                COALESCE((SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS IVA,
                COALESCE((SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS TOTAL
            FROM AMPAR_HIS_STOCK ST
            JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN MAL ON MAL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL AND MAL.ALMACEN_TIPOALMACEN = 3
            WHERE ST.STOCK_STOCKSTATUSID = 1 -- Disponible
              AND (ST.STOCK_CADUCIDAD IS NULL OR ST.STOCK_CADUCIDAD >= CURRENT_DATE) -- No caducado
              {$filtroAlmacen}
        ";

        if ($useUnion) {
            $sql2 = "
                SELECT 
                    ST.STOCK_FOLIO, 
                    AR.ARTICULO_ID AS ID, 
                    AR.NOMBRE, 
                    ST.STOCK_LOTE AS LOTE, 
                    ST.STOCK_CADUCIDAD AS CADUCIDAD, 
                    ST.STOCK_SERIE AS SERIE, 
                    ST.STOCK_ID AS INVDETID, 
                    X.CLAVE_ARTICULO,
                    CAST(NULL AS VARCHAR(100)) AS MALETA_NOMBRE,
                    CAST(NULL AS VARCHAR(50)) AS MALETA_FOLIO,
                    ST.STOCK_FECHA,
                    /* Precios de catálogo (por cliente con fallback a NULL) */
                    COALESCE((SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS SUBTOTAL,
                    COALESCE((SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS IVA,
                    COALESCE((SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}), (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL), 0) AS TOTAL
                FROM AMPAR_HIS_STOCK ST
                JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
                LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                WHERE ST.STOCK_STOCKSTATUSID = 1 -- Disponible
                  AND (ST.STOCK_CADUCIDAD IS NULL OR ST.STOCK_CADUCIDAD >= CURRENT_DATE) -- No caducado
                  AND ST.STOCK_ALMACENIDACTUAL = {$almacenid}
                  AND AR.UNIDAD_VENTA LIKE '%UNIDAD DE SERVICIO%'
            ";
            $sql = "$sql1 UNION ALL $sql2 ORDER BY 3, 11 DESC";
        } else {
            $sql = $sql1 . " ORDER BY AR.NOMBRE, ST.STOCK_FECHA DESC";
        }
        
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getEquipoCapitalDisponible($almacenid, $clienteid = null) {
        $db = new FirebirdConnection();
        $almacenid = (int)$almacenid;
        $clienteCond = $clienteid !== null ? (int)$clienteid : -999;
        
        $sql = "
            SELECT 
                EC.EQUIPOCAPITAL_ID AS ID, 
                EC.FOLIO, 
                AR.ARTICULO_ID, 
                AR.NOMBRE AS ARTICULO_NOMBRE, 
                X.CLAVE_ARTICULO,
                EC.REFERENCIA AS SERIE,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID IS NULL)
                ) AS SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID IS NULL)
                ) AS IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID IS NULL)
                ) AS TOTAL
            FROM AMPAR_EQUIPOCAPITAL EC
            INNER JOIN ARTICULOS AR ON EC.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE EC.ESTATUS = 'A'
              AND EC.ALMACEN_ID = {$almacenid}
              AND EC.EQUIPOCAPITAL_ID NOT IN (
                  SELECT -EVENTOMALETA_MALETAID 
                  FROM AMPAR_HIS_EVENTOSMALETAS 
                  INNER JOIN AMPAR_HIS_EVENTOS ON EVENTO_ID = EVENTOMALETA_EVENTOID
                  WHERE EVENTOMALETA_MALETAID < 0 
                    AND EVENTO_STATUSGENERAL NOT IN (3, 5)
              )
            ORDER BY AR.NOMBRE
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