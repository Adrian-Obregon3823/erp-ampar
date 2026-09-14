<?php
/*
*********************************************************************************
* Módulo de Inventario Global
* CLASE PARA VISUALIZACIÓN DE ALMACENES, MALETAS Y ARTÍCULOS
*********************************************************************************
*/

class InventarioGlobal
{
    function getGruposLineas()
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT GRUPO_LINEA_ID, NOMBRE FROM GRUPOS_LINEAS 
                WHERE GRUPO_LINEA_ID >= 29705
                ORDER BY NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getDivisiones($grupoLineaId = '')
    {
        $db = new FirebirdConnection(true);
        $where = "DELETED_AT IS NULL";
        if ($grupoLineaId != '') {
            $where .= " AND DIVISION_FAMILIAID = " . (int)$grupoLineaId;
        }
        $sql = "SELECT DIVISION_ID, DIVISION_NOMBRE AS NOMBRE FROM AMPAR_CAT_DIVISION WHERE " . $where . " ORDER BY DIVISION_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene las categorías dependiendo del grupo o division
    function getCategorias($grupoLineaId = '', $divisionId = '')
    {
        $db = new FirebirdConnection(true);
        $where = "LA.GRUPO_LINEA_ID IS NOT NULL AND COALESCE(LA.OCULTO, 'N') = 'N'";

        if ($divisionId != '') {
            $where .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        } else {
            $where .= " AND LA.GRUPO_LINEA_ID >= 29705";
        }

        $sql = "SELECT LA.LINEA_ARTICULO_ID, LA.NOMBRE 
                FROM LINEAS_ARTICULOS LA 
                LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
                WHERE " . $where . " ORDER BY LA.NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene las maletas de un almacén dado. Las filtra y oculta si no tienen stock de la categoría
    function getMaletasByAlmacen($almacenId, $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        if ($almacenId === 'sin_almacen') {
            $where = "(A.ALMACEN_ALMACEN_MS IS NULL OR A.ALMACEN_ALMACEN_MS = 0)
                AND A.ALMACEN_TIPOALMACEN = 3
                AND A.ALMACEN_STATUS = 17
                AND A.DELETED_AT IS NULL";
        } else {
            $where = "A.ALMACEN_ALMACEN_MS = " . (int)$almacenId . "
                AND A.ALMACEN_TIPOALMACEN = 3
                AND A.ALMACEN_STATUS = 17
                AND A.DELETED_AT IS NULL";
        }

        // Filtro condicional EXISTE en Stock para Grupos/Division/Categorias
        $subWhere = "";
        if ($categoriaId != '') {
            $subWhere .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $subWhere .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $subWhere .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        if ($subWhere != "") {
            $where .= " AND EXISTS (
                SELECT 1 FROM AMPAR_HIS_STOCK S 
                JOIN ARTICULOS AR ON AR.ARTICULO_ID = S.STOCK_ARTICULOID
                LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
                LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
                WHERE S.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                AND S.STOCK_STOCKSTATUSID IN (1,2) " . $subWhere . "
            )";
        }

        $sql = "
            SELECT 
                A.ALMACEN_ID,
                A.ALMACEN_FOLIO,
                A.ALMACEN_NOMBRE,
                TM.TIPOMALETA_NOMBRE,
                ST.STATUS_NOMBRE,
                ST.STATUS_COLOR,
                (
                    SELECT MIN(ST2.STOCK_CADUCIDAD)
                    FROM AMPAR_HIS_STOCK ST2
                    WHERE ST2.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID
                    AND ST2.STOCK_STOCKSTATUSID IN (1,2)
                    AND ST2.STOCK_CADUCIDAD IS NOT NULL
                ) AS PROXIMA_CADUCIDAD,
                (
                    SELECT 
                        CASE 
                            WHEN COALESCE(SUM(tmd.TIPOMALETADET_CANTIDADSUGERIDA), 0) = 0 THEN 0
                            ELSE ROUND(
                                100.0 * SUM(
                                    CASE 
                                        WHEN COALESCE(c.CANTIDAD, 0) > tmd.TIPOMALETADET_CANTIDADSUGERIDA
                                        THEN tmd.TIPOMALETADET_CANTIDADSUGERIDA
                                        ELSE COALESCE(c.CANTIDAD, 0)
                                    END
                                ) / SUM(tmd.TIPOMALETADET_CANTIDADSUGERIDA),
                                2
                            )
                        END
                    FROM AMPAR_HIS_TIPOMALETADET tmd
                    LEFT JOIN (
                        SELECT
                            STOCK_ARTICULOID AS ARTICULO_ID,
                            STOCK_ALMACENIDACTUAL,
                            COUNT(*) AS CANTIDAD
                        FROM AMPAR_HIS_STOCK
                        WHERE STOCK_STOCKSTATUSID IN (1, 2)
                        GROUP BY STOCK_ARTICULOID, STOCK_ALMACENIDACTUAL
                    ) c ON c.ARTICULO_ID = tmd.TIPOMALETADET_ARTICULOID AND c.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID
                    WHERE tmd.TIPOMALETADET_TIPOMALETAID = A.ALMACEN_TIPOMALETAID
                ) AS PORCENTAJE,
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 WHERE S.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                 AND S.STOCK_STOCKSTATUSID IN (1,2)) AS TOTAL_ARTICULOS
            FROM AMPAR_HIS_ALMACEN A
            LEFT JOIN AMPAR_HIS_TIPOMALETA TM ON TM.TIPOMALETA_ID = A.ALMACEN_TIPOMALETAID
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = A.ALMACEN_STATUS
            WHERE " . $where . "
            ORDER BY PROXIMA_CADUCIDAD ASC NULLS LAST, A.ALMACEN_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene TODAS las maletas (Global). Filtra y oculta si no tienen stock de la categoría
    function getMaletasGlobal($grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        $where = "A.ALMACEN_TIPOALMACEN = 3 AND A.ALMACEN_STATUS = 17 AND A.DELETED_AT IS NULL";

        // Filtro condicional EXISTE en Stock para Grupos/Division/Categorias
        $subWhere = "";
        if ($categoriaId != '') {
            $subWhere .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $subWhere .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $subWhere .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        if ($subWhere != "") {
            $where .= " AND EXISTS (
                SELECT 1 FROM AMPAR_HIS_STOCK S 
                JOIN ARTICULOS AR ON AR.ARTICULO_ID = S.STOCK_ARTICULOID
                LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
                LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
                WHERE S.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                AND S.STOCK_STOCKSTATUSID IN (1,2) " . $subWhere . "
            )";
        }

        $sql = "
            SELECT 
                A.ALMACEN_ID,
                A.ALMACEN_FOLIO,
                A.ALMACEN_NOMBRE,
                TM.TIPOMALETA_NOMBRE,
                ST.STATUS_NOMBRE,
                ST.STATUS_COLOR,
                PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
                (
                    SELECT MIN(ST2.STOCK_CADUCIDAD)
                    FROM AMPAR_HIS_STOCK ST2
                    WHERE ST2.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID
                    AND ST2.STOCK_STOCKSTATUSID IN (1,2)
                    AND ST2.STOCK_CADUCIDAD IS NOT NULL
                ) AS PROXIMA_CADUCIDAD,
                (
                    SELECT 
                        CASE 
                            WHEN COALESCE(SUM(tmd.TIPOMALETADET_CANTIDADSUGERIDA), 0) = 0 THEN 0
                            ELSE ROUND(
                                100.0 * SUM(
                                    CASE 
                                        WHEN COALESCE(c.CANTIDAD, 0) > tmd.TIPOMALETADET_CANTIDADSUGERIDA
                                        THEN tmd.TIPOMALETADET_CANTIDADSUGERIDA
                                        ELSE COALESCE(c.CANTIDAD, 0)
                                    END
                                ) / SUM(tmd.TIPOMALETADET_CANTIDADSUGERIDA),
                                2
                            )
                        END
                    FROM AMPAR_HIS_TIPOMALETADET tmd
                    LEFT JOIN (
                        SELECT
                            STOCK_ARTICULOID AS ARTICULO_ID,
                            STOCK_ALMACENIDACTUAL,
                            COUNT(*) AS CANTIDAD
                        FROM AMPAR_HIS_STOCK
                        WHERE STOCK_STOCKSTATUSID IN (1, 2)
                        GROUP BY STOCK_ARTICULOID, STOCK_ALMACENIDACTUAL
                    ) c ON c.ARTICULO_ID = tmd.TIPOMALETADET_ARTICULOID AND c.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID
                    WHERE tmd.TIPOMALETADET_TIPOMALETAID = A.ALMACEN_TIPOMALETAID
                ) AS PORCENTAJE,
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 WHERE S.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                 AND S.STOCK_STOCKSTATUSID IN (1,2)) AS TOTAL_ARTICULOS
            FROM AMPAR_HIS_ALMACEN A
            LEFT JOIN AMPAR_HIS_TIPOMALETA TM ON TM.TIPOMALETA_ID = A.ALMACEN_TIPOMALETAID
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = A.ALMACEN_STATUS
            LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = A.ALMACEN_ALMACEN_MS
            WHERE " . $where . "
            ORDER BY PROXIMA_CADUCIDAD ASC NULLS LAST, PA.ALMACEN_NOMBRE, A.ALMACEN_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene los artículos de un almacén o maleta específica, agrupados
    function getArticulosByAlmacen($almacenId, $busqueda = "", $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        if ($almacenId === 'sin_almacen') {
            $where = "ST.STOCK_STOCKSTATUSID IN (1, 2) AND (ST.STOCK_ALMACENIDACTUAL IS NULL OR ST.STOCK_ALMACENIDACTUAL = 0)";
        } else {
            $where = "ST.STOCK_STOCKSTATUSID IN (1, 2) AND ST.STOCK_ALMACENIDACTUAL = " . (int)$almacenId;
        }

        if ($busqueda != "") {
            $buscar = strtoupper($busqueda);
            $where .= " AND (
                UPPER(X.CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
                OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
                OR UPPER(ST.STOCK_FOLIO) CONTAINING '" . $buscar . "'
            )";
        }

        // Aplicamos los filtros
        if ($categoriaId != '') {
            $where .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $where .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $sql = "
            SELECT 
                ST.STOCK_ARTICULOID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO AS SKU,
                GL.NOMBRE AS FAMILIA_NOMBRE,
                DV.DIVISION_NOMBRE AS DIVISION_NOMBRE,
                LA.NOMBRE AS CATEGORIA_NOMBRE,
                DA.CLAVE AS CLAVE_SAT,
                AR.UNIDAD_VENTA,
                MIN(ST.STOCK_CADUCIDAD) AS PROXIMA_CADUCIDAD,
                COUNT(ST.STOCK_ID) AS CANTIDAD_TOTAL,
                SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 1 THEN 1 ELSE 0 END) AS CANTIDAD_FISICA,
                SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 2 THEN 1 ELSE 0 END) AS CANTIDAD_TRANSITO
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_CAT_DIVISION DV ON DV.DIVISION_ID = RLD.DIVISION_ID
            LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN DATOS_ADICIONALES DA ON DA.NOM_TABLA = 'ARTICULOS' AND DA.ELEM_ID = AR.ARTICULO_ID
            WHERE " . $where . "
            GROUP BY 
                ST.STOCK_ARTICULOID, 
                AR.NOMBRE, 
                X.CLAVE_ARTICULO,
                GL.NOMBRE,
                DV.DIVISION_NOMBRE,
                LA.NOMBRE,
                DA.CLAVE,
                AR.UNIDAD_VENTA
            ORDER BY 
                CASE WHEN GL.NOMBRE IS NULL OR GL.NOMBRE = '' THEN 1 ELSE 0 END ASC, GL.NOMBRE ASC, 
                CASE WHEN DV.DIVISION_NOMBRE IS NULL OR DV.DIVISION_NOMBRE = '' THEN 1 ELSE 0 END ASC, DV.DIVISION_NOMBRE ASC,
                CASE WHEN LA.NOMBRE IS NULL OR LA.NOMBRE = '' THEN 1 ELSE 0 END ASC, LA.NOMBRE ASC, 
                MIN(ST.STOCK_CADUCIDAD) ASC NULLS LAST, AR.NOMBRE ASC
        ";

        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene TODOS los artículos de TODOS los almacenes (vista Global)
    function getArticulosGlobal($busqueda = "", $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        $where = "ST.STOCK_STOCKSTATUSID IN (1, 2)";

        if ($busqueda != "") {
            $buscar = strtoupper($busqueda);
            $where .= " AND (
                UPPER(X.CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
                OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
                OR UPPER(ST.STOCK_FOLIO) CONTAINING '" . $buscar . "'
            )";
        }

        if ($categoriaId != '') {
            $where .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $where .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $sql = "
            SELECT 
                ST.STOCK_ARTICULOID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO AS SKU,
                GL.NOMBRE AS FAMILIA_NOMBRE,
                DV.DIVISION_NOMBRE AS DIVISION_NOMBRE,
                LA.NOMBRE AS CATEGORIA_NOMBRE,
                DA.CLAVE AS CLAVE_SAT,
                AR.UNIDAD_VENTA,
                MIN(ST.STOCK_CADUCIDAD) AS PROXIMA_CADUCIDAD,
                COUNT(ST.STOCK_ID) AS CANTIDAD_TOTAL,
                SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 1 THEN 1 ELSE 0 END) AS CANTIDAD_FISICA,
                SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 2 THEN 1 ELSE 0 END) AS CANTIDAD_TRANSITO
            FROM AMPAR_HIS_STOCK ST
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
            LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_CAT_DIVISION DV ON DV.DIVISION_ID = RLD.DIVISION_ID
            LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN DATOS_ADICIONALES DA ON DA.NOM_TABLA = 'ARTICULOS' AND DA.ELEM_ID = AR.ARTICULO_ID
            WHERE " . $where . "
            GROUP BY 
                ST.STOCK_ARTICULOID, 
                AR.NOMBRE, 
                X.CLAVE_ARTICULO,
                GL.NOMBRE,
                DV.DIVISION_NOMBRE,
                LA.NOMBRE,
                DA.CLAVE,
                AR.UNIDAD_VENTA
            ORDER BY 
                CASE WHEN GL.NOMBRE IS NULL OR GL.NOMBRE = '' THEN 1 ELSE 0 END ASC, GL.NOMBRE ASC, 
                CASE WHEN DV.DIVISION_NOMBRE IS NULL OR DV.DIVISION_NOMBRE = '' THEN 1 ELSE 0 END ASC, DV.DIVISION_NOMBRE ASC,
                CASE WHEN LA.NOMBRE IS NULL OR LA.NOMBRE = '' THEN 1 ELSE 0 END ASC, LA.NOMBRE ASC, 
                MIN(ST.STOCK_CADUCIDAD) ASC NULLS LAST, AR.NOMBRE ASC
        ";

        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene los artículos que tienen 0 existencia en el almacén o globalmente
    function getArticulosSinStock($almacenId, $busqueda = "", $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        
        if ($almacenId === 'global') {
            $subWhere = "S.STOCK_STOCKSTATUSID IN (1, 2)";
        } elseif ($almacenId === 'sin_almacen') {
            $subWhere = "S.STOCK_STOCKSTATUSID IN (1, 2) AND (S.STOCK_ALMACENIDACTUAL IS NULL OR S.STOCK_ALMACENIDACTUAL = 0)";
        } else {
            $subWhere = "S.STOCK_STOCKSTATUSID IN (1, 2) AND S.STOCK_ALMACENIDACTUAL = " . (int)$almacenId;
        }

        $where = "AR.ESTATUS = 'A' AND NOT EXISTS (
            SELECT 1 FROM AMPAR_HIS_STOCK S 
            WHERE S.STOCK_ARTICULOID = AR.ARTICULO_ID 
            AND " . $subWhere . "
        )";

        if ($busqueda != "") {
            $buscar = strtoupper($busqueda);
            $where .= " AND (
                UPPER(X.CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
                OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
            )";
        }

        if ($categoriaId != '') {
            $where .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $where .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        } else {
            // Solo grupos 29705 en adelante
            $where .= " AND CAST(LA.GRUPO_LINEA_ID AS INTEGER) >= 29705";
        }

        $sql = "
            SELECT 
                AR.ARTICULO_ID AS STOCK_ARTICULOID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO AS SKU,
                GL.NOMBRE AS FAMILIA_NOMBRE,
                DV.DIVISION_NOMBRE AS DIVISION_NOMBRE,
                LA.NOMBRE AS CATEGORIA_NOMBRE,
                DA.CLAVE AS CLAVE_SAT,
                AR.UNIDAD_VENTA,
                NULL AS PROXIMA_CADUCIDAD,
                0 AS CANTIDAD_TOTAL,
                0 AS CANTIDAD_FISICA,
                0 AS CANTIDAD_TRANSITO
            FROM ARTICULOS AR
            LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_CAT_DIVISION DV ON DV.DIVISION_ID = RLD.DIVISION_ID
            LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN DATOS_ADICIONALES DA ON DA.NOM_TABLA = 'ARTICULOS' AND DA.ELEM_ID = AR.ARTICULO_ID
            WHERE " . $where . "
            ORDER BY 
                CASE WHEN GL.NOMBRE IS NULL OR GL.NOMBRE = '' THEN 1 ELSE 0 END ASC, GL.NOMBRE ASC, 
                CASE WHEN DV.DIVISION_NOMBRE IS NULL OR DV.DIVISION_NOMBRE = '' THEN 1 ELSE 0 END ASC, DV.DIVISION_NOMBRE ASC,
                CASE WHEN LA.NOMBRE IS NULL OR LA.NOMBRE = '' THEN 1 ELSE 0 END ASC, LA.NOMBRE ASC, 
                AR.NOMBRE ASC
        ";

        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene el resumen total de un almacén (stock propio + maletas hijas)
    function getResumenAlmacen($almacenId, $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);

        $subWhere = "";
        if ($categoriaId != '') {
            $subWhere = " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $subWhere .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $subWhere = " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $joinResumen = " LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = S.STOCK_ARTICULOID ";
        if ($subWhere != "") {
            $joinResumen .= " LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID ";
        }
        
        if ($almacenId === 'sin_almacen') {
            $whereAlmStock = "(S.STOCK_ALMACENIDACTUAL IS NULL OR S.STOCK_ALMACENIDACTUAL = 0)";
            $whereAlmMaleta = "(ALMACEN_ALMACEN_MS IS NULL OR ALMACEN_ALMACEN_MS = 0)";
            $whereUnidadesAlmacen = "(S.STOCK_ALMACENIDACTUAL IS NULL OR S.STOCK_ALMACENIDACTUAL = 0)";
            $whereUnidadesMaletas = "1=0";
            
            $whereAlmEq = "(EC.ALMACEN_ID IS NULL OR EC.ALMACEN_ID = 0)";
            $whereEqAlmacen = "(EC.ALMACEN_ID IS NULL OR EC.ALMACEN_ID = 0)";
            $whereEqMaletas = "1=0";
        } else {
            $whereAlmStock = "(A.ALMACEN_ID = " . (int)$almacenId . " OR A.ALMACEN_ALMACEN_MS = " . (int)$almacenId . ")";
            $whereAlmMaleta = "ALMACEN_ALMACEN_MS = " . (int)$almacenId;
            $whereUnidadesAlmacen = "A.ALMACEN_ID = " . (int)$almacenId;
            $whereUnidadesMaletas = "A.ALMACEN_ALMACEN_MS = " . (int)$almacenId;

            $whereAlmEq = "(EC.ALMACEN_ID = " . (int)$almacenId . " OR EC.ALMACEN_ID IN (SELECT ALMACEN_ID FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ALMACEN_MS = " . (int)$almacenId . "))";
            $whereEqAlmacen = "EC.ALMACEN_ID = " . (int)$almacenId;
            $whereEqMaletas = "EC.ALMACEN_ID IN (SELECT ALMACEN_ID FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ALMACEN_MS = " . (int)$almacenId . ")";
        }

        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE $whereAlmStock
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS TOTAL_UNIDADES,

                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE $whereUnidadesAlmacen
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS UNIDADES_ALMACEN,

                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE $whereUnidadesMaletas
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS UNIDADES_MALETAS,
                 
                (SELECT COUNT(DISTINCT S.STOCK_ARTICULOID) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE $whereAlmStock
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS TOTAL_ARTICULOS,
                 
                (SELECT COUNT(*) FROM AMPAR_EQUIPOCAPITAL EC 
                 LEFT JOIN ARTICULOS AR2 ON AR2.ARTICULO_ID = EC.ARTICULO_ID
                 LEFT JOIN LINEAS_ARTICULOS LA2 ON LA2.LINEA_ARTICULO_ID = AR2.LINEA_ARTICULO_ID
                 LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD2 ON RLD2.LINEA_ARTICULO_ID = LA2.LINEA_ARTICULO_ID
                 WHERE EC.ESTATUS = 'A' AND $whereAlmEq " . str_replace(['AR.', 'LA.', 'RLD.'], ['AR2.', 'LA2.', 'RLD2.'], $subWhere) . ") AS TOTAL_EQUIPO_CAPITAL,

                (SELECT COUNT(*) FROM AMPAR_HIS_ALMACEN 
                 WHERE $whereAlmMaleta 
                 AND ALMACEN_TIPOALMACEN = 3 AND DELETED_AT IS NULL) AS TOTAL_MALETAS
            FROM RDB\$DATABASE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene resumen Global (todos los almacenes)
    function getResumenGlobal($grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        $subWhere = "";
        if ($categoriaId != '') {
            $subWhere = " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $subWhere .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $subWhere = " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $joinResumen = " LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = S.STOCK_ARTICULOID ";
        if ($subWhere != "") {
            $joinResumen .= " LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID ";
        }

        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 $joinResumen
                 WHERE S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS TOTAL_UNIDADES,

                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE (A.ALMACEN_ALMACEN_MS IS NULL OR A.ALMACEN_ALMACEN_MS = 0)
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS UNIDADES_ALMACEN,

                (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                 LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
                 $joinResumen
                 WHERE A.ALMACEN_ALMACEN_MS > 0
                 AND S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS UNIDADES_MALETAS,
                 
                (SELECT COUNT(DISTINCT S.STOCK_ARTICULOID) FROM AMPAR_HIS_STOCK S 
                 $joinResumen
                 WHERE S.STOCK_STOCKSTATUSID IN (1,2) $subWhere) AS TOTAL_ARTICULOS,
                 
                (SELECT COUNT(*) FROM AMPAR_EQUIPOCAPITAL EC 
                 LEFT JOIN ARTICULOS AR2 ON AR2.ARTICULO_ID = EC.ARTICULO_ID
                 LEFT JOIN LINEAS_ARTICULOS LA2 ON LA2.LINEA_ARTICULO_ID = AR2.LINEA_ARTICULO_ID
                 LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD2 ON RLD2.LINEA_ARTICULO_ID = LA2.LINEA_ARTICULO_ID
                 WHERE EC.ESTATUS = 'A' " . str_replace(['AR.', 'LA.', 'RLD.'], ['AR2.', 'LA2.', 'RLD2.'], $subWhere) . ") AS TOTAL_EQUIPO_CAPITAL,

                (SELECT COUNT(*) FROM AMPAR_HIS_ALMACEN 
                 WHERE ALMACEN_TIPOALMACEN = 3 AND DELETED_AT IS NULL) AS TOTAL_MALETAS
            FROM RDB\$DATABASE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene KPIs globales (sin filtro)
    function getKPIs($grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        $subWhere = "";
        if ($categoriaId != '') {
            $subWhere = " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $subWhere .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $subWhere = " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $joinResumen = " LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID ";
        if ($subWhere != "") {
            $joinResumen .= " LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID ";
        }

        $sql = "
            SELECT 
                COUNT(*) AS TOTAL_UNIDADES,
                COUNT(DISTINCT STOCK_ARTICULOID) AS TOTAL_ARTICULOS_UNIFICADOS,
                (SELECT COUNT(*) FROM AMPAR_EQUIPOCAPITAL EC 
                 LEFT JOIN ARTICULOS AR2 ON AR2.ARTICULO_ID = EC.ARTICULO_ID
                 LEFT JOIN LINEAS_ARTICULOS LA2 ON LA2.LINEA_ARTICULO_ID = AR2.LINEA_ARTICULO_ID
                 LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD2 ON RLD2.LINEA_ARTICULO_ID = LA2.LINEA_ARTICULO_ID
                 WHERE EC.ESTATUS = 'A' " . str_replace(['AR.', 'LA.', 'RLD.'], ['AR2.', 'LA2.', 'RLD2.'], $subWhere) . ") AS TOTAL_EQUIPO_CAPITAL
            FROM AMPAR_HIS_STOCK ST
            $joinResumen
            WHERE STOCK_STOCKSTATUSID IN (1, 2) $subWhere
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // Obtiene TODOS los equipos capitales registrados y sus componentes
    function getEquipoCapitalDirecto($almacenId = '', $busqueda = "", $grupoLineaId = '', $divisionId = '', $categoriaId = '')
    {
        $db = new FirebirdConnection(true);
        $where = "EC.ESTATUS = 'A'";

        if ($almacenId !== '' && $almacenId !== 'global') {
            if ($almacenId === 'sin_almacen') {
                $where .= " AND (EC.ALMACEN_ID IS NULL OR EC.ALMACEN_ID = 0)";
            } else {
                $where .= " AND (EC.ALMACEN_ID = " . (int)$almacenId . " OR EC.ALMACEN_ID IN (SELECT ALMACEN_ID FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ALMACEN_MS = " . (int)$almacenId . "))";
            }
        }

        if ($busqueda != "") {
            $buscar = strtoupper($busqueda);
            $where .= " AND (
                UPPER(EC.FOLIO) CONTAINING '" . $buscar . "'
                OR UPPER(EC.REFERENCIA) CONTAINING '" . $buscar . "'
                OR UPPER(EC.MARCA) CONTAINING '" . $buscar . "'
                OR UPPER(EC.UBICACION) CONTAINING '" . $buscar . "'
                OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
                OR UPPER(X.CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
            )";
        }

        if ($categoriaId != '') {
            $where .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoriaId;
        } elseif ($divisionId != '') {
            $where .= " AND RLD.DIVISION_ID = " . (int)$divisionId;
        } elseif ($grupoLineaId != '') {
            $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupoLineaId;
        }

        $sql = "
            SELECT 
                EC.EQUIPOCAPITAL_ID,
                EC.FOLIO,
                EC.REFERENCIA,
                EC.MARCA,
                EC.UBICACION,
                AR.ARTICULO_ID AS STOCK_ARTICULOID,
                AR.NOMBRE AS ARTICULO_NOMBRE,
                X.CLAVE_ARTICULO AS SKU,
                GL.NOMBRE AS FAMILIA_NOMBRE,
                DV.DIVISION_NOMBRE AS DIVISION_NOMBRE,
                LA.NOMBRE AS CATEGORIA_NOMBRE,
                DA.CLAVE AS CLAVE_SAT,
                AR.UNIDAD_VENTA,
                1 AS CANTIDAD_TOTAL
            FROM AMPAR_EQUIPOCAPITAL EC
            INNER JOIN ARTICULOS AR ON AR.ARTICULO_ID = EC.ARTICULO_ID
            LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
            LEFT JOIN AMPAR_CAT_DIVISION DV ON DV.DIVISION_ID = RLD.DIVISION_ID
            LEFT JOIN GRUPOS_LINEAS GL ON GL.GRUPO_LINEA_ID = LA.GRUPO_LINEA_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN DATOS_ADICIONALES DA ON DA.NOM_TABLA = 'ARTICULOS' AND DA.ELEM_ID = AR.ARTICULO_ID
            WHERE " . $where . "
            ORDER BY AR.NOMBRE ASC, EC.FOLIO ASC
        ";

        $result = $db->query($sql);
        
        // Cargar componentes para los equipos obtenidos
        if (is_array($result) && count($result) > 0) {
            $sqlComp = "SELECT EQUIPOCAPITAL_ID, NOMBRE, CANTIDAD, SERIE, REFERENCIA, MARCA 
                        FROM AMPAR_EQUIPOCAPITAL_COMPONENTES 
                        WHERE ESTATUS = 'A'";
            $resComp = $db->query($sqlComp);
            
            $componentsByEq = [];
            if (is_array($resComp)) {
                foreach ($resComp as $comp) {
                    $eqId = $comp['EQUIPOCAPITAL_ID'];
                    if (!isset($componentsByEq[$eqId])) {
                        $componentsByEq[$eqId] = [];
                    }
                    $componentsByEq[$eqId][] = $comp;
                }
            }
            
            foreach ($result as $key => $row) {
                $eqId = $row['EQUIPOCAPITAL_ID'];
                $result[$key]['COMPONENTES'] = $componentsByEq[$eqId] ?? [];
            }
        }

        $db->close();
        return $result;
    }
}
