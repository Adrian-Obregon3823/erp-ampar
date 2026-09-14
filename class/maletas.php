<?php
class maletas
{

    // ───── FAMILIAS ─────

    function nuevafamilia($nombre, $descripcion)
    {
        $db = new FirebirdConnection(true);
        $nombre_limpio = trim($nombre);
        $nombre_sql = str_replace("'", "''", $nombre_limpio);

        if ($nombre_limpio == '') {
            $db->close();
            return "El nombre de la familia es obligatorio";
        }

        // 1. Validar que no exista un duplicado
        $sqlExiste = "
            SELECT FIRST 1 GRUPO_LINEA_ID
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
              AND UPPER(TRIM(NOMBRE)) = UPPER('" . $nombre_sql . "')
        ";
        $existe = $db->query($sqlExiste);
        $hayDuplicado = is_array($existe) ? count($existe) > 0 : ($existe !== 0 && $existe !== null && $existe !== false);

        if ($hayDuplicado) {
            $db->close();
            return "Ya existe una familia con ese nombre.";
        }

        // 2. Calcular el siguiente ID disponible en tu bloque reservado
        $sqlMaxId = "
            SELECT MAX(GRUPO_LINEA_ID) AS MAX_ID
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
        ";
        $resultadoMax = $db->query($sqlMaxId);

        // Definimos el ID con el que empezará si no hay ningún registro previo
        $nuevo_id = 29701;

        // Extraemos el valor del MAX_ID. 
        // (La forma de extraerlo depende de cómo tu clase FirebirdConnection retorne los arreglos)
        if (is_array($resultadoMax) && isset($resultadoMax[0]['MAX_ID']) && $resultadoMax[0]['MAX_ID'] !== null) {
            $nuevo_id = $resultadoMax[0]['MAX_ID'] + 1;
        }

        // Opcional pero recomendado: Evitar pasarte de tu bloque de IDs
        if ($nuevo_id > 29799) {
            $db->close();
            return "Límite alcanzado: No se pueden crear más familias en el bloque web.";
        }

        // El GRUPO_LINEA_ID se asigna por IDENTITY de Microsip
        $sql = "
            INSERT INTO GRUPOS_LINEAS
            (
                GRUPO_LINEA_ID,
                NOMBRE,
                OCULTO
            )
            VALUES
            (
                " . $nuevo_id . ",
                '" . $nombre_sql . "',
                'N'
            )
        ";

        $db->execute($sql);
        $db->close();

        return "";
    }

    function getfamilias()
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT
                GRUPO_LINEA_ID AS FAMILIA_ID,
                NOMBRE AS FAMILIA_NOMBRE,
                '' AS FAMILIA_DESCRIPCION
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
            ORDER BY NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogofamilias()
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT
                GRUPO_LINEA_ID AS ID,
                NOMBRE
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
            ORDER BY NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function eliminarfamilia($familiaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            UPDATE GRUPOS_LINEAS
            SET OCULTO = 'S'
            WHERE GRUPO_LINEA_ID = " . (int)$familiaid . "
              AND GRUPO_LINEA_ID BETWEEN 29701 AND 29799
        ";
        $db->execute($sql);
        $db->close();
        return "";
    }

