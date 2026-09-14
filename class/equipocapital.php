<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2026
* Clase para el módulo de Equipo Capital
*********************************************************************************
*/

class equipocapital {

    /**
     * Obtiene todas las instancias registradas de Equipo Capital
     */
    public function getArticulos($familia_id = '', $categoria_id = '', $mostrar_inactivos = '0') {
        $db = new FirebirdConnection();
        $sql = "SELECT ec.EQUIPOCAPITAL_ID, 
                       ec.ARTICULO_ID, 
                       ec.FOLIO,
                       ec.REFERENCIA, 
                       ec.MARCA,
                       ec.UBICACION, 
                       ec.ALMACEN_ID,
                       alm.ALMACEN_NOMBRE,
                       (
                           COALESCE((SELECT COUNT(*) 
                            FROM AMPAR_HIS_EVENTOSMALETAS EM 
                            INNER JOIN AMPAR_HIS_EVENTOS EV ON EV.EVENTO_ID = EM.EVENTOMALETA_EVENTOID 
                            WHERE EM.EVENTOMALETA_MALETAID = -ec.EQUIPOCAPITAL_ID 
                            AND EV.EVENTO_STATUSGENERAL NOT IN (3, 5)), 0)
                           +
                           COALESCE((SELECT COUNT(*)
                            FROM AMPAR_HIS_PROYECTOSMALETAS PM
                            INNER JOIN AMPAR_HIS_PROYECTOS PR ON PR.PROYECTO_ID = PM.PROYECTOMALETA_PROYECTOID
                            WHERE PM.PROYECTOMALETA_MALETAID = -ec.EQUIPOCAPITAL_ID
                            AND PR.PROYECTO_STATUSGENERAL NOT IN (3, 5)), 0)
                       ) AS CANTIDAD_EVENTOS_ACTIVOS,
                       (CASE WHEN EXISTS (
                           SELECT 1 
                           FROM AMPAR_HIS_EVENTOSMALETAS EM 
                           INNER JOIN AMPAR_HIS_EVENTOS EV ON EV.EVENTO_ID = EM.EVENTOMALETA_EVENTOID 
                           WHERE EM.EVENTOMALETA_MALETAID = -ec.EQUIPOCAPITAL_ID 
                           AND EV.EVENTO_STATUSGENERAL NOT IN (3, 5)
                       ) OR EXISTS (
                           SELECT 1
                           FROM AMPAR_HIS_PROYECTOSMALETAS PM
                           INNER JOIN AMPAR_HIS_PROYECTOS PR ON PR.PROYECTO_ID = PM.PROYECTOMALETA_PROYECTOID
                           WHERE PM.PROYECTOMALETA_MALETAID = -ec.EQUIPOCAPITAL_ID
                           AND PR.PROYECTO_STATUSGENERAL NOT IN (3, 5)
                       ) THEN 'S' ELSE 'N' END) AS OCUPADO,
                       ec.ESTATUS,  
                       (SELECT LIST(TIPOEVENTO_ID, ',') FROM AMPAR_REL_EQUIPO_TIPOEVENTO WHERE EQUIPOCAPITAL_ID = ec.EQUIPOCAPITAL_ID) AS TIPOEVENTO_ID,
                       (SELECT LIST(TE2.TIPOEVENTO_NOMBRE, ', ') FROM AMPAR_REL_EQUIPO_TIPOEVENTO R INNER JOIN AMPAR_CAT_TIPOEVENTO TE2 ON R.TIPOEVENTO_ID = TE2.TIPOEVENTO_ID WHERE R.EQUIPOCAPITAL_ID = ec.EQUIPOCAPITAL_ID) AS TIPOEVENTO_NOMBRE,
                       a.NOMBRE AS NOMBRE,
                       la.NOMBRE AS CATEGORIA_NOMBRE,
                       gl.NOMBRE AS FAMILIA_NOMBRE,
                       la.LINEA_ARTICULO_ID,
                       gl.GRUPO_LINEA_ID,
                       X.CLAVE_ARTICULO AS CATALOG_REFERENCIA
                FROM AMPAR_EQUIPOCAPITAL AS ec
                INNER JOIN ARTICULOS AS a ON ec.ARTICULO_ID = a.ARTICULO_ID
                LEFT JOIN LINEAS_ARTICULOS AS la ON a.LINEA_ARTICULO_ID = la.LINEA_ARTICULO_ID
                LEFT JOIN GRUPOS_LINEAS AS gl ON la.GRUPO_LINEA_ID = gl.GRUPO_LINEA_ID
                LEFT JOIN AMPAR_HIS_ALMACEN AS alm ON ec.ALMACEN_ID = alm.ALMACEN_ID
                LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = a.ARTICULO_ID";

        $where = [];
        if ($mostrar_inactivos != '1') {
            $where[] = "ec.ESTATUS = 'A'";
        }

        if ($familia_id != '') {
            $where[] = "la.GRUPO_LINEA_ID = " . (int)$familia_id;
        }

        if ($categoria_id != '') {
            $where[] = "a.LINEA_ARTICULO_ID = " . (int)$categoria_id;
        }

        if (count($where) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY ec.EQUIPOCAPITAL_ID DESC";

        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    /**
     * Guarda o actualiza un registro de equipo capital (instancia física)
     */
    public function guardarEquipoCapital($equipocapital_id, $articulo_id, $referencia, $ubicacion, $marca = '', $tipoeventos_ids = null, $almacen_id = null) {
        $db = new FirebirdConnection();
        $almacen_id = empty($almacen_id) ? null : intval($almacen_id);
        if ($equipocapital_id > 0) {
            $sql = "UPDATE AMPAR_EQUIPOCAPITAL 
                    SET REFERENCIA = ?, UBICACION = ?, MARCA = ?, ALMACEN_ID = ?
                    WHERE EQUIPOCAPITAL_ID = ?";
            $success = $db->execute($sql, [$referencia, $ubicacion, $marca, $almacen_id, $equipocapital_id]);
        } else {
            // Fetch next generator ID
            $res = $db->query("SELECT GEN_ID(GEN_AMPAR_EQUIPOCAPITAL_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
            $next_id = intval($res[0]['NEXT_ID']);
            
            // Format folio
            $folio = "EQ-" . str_pad($next_id, 5, "0", STR_PAD_LEFT);
            $equipocapital_id = $next_id;

            $sql = "INSERT INTO AMPAR_EQUIPOCAPITAL (EQUIPOCAPITAL_ID, ARTICULO_ID, FOLIO, REFERENCIA, MARCA, UBICACION, ALMACEN_ID, OCUPADO, ESTATUS) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'N', 'A')";
            $success = $db->execute($sql, [$next_id, $articulo_id, $folio, $referencia, $marca, $ubicacion, $almacen_id]);
        }
        
        if ($success) {
            $db->execute("DELETE FROM AMPAR_REL_EQUIPO_TIPOEVENTO WHERE EQUIPOCAPITAL_ID = ?", [$equipocapital_id]);
            
            if (!empty($tipoeventos_ids)) {
                if (!is_array($tipoeventos_ids)) {
                    $tipoeventos_ids = explode(',', $tipoeventos_ids);
                }
                foreach ($tipoeventos_ids as $tid) {
                    if ($tid) {
                        $db->execute("INSERT INTO AMPAR_REL_EQUIPO_TIPOEVENTO (EQUIPOCAPITAL_ID, TIPOEVENTO_ID) VALUES (?, ?)", [$equipocapital_id, $tid]);
                    }
                }
            }
            // Explicitly commit
            $db->commit();
        }
        
        $db->close();
        return $success;
    }

    /**
     * Elimina físicamente un registro de equipo capital
     */
    public function eliminarEquipoCapital($equipocapital_id) {
        $db = new FirebirdConnection();
        $sql = "DELETE FROM AMPAR_EQUIPOCAPITAL WHERE EQUIPOCAPITAL_ID = ?";
        $success = $db->execute($sql, [$equipocapital_id]);
        $db->close();
        return $success;
    }

    /**
     * Obtiene los componentes adicionales de un equipo capital
     */
    public function getComponentes($equipocapital_id) {
        $db = new FirebirdConnection();
        $sql = "SELECT COMPONENTE_ID, EQUIPOCAPITAL_ID, NOMBRE, CANTIDAD, SERIE, REFERENCIA, MARCA, ESTATUS 
                FROM AMPAR_EQUIPOCAPITAL_COMPONENTES 
                WHERE EQUIPOCAPITAL_ID = ? AND ESTATUS = 'A'
                ORDER BY COMPONENTE_ID ASC";
        $result = $db->query($sql, [$equipocapital_id]);
        $db->close();
        return $result;
    }

    /**
     * Guarda o actualiza un componente adicional
     */
    public function guardarComponente($componente_id, $equipocapital_id, $nombre, $cantidad, $serie = '', $referencia = '', $marca = '') {
        $db = new FirebirdConnection();
        if ($componente_id > 0) {
            $sql = "UPDATE AMPAR_EQUIPOCAPITAL_COMPONENTES 
                    SET NOMBRE = ?, CANTIDAD = ?, SERIE = ?, REFERENCIA = ?, MARCA = ? 
                    WHERE COMPONENTE_ID = ? AND EQUIPOCAPITAL_ID = ?";
            $success = $db->execute($sql, [$nombre, $cantidad, $serie, $referencia, $marca, $componente_id, $equipocapital_id]);
        } else {
            $sql = "INSERT INTO AMPAR_EQUIPOCAPITAL_COMPONENTES (COMPONENTE_ID, EQUIPOCAPITAL_ID, NOMBRE, CANTIDAD, SERIE, REFERENCIA, MARCA, ESTATUS) 
                    VALUES (GEN_ID(GEN_AMPAR_EQ_COMPONENTE_ID, 1), ?, ?, ?, ?, ?, ?, 'A')";
            $success = $db->execute($sql, [$equipocapital_id, $nombre, $cantidad, $serie, $referencia, $marca]);
        }
        $db->close();
        return $success;
    }

    /**
     * Elimina físicamente un componente
     */
    public function eliminarComponente($componente_id, $equipocapital_id) {
        $db = new FirebirdConnection();
        $sql = "DELETE FROM AMPAR_EQUIPOCAPITAL_COMPONENTES WHERE COMPONENTE_ID = ? AND EQUIPOCAPITAL_ID = ?";
        $success = $db->execute($sql, [$componente_id, $equipocapital_id]);
        $db->close();
        return $success;
    }
}
?>
