<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* CLASE PRINCIPAL PARA ALMACENES
*********************************************************************************
*/
?>
<?php

class almacenes
{

    //SELECCIONA TODOS LOS ALMACENES TIPO 1 Y 2 OSEA NO MALETAS
    function getalmacenes()
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT A.*, TA.TIPOALMACEN_NOMBRE, SU.NOMBRE SUCURSAL_NOMBRE, S.*
                FROM AMPAR_HIS_ALMACEN A
                LEFT JOIN AMPAR_CONF_TIPOALMACEN TA ON ALMACEN_TIPOALMACEN = TIPOALMACEN_ID
                LEFT JOIN ampar_cat_sucursales SU on SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = A.ALMACEN_STATUS
                WHERE ALMACEN_TIPOALMACEN IN (1,2)
                ORDER BY SU.NOMBRE, ALMACEN_FOLIO
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    //INFORMACION DE UN ALMACEN
    function getinfoalmacen($almacenid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT 
                A.*, C.NOMBRE MUNICIPIO_NOMBRE, P.NOMBRE PAIS_NOMBRE, E.NOMBRE ESTADO_NOMBRE, TA.*, S.SUCURSAL_ID, S.NOMBRE SUCURSAL_NOMBRE, ST.STATUS_NOMBRE, ST.STATUS_COLOR, TIPOMALETA_NOMBRE
                FROM 
                AMPAR_HIS_ALMACEN A
                left join CIUDADES C on C.CIUDAD_ID = A.ALMACEN_MUNICIPIO
                left join PAISES P ON P.PAIS_ID = A.ALMACEN_PAIS
                left join ESTADOS E on E.ESTADO_ID = A.ALMACEN_ESTADO
                left join AMPAR_CONF_TIPOALMACEN TA on TA.TIPOALMACEN_ID = A.ALMACEN_TIPOALMACEN
                left join ampar_cat_sucursales S on S.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                left join AMPAR_CONF_STATUS ST ON ST.STATUS_ID = ALMACEN_STATUS
                left join AMPAR_HIS_TIPOMALETA TM ON TIPOMALETA_ID = ALMACEN_TIPOMALETAID
                WHERE A.ALMACEN_ID = " . $almacenid . "
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    //GET TODOS LOS ARTICULOS DE UN ALMACEN ORDENADOS POR ARTICULO
    function getarticulosalmacenbyalmacenid($almacenid, $busqueda = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT
                ST.*, AR.*, X.CLAVE_ARTICULO_ID, X.CLAVE_ARTICULO, A.*, S.NOMBRE SUCURSAL_NOMBRE, STS.STOKSTATUS_NOMBRE STOCK_STOCKSTATUSNOMBRE
                FROM AMPAR_HIS_STOCK ST
                LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
                LEFT JOIN ampar_cat_sucursales S ON S.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
                LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = STOCK_ARTICULOID 
                LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
                LEFT JOIN AMPAR_CONF_STOKSTATUS STS ON STS.STOKSTATUS_ID = ST.STOCK_STOCKSTATUSID
                WHERE 
                STOCK_STOCKSTATUSID in (1, 2)
                AND STOCK_ALMACENIDACTUAL = " . $almacenid . "
            ";
        if ($busqueda <> "") {
            $buscar = strtoupper($busqueda);
            $sql .= " 
                    AND (
                        UPPER(STOCK_FOLIO) CONTAINING '" . $buscar . "'
                        OR UPPER(CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
                        OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
                    )
                ";
        }
        $sql .= "
                ORDER BY STOCK_ID DESC
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function nuevo($tipoalmacen, $nombre, $descripcion, $calle, $numext, $numint, $colonia, $cp, $tel, $pais, $estado, $municipio, $sucursal)
    {
        // usa transacción explícita
        $db = new FirebirdConnection(false); // autoCommit = false

        try {
            $sql = "
                    INSERT INTO AMPAR_HIS_ALMACEN
                    (
                        ALMACEN_FOLIO,
                        ALMACEN_TIPOALMACEN,
                        ALMACEN_NOMBRE,
                        ALMACEN_DESCRIPCION,
                        ALMACEN_CALLE,
                        ALMACEN_NUMEXT,
                        ALMACEN_NUMINT,
                        ALMACEN_COLONIA,
                        ALMACEN_PAIS,
                        ALMACEN_ESTADO,
                        ALMACEN_MUNICIPIO,
                        ALMACEN_CP,
                        ALMACEN_TELEFONO,
                        ALMACEN_SUCURSAL_MS,
                        ALMACEN_STATUS
                    ) VALUES (
                        (
                            SELECT 'AL' ||
                                   LPAD(COALESCE(MAX(CAST(SUBSTRING(ALMACEN_FOLIO FROM 3) AS INTEGER)),0) + 1, 3, '0')
                            FROM AMPAR_HIS_ALMACEN
                            WHERE ALMACEN_FOLIO STARTING WITH 'AL'
                        ),
                        {$tipoalmacen},
                        '{$nombre}',
                        '{$descripcion}',
                        '{$calle}',
                        '{$numext}',
                        '{$numint}',
                        '{$colonia}',
                        " . (($pais === '') ? 'null' : $pais) . ",
                        " . (($estado === '') ? 'null' : $estado) . ",
                        " . (($municipio === '') ? 'null' : $municipio) . ",
                        '{$cp}',
                        '{$tel}',
                        '{$sucursal}',
                        17
                    )
                ";

            $id_insertado = $db->executeconreturning($sql, 'ALMACEN_ID');

            // 🔴 Muy importante: confirmar la inserción del padre
            $db->commit();
            $db->close();

            // Ya confirmado: ahora sí consulta y registra bitácora en otra conexión
            $infoalmacen = $this->getinfoalmacen($id_insertado);
            $usersesion  = $_SESSION['ampar']['usuario'];

            bitacora::guardar(
                'ALTA ALMACÉN <strong>' . $infoalmacen[0]['ALMACEN_FOLIO'] . '</strong> ' . $infoalmacen[0]['ALMACEN_NOMBRE'] .
                    ' Campos:' . bitacora::printarray($infoalmacen[0]),
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $sucursal,
                $id_insertado // <- este es el que pega a FK
            );
        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }


    //EDITAR ALMACEN
    function editar($id, $nombre, $descripcion, $calle, $numext, $numint, $colonia, $cp, $tel, $pais, $estado, $municipio, $status, $tipo, $sucursal)
    {
        //CONSULTAR INFORMACION DE ALMACEN
        $infoalmacenori = $this->getinfoalmacen($id);
        $db = new FirebirdConnection(true);
        $sql = "
                update AMPAR_HIS_ALMACEN
                SET     
                    ALMACEN_NOMBRE = '" . $nombre . "',
                    ALMACEN_TIPOALMACEN = " . $tipo . ",
                    ALMACEN_SUCURSAL_MS = " . $sucursal . ",
                    ALMACEN_DESCRIPCION = '" . $descripcion . "',
                    ALMACEN_CALLE = '" . $calle . "',
                    ALMACEN_NUMEXT = '" . $numext . "',
                    ALMACEN_NUMINT = '" . $numint . "',
                    ALMACEN_COLONIA = '" . $colonia . "',
                    ALMACEN_PAIS = " . $pais . ",
                    ALMACEN_ESTADO = " . $estado . ",
                    ALMACEN_MUNICIPIO = " . $municipio . ",
                    ALMACEN_CP = '" . $cp . "',
                    ALMACEN_TELEFONO = '" . $tel . "',
                    ALMACEN_STATUS = " . $status . "
                WHERE ALMACEN_ID = " . $id . "
            ";
        $db->execute($sql);
        $db->close();
        //CONSULTAR INFORMACION DE ALMACEN
        $infoalmacenedit = $this->getinfoalmacen($id);
        //guardar en bitácora
        $usersesion =  $_SESSION['ampar']['usuario'];
        bitacora::guardar('EDICIÓN ALMACÉN <strong>' . $infoalmacenori[0]['ALMACEN_FOLIO'] . '</strong> ' . $infoalmacenori[0]['ALMACEN_NOMBRE'] . ' Campos:' . bitacora::printarraycomparacion($infoalmacenori[0], $infoalmacenedit[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $infoalmacenori[0]['ALMACEN_SUCURSAL_MS'], $id);
    }

    function getcatalogoalmacenes($sucursalid = "", $tipoalmacen = "", $todos = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE, ALMACEN_TIPOALMACEN, ALMACEN_SUCURSAL_MS
                FROM AMPAR_HIS_ALMACEN
                WHERE
                1 = 1
            ";
        if ($sucursalid <> "") {
            $sql .= " and ALMACEN_SUCURSAL_MS = " . $sucursalid;
        }
        if ($tipoalmacen <> "") {
            $sql .= " and ALMACEN_TIPOALMACEN IN (" . $tipoalmacen . ")";
        }
        if ($todos <> "") {
            $sql .= " and ALMACEN_STATUS = " . $todos;
        }
        $sql .= " order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getconceptosinventarios($naturaleza = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT CONCEPTOSAL_ID ID, CONCEPTOSAL_NOMBRE NOMBRE, CONCEPTOSAL_FILTRO FILTRO
                FROM AMPAR_CONF_CONCEPTOSAL 
                WHERE 1 = 1
                ";
        if ($naturaleza <> "") {
            $sql .= " AND CONCEPTOSAL_TIPO = '" . $naturaleza . "'";
        }
        $sql .= " order by CONCEPTOSAL_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function revisionalmacenesfaltantes($sucursalid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT FALTANTES.TIPOMALETADET_ARTICULOID, X.CLAVE_ARTICULO,  A.NOMBRE ARTICULONOMBRE, FALTANTES.FALTANTES, COALESCE(INVENTARIO.CANTIDADINVENTARIO,0) INVENTARIO, FALTANTES-COALESCE(INVENTARIO.CANTIDADINVENTARIO,0) COMPRAR FROM 
                (
                    SELECT al.TIPOMALETADET_ARTICULOID, al.CANTIDADINVENTARIO - COALESCE(CANTIDADMALETAS,0) FALTANTES  FROM 
                    (
                        SELECT TMD.TIPOMALETADET_ARTICULOID, SUM(TMD.TIPOMALETADET_CANTIDADSUGERIDA) CANTIDADINVENTARIO FROM AMPAR_HIS_TIPOMALETADET TMD
                        LEFT JOIN AMPAR_HIS_TIPOMALETA TM ON TM.TIPOMALETA_ID = TMD.TIPOMALETADET_TIPOMALETAID
                        WHERE TIPOMALETA_SUCURSALID = " . $sucursalid . "
                        GROUP BY TIPOMALETADET_ARTICULOID
                    ) al
                    LEFT JOIN (
                        SELECT ESDET_ARTICULOID, COUNT(*) CANTIDADMALETAS FROM AMPAR_HIS_STOCK
                        LEFT JOIN AMPAR_HIS_ESDET ON ESDET_ID = STOCK_ESDETID
                        LEFT JOIN AMPAR_HIS_ALMACEN ON ALMACEN_ID = STOCK_ALMACENIDACTUAL
                        WHERE 
                        STOCK_STOCKSTATUSID = 1
                        AND ALMACEN_TIPOALMACEN = 3
                        AND ALMACEN_SUCURSAL_MS = " . $sucursalid . "
                        GROUP BY ESDET_ARTICULOID
                    ) mal ON mal.ESDET_ARTICULOID = al.TIPOMALETADET_ARTICULOID
                    WHERE al.CANTIDADINVENTARIO - COALESCE(CANTIDADMALETAS,0) > 0
                ) FALTANTES
                LEFT JOIN (
                    SELECT ESDET_ARTICULOID, count(*) cantidadinventario FROM AMPAR_HIS_STOCK
                    LEFT JOIN AMPAR_HIS_ESDET ON ESDET_ID = STOCK_ESDETID
                    LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = STOCK_ALMACENIDACTUAL
                    WHERE 
                    STOCK_STOCKSTATUSID=1
                    AND ALMACEN_TIPOALMACEN in (1,2)
                    AND ALMACEN_SUCURSAL_MS = " . $sucursalid . "
                    GROUP BY ESDET_ARTICULOID 
                ) INVENTARIO ON INVENTARIO.ESDET_ARTICULOID = FALTANTES.TIPOMALETADET_ARTICULOID
                LEFT JOIN ARTICULOS A ON A.ARTICULO_ID = FALTANTES.TIPOMALETADET_ARTICULOID
                LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = A.ARTICULO_ID
                WHERE FALTANTES-COALESCE(INVENTARIO.CANTIDADINVENTARIO,0) > 0
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getalmacentipo()
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT TIPOALMACEN_ID ID, TIPOALMACEN_NOMBRE NOMBRE 
                FROM AMPAR_CONF_TIPOALMACEN
                WHERE TIPOALMACEN_ID <> 3
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getalmacentipotodos()
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT TIPOALMACEN_ID ID, TIPOALMACEN_NOMBRE NOMBRE FROM AMPAR_CONF_TIPOALMACEN";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getpaises()
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT PAIS_ID ID, NOMBRE FROM PAISES order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getestados($paisid)
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT ESTADO_ID ID, NOMBRE FROM ESTADOS where PAIS_ID = " . $paisid . " order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getmunicipios($estadoid)
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT CIUDAD_ID ID, NOMBRE FROM CIUDADES WHERE ESTADO_ID  = " . $estadoid . " order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getalmacenesporcategoria($categoriaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
                SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE
                FROM AMPAR_HIS_ALMACEN
                WHERE ALMACEN_SUCURSAL_MS = " . $categoriaid . "
                AND ALMACEN_TIPOALMACEN IN (1,2)
                AND ALMACEN_STATUS = 17
                ORDER BY ALMACEN_NOMBRE
            ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }
}

?>