    function getinfofamiliabyid($familiaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT
                GRUPO_LINEA_ID AS FAMILIA_ID,
                NOMBRE AS FAMILIA_NOMBRE,
                '' AS FAMILIA_DESCRIPCION
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
              AND GRUPO_LINEA_ID = " . (int)$familiaid;
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function editarfamilia($familiaid, $nombre, $descripcion)
    {
        $db = new FirebirdConnection(true);
        $nombre_limpio = trim($nombre);
        $nombre_sql = str_replace("'", "''", $nombre_limpio);

        if ($nombre_limpio == '') {
            $db->close();
            return "El nombre de la familia es obligatorio";
        }

        $sqlExiste = "
            SELECT FIRST 1 GRUPO_LINEA_ID
            FROM GRUPOS_LINEAS
            WHERE GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
              AND UPPER(TRIM(NOMBRE)) = UPPER('" . $nombre_sql . "')
              AND GRUPO_LINEA_ID <> " . (int)$familiaid;
        $existe = $db->query($sqlExiste);
        $hayDuplicado = is_array($existe) ? count($existe) > 0 : ($existe !== 0 && $existe !== null && $existe !== false);
        if ($hayDuplicado) {
            $db->close();
            return "Ya existe una familia con ese nombre.";
        }

        $sql = "
            UPDATE GRUPOS_LINEAS
            SET NOMBRE = '" . $nombre_sql . "'
            WHERE GRUPO_LINEA_ID = " . (int)$familiaid . "
              AND GRUPO_LINEA_ID BETWEEN 29701 AND 29799
              AND COALESCE(OCULTO, 'N') = 'N'
        ";
        $db->execute($sql);
        $db->close();
        return "";
    }

    // ───── DIVISIONES ─────

    function nuevadivision($nombre, $descripcion, $familiaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            INSERT INTO AMPAR_CAT_DIVISION
            (
                DIVISION_NOMBRE,
                DIVISION_DESCRIPCION,
                DIVISION_FAMILIAID
            )
            VALUES
            (
                '" . $nombre . "',
                '" . $descripcion . "',
                " . ($familiaid != '' ? intval($familiaid) : 'NULL') . "
            )
        ";
        $db->execute($sql);
        $db->close();
    }

    function getdivisiones()
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT d.*, gl.NOMBRE AS FAMILIA_NOMBRE 
            FROM AMPAR_CAT_DIVISION d 
            LEFT JOIN GRUPOS_LINEAS gl ON gl.GRUPO_LINEA_ID = d.DIVISION_FAMILIAID
            WHERE d.DELETED_AT IS NULL 
            ORDER BY d.DIVISION_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogodivisiones($familiaid = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "SELECT DIVISION_ID ID, DIVISION_NOMBRE NOMBRE FROM AMPAR_CAT_DIVISION WHERE DELETED_AT IS NULL";
        if ($familiaid <> "") {
            $sql .= " AND DIVISION_FAMILIAID = " . $familiaid;
        }
        $sql .= " ORDER BY DIVISION_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function eliminardivision($divisionid)
    {
        $db = new FirebirdConnection(true);
        // Verificar que no tenga tipo maletas asociadas
        $sql0 = "SELECT COUNT(*) CONTADOR FROM AMPAR_HIS_TIPOMALETA WHERE TIPOMALETA_DIVISIONID = " . $divisionid . " AND DELETED_AT IS NULL";
        $result = $db->query($sql0);
        $db->close();
        if ($result[0]['CONTADOR'] > 0) {
            echo "No se puede eliminar: existen " . $result[0]['CONTADOR'] . " tipo(s) de maleta asignado(s) a esta división.";
        } else {
            $db2 = new FirebirdConnection(true);
            $sql = "UPDATE AMPAR_CAT_DIVISION SET DELETED_AT = CURRENT_TIMESTAMP WHERE DIVISION_ID = " . $divisionid;
            $db2->execute($sql);
        }
    }

    function getinfodivisionbyid($divisionid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT d.*, gl.NOMBRE AS FAMILIA_NOMBRE 
            FROM AMPAR_CAT_DIVISION d
            LEFT JOIN GRUPOS_LINEAS gl ON gl.GRUPO_LINEA_ID = d.DIVISION_FAMILIAID 
            WHERE d.DIVISION_ID = " . $divisionid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function editardivision($divisionid, $nombre, $descripcion, $familiaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            UPDATE AMPAR_CAT_DIVISION
            SET 
                DIVISION_NOMBRE = '" . $nombre . "',
                DIVISION_DESCRIPCION = '" . $descripcion . "',
                DIVISION_FAMILIAID = " . ($familiaid != '' ? intval($familiaid) : 'NULL') . "
            WHERE DIVISION_ID = " . $divisionid . "
        ";
        $db->execute($sql);
        $db->close();
    }


    function nuevotipomaleta($divisionid, $nombre, $descripcion, $articulos, $cantidades)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            Insert into AMPAR_HIS_TIPOMALETA
            (         
                TIPOMALETA_DIVISIONID,
                TIPOMALETA_NOMBRE,
                TIPOMALETA_DESCRIPCION
            )
            VALUES
            (
                " . $divisionid . ",
                '" . $nombre . "',
                '" . $descripcion . "'
            )
        ";
        $id_insertado = $db->executeconreturning($sql, 'TIPOMALETA_ID');
        $db->close();
        //Guardar en bitácora
        $infotipomaleta = $this->getinfotipomaletasindetallebyid($id_insertado);
        $usersesion =  $_SESSION['ampar']['usuario'];
        bitacora::guardar('ALTA TIPO DE MALETA <b>' . $nombre . '</b> Campos:' . bitacora::printarray($infotipomaleta[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], 0);
        if ($id_insertado) {
            if (!empty($articulos) and !empty($cantidades)) {
                $this->nuevotipomaletadet($id_insertado, $articulos, $cantidades);
            }
        } else {
            echo "Error al insertar registro.";
        }
    }

    // NUEVA MALETA (ALMACÉN tipo maleta)
    function nuevamaleta($sucursal, $tipomaleta, $nombre, $descripcion, $almacen_ms = null)
    {
        // Usa transacción explícita para asegurar visibilidad del padre antes de bitácora
        $db = new FirebirdConnection(false); // autoCommit = false

        try {
            // Genera el folio ML###. OJO: esto es concurrencia "MAX+1".
            // Idealmente usa una SEQUENCE + trigger. Ver nota abajo.
            $sql = "
                INSERT INTO AMPAR_HIS_ALMACEN
                (
                    ALMACEN_FOLIO,
                    ALMACEN_SUCURSAL_MS,
                    ALMACEN_ALMACEN_MS,
                    ALMACEN_TIPOMALETAID,
                    ALMACEN_NOMBRE,
                    ALMACEN_DESCRIPCION,
                    ALMACEN_TIPOALMACEN,
                    ALMACEN_STATUS
                )
                VALUES
                (
                    (
                        SELECT 'ML' ||
                            LPAD(
                                COALESCE(MAX(CAST(SUBSTRING(ALMACEN_FOLIO FROM 3) AS INTEGER)), 0) + 1,
                                3, '0'
                            )
                        FROM AMPAR_HIS_ALMACEN
                        WHERE ALMACEN_FOLIO STARTING WITH 'ML'
                    ),
                    ?, ?, ?, ?, ?,     -- parámetros
                    3,                 -- tipo almacén (maleta)
                    17                 -- status Activo
                )
            ";

            // Parámetros en orden: sucursal, almacen_ms, tipomaleta, nombre, descripcion
            $id_insertado = $db->executeconreturning($sql, 'ALMACEN_ID', [
                $sucursal,
                $almacen_ms,
                $tipomaleta,
                $nombre,
                $descripcion
            ]);

            if (!$id_insertado) {
                throw new Exception('No se devolvió ALMACEN_ID en el INSERT.');
            }

            // Confirma antes de consultar con otra conexión o escribir bitácora
            $db->commit();
            $db->close();

            // Ya confirmado, ahora sí puedes leer info y guardar bitácora
            $almacen     = new almacenes();
            $infoalmacen = $almacen->getinfoalmacen($id_insertado); // otra conexión
            $usersesion  = $_SESSION['ampar']['usuario'];

            bitacora::guardar(
                'ALTA MALETA <strong>' . $infoalmacen[0]['ALMACEN_FOLIO'] . '</strong> ' .
                    htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ' Campos:' .
                    bitacora::printarray($infoalmacen[0]),
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $sucursal,
                $id_insertado // <- FK a AMPAR_HIS_ALMACEN(ALMACEN_ID)
            );

            return $id_insertado;
        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }

    function edittipomaleta($tipomaletaid, $divisionid, $nombre, $descripcion, $articulos, $cantidades, $iddetallesexistentes = "", $cantidadesexistentes = "")
    {
        $infotipomaletaori = $this->getinfotipomaletasindetallebyid($tipomaletaid);
        $db = new FirebirdConnection(true);
        $sql = "
            update AMPAR_HIS_TIPOMALETA
            set        
                TIPOMALETA_DIVISIONID = " . $divisionid . ",
                TIPOMALETA_NOMBRE = '" . $nombre . "',
                TIPOMALETA_DESCRIPCION = '" . $descripcion . "'
            where
                TIPOMALETA_ID = " . $tipomaletaid . "
        ";
        $db->execute($sql);
        $db->close();
        $infotipomaletaedit = $this->getinfotipomaletasindetallebyid($tipomaletaid);
        //Guardar en bitácora datos generales
        $usersesion =  $_SESSION['ampar']['usuario'];
        bitacora::guardar('EDICIÓN DE TIPO DE MALETA <b>' . $infotipomaletaori[0]['TIPOMALETA_NOMBRE'] . '</b> Campos:' . bitacora::printarraycomparacion($infotipomaletaori[0], $infotipomaletaedit[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], 0);
        if (is_array($articulos) and is_array($cantidades)) {
            $this->nuevotipomaletadet($tipomaletaid, $articulos, $cantidades);
        }

        // Actualizar las cantidades de los artículos existentes
        if (is_array($iddetallesexistentes) and is_array($cantidadesexistentes)) {
            $db2 = new FirebirdConnection(true);
            for ($i = 0; $i < count($iddetallesexistentes); $i++) {
                $cant = (int)$cantidadesexistentes[$i];
                $id_det = (int)$iddetallesexistentes[$i];
                if ($cant > 0 && $id_det > 0) {
                    $sql_update = "UPDATE AMPAR_HIS_TIPOMALETADET SET TIPOMALETADET_CANTIDADSUGERIDA = " . $cant . " WHERE TIPOMALETADET_ID = " . $id_det;
                    $db2->execute($sql_update);
                }
            }
            $db2->close();
        }
    }

    function editmaleta($maletaid, $sucalm, $tipomaleta, $nombre, $descripcion, $status)
    {
        $maletaid = (int)$maletaid;
        $sucalm = (int)$sucalm;
        $tipomaleta = (int)$tipomaleta;
        $status = (int)$status;

        // Si se desactiva (status 18), regresar el stock al almacén de origen ($sucalm)
        if ($status == 18 && $sucalm > 0) {
            $dbStock = new FirebirdConnection(true);
            $sqlStock = "UPDATE AMPAR_HIS_STOCK 
                         SET STOCK_ALMACENIDACTUAL = " . $sucalm . " 
                         WHERE STOCK_ALMACENIDACTUAL = " . $maletaid . " 
                           AND STOCK_STOCKSTATUSID IN (1, 2)";
            $dbStock->execute($sqlStock);
            $dbStock->close();
        }

        //CONSULTAR INFORMACION DE MALETA
        $almacen = new almacenes();
        $infoalmacenori = $almacen->getinfoalmacen($maletaid);
        $db = new FirebirdConnection(true);
        $sql = "
            update AMPAR_HIS_ALMACEN
            set         
                ALMACEN_ALMACEN_MS = " . $sucalm . ",
                ALMACEN_TIPOMALETAID = " . $tipomaleta . ",
                ALMACEN_NOMBRE = '" . $nombre . "',
                ALMACEN_DESCRIPCION = '" . $descripcion . "',
                ALMACEN_STATUS = " . $status . "
            where ALMACEN_ID = " . $maletaid . "
        ";
        $db->execute($sql);
        $db->close();
        //CONSULTAR INFORMACION DE MALETA
        $infoalmacenedit = $almacen->getinfoalmacen($maletaid);
        //guardar en bitácora
        $usersesion =  $_SESSION['ampar']['usuario'];
        bitacora::guardar('EDICIÓN MALETA <strong>' . $infoalmacenori[0]['ALMACEN_FOLIO'] . '</strong> ' . $infoalmacenori[0]['ALMACEN_NOMBRE'] . ' Campos:' . bitacora::printarraycomparacion($infoalmacenori[0], $infoalmacenedit[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $infoalmacenori[0]['ALMACEN_SUCURSAL_MS'], $maletaid);
    }

    function nuevotipomaletadet($idtipomaleta, $articulos, $cantidades)
    {
        $db = new FirebirdConnection(true);
        for ($i = 0; $i < count($articulos); $i++) {
            $sql = "
                insert into AMPAR_HIS_TIPOMALETADET
                (
                    TIPOMALETADET_TIPOMALETAID,
                    TIPOMALETADET_ARTICULOID,
                    TIPOMALETADET_CANTIDADSUGERIDA
                )
                VALUES
                (
                    " . $idtipomaleta . ",
                    " . $articulos[$i] . ",
                    " . $cantidades[$i] . "
                )
            ";
            $id_insertado = $db->executeconreturning($sql, 'TIPOMALETADET_ID');
            //Guardar en bitácora 
            $detallemaleta = $this->getinfodetalledemaletabyiddetallemaleta($db, $id_insertado);
            $usersesion =  $_SESSION['ampar']['usuario'];
            bitacora::guardardb($db, 'ALTA ARTÍCULO <b>' . $detallemaleta[0]['CLAVE_ARTICULO'] . ' - ' . $detallemaleta[0]['ARTICULO_NOMBRE'] . '</b> Campos:' . bitacora::printarray($detallemaleta[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $detallemaleta[0]['SUCURSAL_ID']);
        }
        $db->close();
    }

    function eliminartipomaleta($idtipomaleta)
    {
        $infotipomaleta = $this->getinfotipomaletasindetallebyid($idtipomaleta);
        $db = new FirebirdConnection(true);
        //Revisar que no haya almacenes definidos con este tipo de maleta
        $sql0 = "select count(*) CONTADOR from AMPAR_HIS_ALMACEN WHERE ALMACEN_TIPOMALETAID = " . $idtipomaleta . " AND DELETED_AT IS NULL";
        $result = $db->query($sql0);
        $db->close();
        if ($result[0]['CONTADOR'] > 0) {
            echo "No se puede eliminar <b>" . $infotipomaleta[0]['TIPOMALETA_NOMBRE'] . "</b>.<br>Existe(n) " . $result[0]['CONTADOR'] . " maletas con este identificador";
        } else {
            // No borramos el detalle para mantener historial si es soft delete
            /*
            $infomaleta = $this->getinfotipomaletabyid($idtipomaleta);
            if ($infomaleta[0]['TIPOMALETADET_ID'] <> '') {
                foreach ($infomaleta as $im) {
                    $this->eliminartipomaletadet($im['TIPOMALETADET_ID']);
                }
            }
            */
            $db = new FirebirdConnection(true);
            $sql = "
                UPDATE AMPAR_HIS_TIPOMALETA SET DELETED_AT = CURRENT_TIMESTAMP
                where
                TIPOMALETA_ID = " . $idtipomaleta . "
            ";
            $db->execute($sql);
            $db->close();
            //Guardar en bitácora
            $usersesion =  $_SESSION['ampar']['usuario'];
            bitacora::guardar('TIPO DE MALETA <b>' . $infotipomaleta[0]['TIPOMALETA_NOMBRE'] . '</b>, eliminado (Soft Delete). Campos:' . bitacora::printarray($infotipomaleta[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $infotipomaleta[0]['TIPOMALETA_SUCURSALID']);
        }
    }

    function eliminarmaleta($maletaid)
    {
        $db = new FirebirdConnection(true);

        // 1. Obtener el almacén de origen (ALMACEN_ALMACEN_MS)
        $sqlParent = "SELECT ALMACEN_ALMACEN_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = " . (int)$maletaid;
        $result = $db->query($sqlParent);
        if (is_array($result) && !empty($result)) {
            $parentAlmacenId = $result[0]['ALMACEN_ALMACEN_MS'];
            if ($parentAlmacenId) {
                // 2. Regresar el stock al almacén de origen
                $sqlStock = "UPDATE AMPAR_HIS_STOCK 
                             SET STOCK_ALMACENIDACTUAL = " . (int)$parentAlmacenId . " 
                             WHERE STOCK_ALMACENIDACTUAL = " . (int)$maletaid . " 
                               AND STOCK_STOCKSTATUSID IN (1, 2)";
                $db->execute($sqlStock);
            }
        }

        // 3. Cambiar status a 18 (inactivo) y poner DELETED_AT
        $sql = "
            UPDATE AMPAR_HIS_ALMACEN 
            SET DELETED_AT = CURRENT_TIMESTAMP,
                ALMACEN_STATUS = 18
            where
            ALMACEN_ID = " . $maletaid . "
        ";
        $db->execute($sql);
        $db->close();
    }

    function activarmaleta($maletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            UPDATE AMPAR_HIS_ALMACEN 
            SET DELETED_AT = NULL,
                ALMACEN_STATUS = 17
            where
            ALMACEN_ID = " . $maletaid . "
        ";
        $db->execute($sql);
        $db->close();
    }

    function eliminartipomaletadet($idtipomaletadet)
    {
        $db = new FirebirdConnection(true);
        $detallemaleta = $this->getinfodetalledemaletabyiddetallemaleta($db, $idtipomaletadet);
        $sql = "
            delete from AMPAR_HIS_TIPOMALETADET
            where
            TIPOMALETADET_ID = " . $idtipomaletadet . "
        ";
        $db->execute($sql);
        //Guardar en bitácora 
        $usersesion =  $_SESSION['ampar']['usuario'];
        bitacora::guardar('ARTÍCULO ELIMINADO EN TIPO DE MALETA <b>' . $detallemaleta[0]['CLAVE_ARTICULO'] . ' - ' . $detallemaleta[0]['ARTICULO_NOMBRE'] . '</b> Campos:' . bitacora::printarray($detallemaleta[0]), $usersesion['USUARIO_ID'], $usersesion['USUARIO_CORREO'], $detallemaleta[0]['SUCURSAL_ID']);
        $db->close();
    }

    function getinfotipomaletabyid($tipomaletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT 
                tm.*, tmd.*, d.DIVISION_ID, d.DIVISION_NOMBRE, d.DIVISION_FAMILIAID, f.NOMBRE AS FAMILIA_NOMBRE, ar.NOMBRE ARTICULO_NOMBRE, CA.CLAVE_ARTICULO
            FROM AMPAR_HIS_TIPOMALETA tm
            LEFT JOIN AMPAR_HIS_TIPOMALETADET tmd ON TIPOMALETADET_TIPOMALETAID = TIPOMALETA_ID
            LEFT JOIN AMPAR_CAT_DIVISION d ON d.DIVISION_ID = tm.TIPOMALETA_DIVISIONID
            LEFT JOIN GRUPOS_LINEAS f ON f.GRUPO_LINEA_ID = d.DIVISION_FAMILIAID
            LEFT JOIN ARTICULOS ar ON ARTICULO_ID = TIPOMALETADET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) CA on CA.ARTICULO_ID = ar.ARTICULO_ID
            WHERE TIPOMALETA_ID = " . $tipomaletaid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfotipomaletasindetallebyid($tipomaletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT 
                tm.*, d.DIVISION_ID, d.DIVISION_NOMBRE, d.DIVISION_FAMILIAID, f.NOMBRE AS FAMILIA_NOMBRE
            FROM AMPAR_HIS_TIPOMALETA tm
            LEFT JOIN AMPAR_CAT_DIVISION d ON d.DIVISION_ID = tm.TIPOMALETA_DIVISIONID
            LEFT JOIN GRUPOS_LINEAS f ON f.GRUPO_LINEA_ID = d.DIVISION_FAMILIAID
            WHERE TIPOMALETA_ID = " . $tipomaletaid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    //GET PORCENTAJE DE CADA MALETA DE ACUERDO A LA PLANTILLA
    function getporcentajebymaleta($maletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            -- ARTÍCULOS DE LA PLANTILLA
            SELECT 
                SU.SUCURSAL_ID, SU.NOMBRE AS SUCURSAL_NOMBRE, 
                A.ALMACEN_ID, A.ALMACEN_FOLIO, A.ALMACEN_NOMBRE, 
                TM.TIPOMALETA_ID, TM.TIPOMALETA_NOMBRE, 
                TMD.TIPOMALETADET_ID, TMD.TIPOMALETADET_ARTICULOID, TMD.TIPOMALETADET_CANTIDADSUGERIDA, 
                AR.NOMBRE AS ARTICULO_NOMBRE, AR.SEGUIMIENTO, 
                X.ARTICULO_CLAVE,
                (SELECT COUNT(*) 
                FROM AMPAR_HIS_STOCK ST 
                WHERE ST.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                AND ST.STOCK_ARTICULOID = TMD.TIPOMALETADET_ARTICULOID
                AND ST.STOCK_STOCKSTATUSID IN (1,2)
                ) AS EXISTENCIA,
                CASE 
                    WHEN TMD.TIPOMALETADET_CANTIDADSUGERIDA IS NULL OR TMD.TIPOMALETADET_CANTIDADSUGERIDA = 0 THEN 0
                    ELSE ROUND(
                        (
                            CASE 
                                WHEN (
                                    SELECT COUNT(*) 
                                    FROM AMPAR_HIS_STOCK ST 
                                    WHERE ST.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                                    AND ST.STOCK_ARTICULOID = TMD.TIPOMALETADET_ARTICULOID
                                    AND ST.STOCK_STOCKSTATUSID IN (1,2)
                                ) > TMD.TIPOMALETADET_CANTIDADSUGERIDA
                                THEN TMD.TIPOMALETADET_CANTIDADSUGERIDA
                                ELSE (
                                    SELECT COUNT(*) 
                                    FROM AMPAR_HIS_STOCK ST 
                                    WHERE ST.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                                    AND ST.STOCK_ARTICULOID = TMD.TIPOMALETADET_ARTICULOID
                                    AND ST.STOCK_STOCKSTATUSID IN (1,2)
                                )
                            END
                        ) * 100.0 / TMD.TIPOMALETADET_CANTIDADSUGERIDA, 2
                    )
                END AS PORCENTAJE
            FROM AMPAR_HIS_ALMACEN A
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS 
            LEFT JOIN AMPAR_HIS_TIPOMALETA TM ON A.ALMACEN_TIPOMALETAID = TM.TIPOMALETA_ID 
            LEFT JOIN AMPAR_HIS_TIPOMALETADET TMD ON TMD.TIPOMALETADET_TIPOMALETAID = TM.TIPOMALETA_ID 
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = TMD.TIPOMALETADET_ARTICULOID 
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO ARTICULO_CLAVE, ARTICULO_ID 
                FROM claves_articulos 
                WHERE ROL_CLAVE_ART_ID = 17
            ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE A.ALMACEN_ID = " . $maletaid . "
            UNION ALL
            -- ARTÍCULOS EN STOCK QUE NO ESTÁN EN LA PLANTILLA
            SELECT 
                SU.SUCURSAL_ID, SU.NOMBRE AS SUCURSAL_NOMBRE, 
                A.ALMACEN_ID, A.ALMACEN_FOLIO, A.ALMACEN_NOMBRE, 
                NULL AS TIPOMALETA_ID, NULL AS TIPOMALETA_NOMBRE, 
                NULL AS TIPOMALETADET_ID, ST.STOCK_ARTICULOID AS TIPOMALETADET_ARTICULOID, NULL AS TIPOMALETADET_CANTIDADSUGERIDA, 
                AR.NOMBRE AS ARTICULO_NOMBRE, AR.SEGUIMIENTO, 
                X.ARTICULO_CLAVE,
                COUNT(*) AS EXISTENCIA,
                0 AS PORCENTAJE
            FROM AMPAR_HIS_ALMACEN A
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS 
            LEFT JOIN AMPAR_HIS_STOCK ST ON ST.STOCK_ALMACENIDACTUAL = A.ALMACEN_ID 
                AND ST.STOCK_STOCKSTATUSID IN (1,2)
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID 
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO ARTICULO_CLAVE, ARTICULO_ID 
                FROM claves_articulos 
                WHERE ROL_CLAVE_ART_ID = 17
            ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE A.ALMACEN_ID = " . $maletaid . "
            AND ST.STOCK_ARTICULOID NOT IN (
                    SELECT TIPOMALETADET_ARTICULOID 
                    FROM AMPAR_HIS_TIPOMALETADET TMD
                    INNER JOIN AMPAR_HIS_TIPOMALETA TM ON TMD.TIPOMALETADET_TIPOMALETAID = TM.TIPOMALETA_ID
                    WHERE TM.TIPOMALETA_ID = A.ALMACEN_TIPOMALETAID
            )
            GROUP BY SU.SUCURSAL_ID, SU.NOMBRE, 
                    A.ALMACEN_ID, A.ALMACEN_FOLIO, A.ALMACEN_NOMBRE, 
                    AR.NOMBRE, AR.SEGUIMIENTO, X.ARTICULO_CLAVE, ST.STOCK_ARTICULOID;
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfodetalledemaletabyiddetallemaleta($db, $iddetallemaleta)
    {
        $sql = "
            SELECT TMD.TIPOMALETADET_ID, TMD.TIPOMALETADET_TIPOMALETAID, TM.TIPOMALETA_NOMBRE, S.SUCURSAL_ID, S.NOMBRE, AR.ARTICULO_ID, AR.NOMBRE ARTICULO_NOMBRE, CA.CLAVE_ARTICULO, TMD.TIPOMALETADET_CANTIDADSUGERIDA 
            FROM AMPAR_HIS_TIPOMALETADET TMD
            LEFT JOIN AMPAR_HIS_TIPOMALETA TM ON TM.TIPOMALETA_ID = TMD.TIPOMALETADET_TIPOMALETAID
            LEFT JOIN ampar_cat_sucursales S ON S.SUCURSAL_ID = TM.TIPOMALETA_SUCURSALID 
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = TMD.TIPOMALETADET_ARTICULOID 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) CA on CA.ARTICULO_ID = AR.ARTICULO_ID
            WHERE TMD.TIPOMALETADET_ID = " . $iddetallemaleta . "
        ";
        $result = $db->query($sql);
        return $result;
    }

    function getmaletasfolioentipomaleta($tipomaletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT ALMACEN_FOLIO, ALMACEN_NOMBRE, ALMACEN_STATUS, STATUS_NOMBRE, STATUS_COLOR
            FROM AMPAR_HIS_ALMACEN 
            LEFT JOIN AMPAR_CONF_STATUS ON STATUS_ID = ALMACEN_STATUS 
            WHERE ALMACEN_TIPOMALETAID = " . $tipomaletaid . " AND AMPAR_HIS_ALMACEN.DELETED_AT IS NULL
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function gettipomaletas()
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT tm.*, d.DIVISION_ID, d.DIVISION_NOMBRE 
            FROM AMPAR_HIS_TIPOMALETA tm
            LEFT JOIN AMPAR_CAT_DIVISION d ON d.DIVISION_ID = tm.TIPOMALETA_DIVISIONID
            WHERE tm.DELETED_AT IS NULL
            ORDER BY tm.TIPOMALETA_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogotipomaletas($divisionid = "")
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT TIPOMALETA_ID ID, TIPOMALETA_NOMBRE NOMBRE FROM AMPAR_HIS_TIPOMALETA 
            LEFT JOIN AMPAR_CAT_DIVISION d ON d.DIVISION_ID = TIPOMALETA_DIVISIONID
            WHERE AMPAR_HIS_TIPOMALETA.DELETED_AT IS NULL
        ";
        if ($divisionid <> "") {
            $sql .= " AND TIPOMALETA_DIVISIONID = " . $divisionid;
        }
        $sql .= " ORDER BY TIPOMALETA_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getmaletas($sucursales_ids = [], $ver_desactivados = false)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT 
                a.*,
                s.NOMBRE SUCURSAL_NOMBRE,
                tm.*,
                pa.ALMACEN_NOMBRE PADRE_ALMACEN_NOMBRE,
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
                    ) c ON c.ARTICULO_ID = tmd.TIPOMALETADET_ARTICULOID AND c.STOCK_ALMACENIDACTUAL = a.ALMACEN_ID
                    WHERE tmd.TIPOMALETADET_TIPOMALETAID = a.ALMACEN_TIPOMALETAID
                ) AS PORCENTAJE
            FROM AMPAR_HIS_ALMACEN a
            LEFT JOIN ampar_cat_sucursales s ON s.SUCURSAL_ID = a.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_HIS_TIPOMALETA tm ON tm.TIPOMALETA_ID = a.ALMACEN_TIPOMALETAID
            LEFT JOIN AMPAR_HIS_ALMACEN pa ON pa.ALMACEN_ID = a.ALMACEN_ALMACEN_MS
            WHERE a.ALMACEN_TIPOALMACEN = 3
        ";
        if ($ver_desactivados) {
            $sql .= " AND (a.DELETED_AT IS NOT NULL OR a.ALMACEN_STATUS = 18)";
        } else {
            $sql .= " AND a.DELETED_AT IS NULL AND (a.ALMACEN_STATUS IS NULL OR a.ALMACEN_STATUS <> 18)";
        }

        if (!empty($sucursales_ids)) {
            $sql .= " AND a.ALMACEN_SUCURSAL_MS IN (" . implode(',', $sucursales_ids) . ")";
        }
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfomaleta($maletaid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT a.*, tm.*, s.NOMBRE SUCURSAL_NOMBRE, a.ALMACEN_NOMBRE, ST.*, d.DIVISION_NOMBRE, d.DIVISION_FAMILIAID, f.NOMBRE AS FAMILIA_NOMBRE,
                   pa.ALMACEN_NOMBRE PADRE_ALMACEN_NOMBRE, pa.ALMACEN_TIPOALMACEN PADRE_TIPOALMACEN, pta.TIPOALMACEN_NOMBRE PADRE_TIPOALMACEN_NOMBRE 
            FROM AMPAR_HIS_ALMACEN a
            LEFT JOIN AMPAR_HIS_TIPOMALETA tm ON tm.TIPOMALETA_ID = a.ALMACEN_TIPOMALETAID
            LEFT JOIN ampar_cat_sucursales s ON s.SUCURSAL_ID = a.ALMACEN_SUCURSAL_MS
            LEFT JOIN AMPAR_CONF_STATUS ST ON ST.STATUS_ID = a.ALMACEN_STATUS
            LEFT JOIN AMPAR_CAT_DIVISION d ON d.DIVISION_ID = tm.TIPOMALETA_DIVISIONID
            LEFT JOIN GRUPOS_LINEAS f ON f.GRUPO_LINEA_ID = d.DIVISION_FAMILIAID
            LEFT JOIN AMPAR_HIS_ALMACEN pa ON pa.ALMACEN_ID = a.ALMACEN_ALMACEN_MS
            LEFT JOIN AMPAR_CONF_TIPOALMACEN pta ON pta.TIPOALMACEN_ID = pa.ALMACEN_TIPOALMACEN
            WHERE a.ALMACEN_ID = " . $maletaid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfomaletaexistenciasbymaletaid($almacenid)
    {
        $db = new FirebirdConnection(true);
        $sql = "
            SELECT 
                ALMACEN_FOLIO,
                INV.ARTICULO_ID,
                CA.CLAVE_ARTICULO,
                AR.NOMBRE ARTICULO_NOMBRE,
                INV.CANTIDAD_ACTUAL,
                INV.CANTIDAD_SUGERIDA,
                INV.PORCENTAJE_LLENADO,
                STOCK_FOLIO,
                STOCK_STOCKSTATUSID,
                ESDET_LOTE,
                ESDET_CADUCIDAD,
                ESDET_SERIE
            FROM 
            (
                SELECT 
                    sugeridos.ARTICULO_ID,
                    COALESCE(inventario.CANTIDAD_ACTUAL, 0) AS CANTIDAD_ACTUAL,
                    sugeridos.CANTIDAD_SUGERIDA,
                    CASE 
                        WHEN sugeridos.CANTIDAD_SUGERIDA = 0 THEN 0
                        ELSE 
                            CASE 
                                WHEN (COALESCE(inventario.CANTIDAD_ACTUAL, 0) * 100 / sugeridos.CANTIDAD_SUGERIDA) > 100 
                                THEN 100
                                ELSE (COALESCE(inventario.CANTIDAD_ACTUAL, 0) * 100 / sugeridos.CANTIDAD_SUGERIDA)
                            END
                    END AS PORCENTAJE_LLENADO
                FROM (
                    SELECT 
                        TIPOMALETADET_ARTICULOID AS ARTICULO_ID,
                        TIPOMALETADET_CANTIDADSUGERIDA AS CANTIDAD_SUGERIDA
                    FROM AMPAR_HIS_ALMACEN 
                    LEFT JOIN AMPAR_HIS_TIPOMALETA ON TIPOMALETA_ID = ALMACEN_TIPOMALETAID
                    LEFT JOIN AMPAR_HIS_TIPOMALETADET ON TIPOMALETADET_TIPOMALETAID = TIPOMALETA_ID
                    WHERE ALMACEN_ID = " . $almacenid . "
                ) AS sugeridos
                LEFT JOIN (
                    SELECT 
                        ESDET_ARTICULOID AS ARTICULO_ID,
                        COUNT(*) AS CANTIDAD_ACTUAL
                    FROM AMPAR_HIS_STOCK
                    LEFT JOIN AMPAR_HIS_ESDET ON ESDET_ID = STOCK_ESDETID
                    WHERE STOCK_ALMACENIDACTUAL = " . $almacenid . "
                    AND STOCK_STOCKSTATUSID IN (1, 2)
                    GROUP BY ESDET_ARTICULOID
                ) AS inventario ON sugeridos.ARTICULO_ID = inventario.ARTICULO_ID
            ) INV
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = INV.ARTICULO_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) CA on CA.ARTICULO_ID = ar.ARTICULO_ID
            LEFT JOIN (
                SELECT * FROM AMPAR_HIS_STOCK 
                LEFT JOIN AMPAR_HIS_ESDET ON ESDET_ID = STOCK_ESDETID
                LEFT JOIN AMPAR_HIS_ALMACEN ON ALMACEN_ID = STOCK_ALMACENIDACTUAL
                WHERE STOCK_ALMACENIDACTUAL = " . $almacenid . "
                AND STOCK_STOCKSTATUSID IN (1, 2)
            ) ED ON ED.ESDET_ARTICULOID = INV.ARTICULO_ID
            UNION ALL
            SELECT 
                ALMACEN_FOLIO,
                ESDET_ARTICULOID,
                CLAVE_ARTICULO,
                AR.NOMBRE ARTICULO_NOMBRE,
                (SELECT count(*) FROM AMPAR_HIS_STOCK STIN LEFT JOIN AMPAR_HIS_ESDET EDINT ON EDINT.ESDET_ID = STIN.STOCK_ESDETID WHERE STIN.STOCK_ALMACENIDACTUAL = " . $almacenid . " AND STIN.STOCK_STOCKSTATUSID IN (1, 2) AND EDINT.ESDET_ARTICULOID = ED.ESDET_ARTICULOID ) CANTIDAD_ACTUAL,
                0 CANTIDAD_SUGERIDA,
                0 PORCENTAJE_LLENADO,
                STOCK_FOLIO,
                STOCK_STOCKSTATUSID,
                ESDET_LOTE,
                ESDET_CADUCIDAD,
                ESDET_SERIE
            FROM AMPAR_HIS_STOCK
            LEFT JOIN AMPAR_HIS_ALMACEN ON ALMACEN_ID = STOCK_ALMACENIDACTUAL
            LEFT JOIN AMPAR_HIS_TIPOMALETA ON TIPOMALETA_ID = ALMACEN_TIPOMALETAID
            LEFT JOIN AMPAR_HIS_ESDET ED ON ESDET_ID = STOCK_ESDETID
            LEFT JOIN AMPAR_HIS_TIPOMALETADET ON TIPOMALETADET_TIPOMALETAID = TIPOMALETA_ID AND ESDET_ARTICULOID = TIPOMALETADET_ARTICULOID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ESDET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) CA on CA.ARTICULO_ID = ar.ARTICULO_ID
            WHERE 
            STOCK_ALMACENIDACTUAL = " . $almacenid . "
            AND STOCK_STOCKSTATUSID IN (1, 2)
            AND TIPOMALETADET_ARTICULOID IS NULL
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogomaletas($sucid = "")
    {
        $db = new FirebirdConnection(true);
        $sql1 = "
           SELECT 
                a.ALMACEN_ID ID,
                CAST(a.ALMACEN_NOMBRE AS VARCHAR(255) CHARACTER SET UTF8) NOMBRE,
                a.ALMACEN_FOLIO FOLIO,
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
                    ) c ON c.ARTICULO_ID = tmd.TIPOMALETADET_ARTICULOID AND c.STOCK_ALMACENIDACTUAL = a.ALMACEN_ID
                    WHERE tmd.TIPOMALETADET_TIPOMALETAID = a.ALMACEN_TIPOMALETAID
                ) AS PORCENTAJE
            FROM AMPAR_HIS_ALMACEN a
            WHERE 
            ALMACEN_TIPOALMACEN = 3 AND DELETED_AT IS NULL AND (a.ALMACEN_STATUS IS NULL OR a.ALMACEN_STATUS <> 18)
        ";

        if ($sucid <> "") {
            $sql1 .= " AND a.ALMACEN_SUCURSAL_MS = " . (int)$sucid;
        }

        $sql1 .= " ORDER BY 2";
        $result = $db->query($sql1);
        $db->close();
        return $result;
    }
}
