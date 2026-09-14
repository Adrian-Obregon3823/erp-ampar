<?php
class actividades {

    /**
     * Para ALMACENISTA:
     * Verifica si el usuario ya realizó el escaneo diario.
     * Un escaneo es "completado" cuando existen registros en AMPAR_HIS_ESCANEO
     * del usuario actual, con fecha de HOY y hora >= 17:00 (5 PM),
     * y los folios de almacén (AMPAR_HIS_ALMACEN, folios que empiezan con 'AL')
     * están todos cubiertos en los detalles del escaneo.
     *
     * Devuelve array con:
     *   - total_maletas: total de maletas (almacenes con folio que empieza por 'AL')
     *   - escaneos_hoy: cuántos escaneos del usuario hay hoy >= 17:00
     *   - completado: boolean
     *   - ultimo_escaneo: fecha del último escaneo del día
     */
    function getEscaneoStatus($usuarioid, $fechaDate = null) {
        $db = new FirebirdConnection();

        // Total de maletas activas asignadas al usuario (almacenes cuyo folio empieza con 'ML')
        $sqlMaletas = "
            SELECT COUNT(*) TOTAL_MALETAS
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ALMACEN_MS
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'ML%'
            AND A.ALMACEN_STATUS = 17
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
        ";
        $resMaletas = $db->query($sqlMaletas);
        $totalMaletas = (int)($resMaletas[0]['TOTAL_MALETAS'] ?? 0);

        // Escaneos de hoy, después de las 08:00 (hechos por CUALQUIER usuario)
        $sqlEscaneos = "
            SELECT 
                UPPER(TRIM(A.ALMACEN_FOLIO)) AS FOLIO,
                TRIM(A.ALMACEN_NOMBRE) AS NOMBRE,
                MAX(E.ESCANEO_FECHA) AS ULTIMO_ESCANEO
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ALMACEN_MS
            JOIN AMPAR_HIS_ESCANEO E ON UPPER(TRIM(E.ESCANEO_FOLIO)) = UPPER(TRIM(A.ALMACEN_FOLIO))
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'ML%'
            AND A.ALMACEN_STATUS = 17
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND CAST(E.ESCANEO_FECHA AS DATE) = CURRENT_DATE
            AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            GROUP BY UPPER(TRIM(A.ALMACEN_FOLIO)), TRIM(A.ALMACEN_NOMBRE)
        ";
        $resEscaneos = $db->query($sqlEscaneos);
        $escaneosHoy = is_array($resEscaneos) ? count($resEscaneos) : 0;
        
        $ultimoEscaneoTotal = null;
        $validados = [];
        if ($escaneosHoy > 0) {
            foreach ($resEscaneos as $r) {
                $validados[] = [
                    'FOLIO' => $r['FOLIO'],
                    'NOMBRE' => $r['NOMBRE'],
                    'FECHA' => $r['ULTIMO_ESCANEO']
                ];
                if ($ultimoEscaneoTotal === null || $r['ULTIMO_ESCANEO'] > $ultimoEscaneoTotal) {
                    $ultimoEscaneoTotal = $r['ULTIMO_ESCANEO'];
                }
            }
        }

        $sqlPendientes = "
            SELECT 
                UPPER(TRIM(A.ALMACEN_FOLIO)) AS FOLIO,
                TRIM(A.ALMACEN_NOMBRE) AS NOMBRE
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ALMACEN_MS
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'ML%'
            AND A.ALMACEN_STATUS = 17
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND UPPER(TRIM(A.ALMACEN_FOLIO)) NOT IN (
                SELECT UPPER(TRIM(E.ESCANEO_FOLIO))
                FROM AMPAR_HIS_ESCANEO E
                WHERE CAST(E.ESCANEO_FECHA AS DATE) = CURRENT_DATE
                AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            )
        ";
        $resPendientes = $db->query($sqlPendientes);
        $pendientes = [];
        if (is_array($resPendientes)) {
            foreach ($resPendientes as $p) {
                $pendientes[] = [
                    'FOLIO' => $p['FOLIO'],
                    'NOMBRE' => $p['NOMBRE']
                ];
            }
        }

        $db->close();

        return [
            'total_maletas'  => $totalMaletas,
            'escaneos_hoy'   => $escaneosHoy,
            'completado'     => ($totalMaletas == 0 || $escaneosHoy >= $totalMaletas),
            'ultimo_escaneo' => $ultimoEscaneoTotal,
            'validados'      => $validados,
            'pendientes'     => $pendientes
        ];
    }

    /**
     * Para ALMACENISTA:
     * Retorna el estatus del escaneo diario de almacenes.
     * Mismas reglas pero para ALMACEN_FOLIO LIKE 'AL%' y ALMACEN_TIPOALMACEN <> 4.
     */
    function getEscaneoAlmacenesStatus($usuarioid, $fechaDate = null) {
        $db = new FirebirdConnection();

        // Total de almacenes activos asignados al usuario (que empiecen con AL y no sean tipo 4)
        $sqlAlmacenes = "
            SELECT COUNT(*) TOTAL_ALMACENES
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ID
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'AL%'
            AND A.ALMACEN_STATUS = 17
            AND (A.ALMACEN_TIPOALMACEN IS NULL OR A.ALMACEN_TIPOALMACEN <> 4)
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
        ";
        $resAlmacenes = $db->query($sqlAlmacenes);
        $totalAlmacenes = (int)($resAlmacenes[0]['TOTAL_ALMACENES'] ?? 0);

        // Escaneos de hoy, después de las 08:00 (hechos por CUALQUIER usuario)
        $sqlEscaneos = "
            SELECT 
                UPPER(TRIM(A.ALMACEN_FOLIO)) AS FOLIO,
                TRIM(A.ALMACEN_NOMBRE) AS NOMBRE,
                MAX(E.ESCANEO_FECHA) AS ULTIMO_ESCANEO
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ID
            JOIN AMPAR_HIS_ESCANEO E ON UPPER(TRIM(E.ESCANEO_FOLIO)) = UPPER(TRIM(A.ALMACEN_FOLIO))
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'AL%'
            AND A.ALMACEN_STATUS = 17
            AND (A.ALMACEN_TIPOALMACEN IS NULL OR A.ALMACEN_TIPOALMACEN <> 4)
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND CAST(E.ESCANEO_FECHA AS DATE) = CURRENT_DATE
            AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            GROUP BY UPPER(TRIM(A.ALMACEN_FOLIO)), TRIM(A.ALMACEN_NOMBRE)
        ";
        $resEscaneos = $db->query($sqlEscaneos);
        $escaneosHoy = is_array($resEscaneos) ? count($resEscaneos) : 0;
        
        $ultimoEscaneoTotal = null;
        $validados = [];
        if ($escaneosHoy > 0) {
            foreach ($resEscaneos as $r) {
                $validados[] = [
                    'FOLIO' => $r['FOLIO'],
                    'NOMBRE' => $r['NOMBRE'],
                    'FECHA' => $r['ULTIMO_ESCANEO']
                ];
                if ($ultimoEscaneoTotal === null || $r['ULTIMO_ESCANEO'] > $ultimoEscaneoTotal) {
                    $ultimoEscaneoTotal = $r['ULTIMO_ESCANEO'];
                }
            }
        }

        $sqlPendientes = "
            SELECT 
                UPPER(TRIM(A.ALMACEN_FOLIO)) AS FOLIO,
                TRIM(A.ALMACEN_NOMBRE) AS NOMBRE
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ID
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'AL%'
            AND A.ALMACEN_STATUS = 17
            AND (A.ALMACEN_TIPOALMACEN IS NULL OR A.ALMACEN_TIPOALMACEN <> 4)
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND UPPER(TRIM(A.ALMACEN_FOLIO)) NOT IN (
                SELECT UPPER(TRIM(E.ESCANEO_FOLIO))
                FROM AMPAR_HIS_ESCANEO E
                WHERE CAST(E.ESCANEO_FECHA AS DATE) = CURRENT_DATE
                AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            )
        ";
        $resPendientes = $db->query($sqlPendientes);
        $pendientes = [];
        if (is_array($resPendientes)) {
            foreach ($resPendientes as $p) {
                $pendientes[] = [
                    'FOLIO' => $p['FOLIO'],
                    'NOMBRE' => $p['NOMBRE']
                ];
            }
        }

        $db->close();

        return [
            'total_almacenes' => $totalAlmacenes,
            'escaneos_hoy'   => $escaneosHoy,
            'completado'     => ($totalAlmacenes == 0 || $escaneosHoy >= $totalAlmacenes),
            'ultimo_escaneo' => $ultimoEscaneoTotal,
            'validados'      => $validados,
            'pendientes'     => $pendientes
        ];
    }

    /**
     * Para ALMACENISTA:
     * Retorna el número de recepciones pendientes de procesar.
     * Una recepción está pendiente cuando su RECEPCION_STATUS = 1 (sin entradas)
     */
    function getRecepcionesPendientes() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_RECEPCION R
            JOIN AMPAR_OC OC ON OC.OC_ID = R.RECEPCION_OCID
            WHERE R.RECEPCION_STATUS = 1 AND OC.OC_STATUS = 1
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ALMACENISTA:
     * Retorna el número de recepciones de traspasos pendientes (Traspasos enviados a sucursal del usuario).
     */
    function getRecepcionesTraspasosPendientes($usuarioid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(DISTINCT T.TRASPASO_ID) TOTAL
            FROM AMPAR_HIS_TRASPASO T
            JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = T.TRASPASO_AALMACENID
            JOIN AMPAR_CAT_USUARIOSSUCURSALES US ON US.USUARIOSSUCURSALES_SUCURSALID = A.ALMACEN_SUCURSAL_MS
            WHERE T.TRASPASO_STATUS = 9 AND US.USUARIOSSUCURSALES_USUARIOID = " . (int)$usuarioid . "
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ADMINISTRADOR:
     * Retorna el número de Entradas/Salidas pendientes de autorizar (status 3 = En revisión).
     */
    function getEntradasPorAutorizar() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_HIS_ES
            WHERE ES_STATUS = 8
            AND ES_TIPO = 'E'
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ADMINISTRADOR:
     * Retorna el número de traspasos pendientes de autorizar (status 8 = En revisión).
     */
    function getTraspasosPorAutorizar() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_HIS_TRASPASO
            WHERE TRASPASO_STATUS = 8
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ADMINISTRADOR:
     * Retorna el número de disputas de traspasos pendientes de resolución (status 1 = Abierta).
     */
    function getDisputasPorRevisar() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_DISPUTAS
            WHERE DISPUTA_STATUS = 1
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ALMACENISTA:
     * Retorna el número de Entradas pendientes (status 1 = Guardado, 9 = Enviado).
     */
    function getEntradasPendientesAlmacenista() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_HIS_ES
            WHERE (ES_STATUS = 1 OR ES_STATUS = 9)
            AND ES_TIPO = 'E'
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ALMACENISTA:
     * Retorna el número de Salidas pendientes (status 1 = Guardado, 9 = Enviado).
     */
    function getSalidasPendientesAlmacenista() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(*) TOTAL
            FROM AMPAR_HIS_ES
            WHERE (ES_STATUS = 1 OR ES_STATUS = 9)
            AND ES_TIPO = 'S'
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Para ALMACENISTA:
     * Retorna el número de Traspasos pendientes (status 1 = Guardado, 9 = Enviado).
     */
    function getTraspasosPendientesAlmacenista($usuarioid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT COUNT(DISTINCT T.TRASPASO_ID) TOTAL
            FROM AMPAR_HIS_TRASPASO T
            JOIN AMPAR_CAT_USUARIOSALMACENES UA 
              ON (UA.USUARIOSALMACENES_ALMACENID = T.TRASPASO_DEALMACENID OR UA.USUARIOSALMACENES_ALMACENID = T.TRASPASO_AALMACENID)
            WHERE (T.TRASPASO_STATUS = 1 OR T.TRASPASO_STATUS = 9)
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
        ";
        $res = $db->query($sql);
        $db->close();
        return (int)($res[0]['TOTAL'] ?? 0);
    }

    /**
     * Retorna el nombre del almacén padre asignado al usuario
     */
    function getNombreAlmacenUsuario($usuarioid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT FIRST 1 A.ALMACEN_NOMBRE
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ID
            WHERE UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
        ";
        $res = $db->query($sql);
        $db->close();
        return $res[0]['ALMACEN_NOMBRE'] ?? '';
    }

    /**
     * Retorna las asignaciones (códigos de actividad) para un usuario específico.
     */
    function getActividadesAsignadas($usuarioid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT ACTIVIDAD_CODIGO 
            FROM AMPAR_CONF_USUARIOS_ACTIVIDADES
            WHERE USUARIO_ID = " . (int)$usuarioid . "
        ";
        $res = $db->query($sql);
        $db->close();
        $codigos = [];
        if (!empty($res)) {
            foreach ($res as $r) {
                $codigos[] = strtoupper(trim($r['ACTIVIDAD_CODIGO']));
            }
        }
        return $codigos;
    }

    /**
     * Retorna la lista de actividades personalizadas creadas por el admin
     */
    function getActividadesPersonalizadas() {
        $db = new FirebirdConnection();
        $sql = "SELECT * FROM AMPAR_CONF_ACT_CUSTOM ORDER BY ACTIVIDAD_TITULO";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    /**
     * Inserta el menú de Actividades en la BD si no existe.
     * Se usa solo en instalación. No se llama en ejecución normal.
     */
    function insertMenuActividades() {
        $db = new FirebirdConnection();
        // Verificar si ya existe
        $check = $db->query("SELECT MENU_ID FROM AMPAR_CONF_MENU WHERE UPPER(MENU_URL) LIKE '%actividades%'");
        if (!empty($check)) {
            $db->close();
            return false; // Ya existe
        }
        // Insertar ítem de menú
        $db->execute("
            INSERT INTO AMPAR_CONF_MENU (MENU_NOMBRE, MENU_URL, MENU_ICONO, MENU_PADREID, MENU_NIVEL, MENU_ORDEN)
            VALUES ('Actividades', 'gui/actividades.php', 'mdi-checkbox-marked-circle-outline', NULL, 1, 1)
        ");
        $db->close();
        return true;
    }

    /**
     * Inicializa las tablas de configuración dinámica de actividades
     */
    function initConfigTables() {
        $db = new FirebirdConnection();
        // Custom activities table
        try {
            $db->query("SELECT FIRST 1 ACTIVIDAD_ID FROM AMPAR_CONF_ACT_CUSTOM");
        } catch(Exception $e) {
            $sql = "CREATE TABLE AMPAR_CONF_ACT_CUSTOM (
                ACTIVIDAD_ID INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                ACTIVIDAD_TITULO VARCHAR(200),
                ACTIVIDAD_DESCRIPCION VARCHAR(500),
                ACTIVIDAD_ICONO VARCHAR(50),
                ACTIVIDAD_COLOR VARCHAR(20),
                ACTIVIDAD_URL VARCHAR(255),
                ACTIVIDAD_QUERY BLOB SUB_TYPE TEXT
            )";
            $db->execute($sql);
        }

        // Assignments table
        try {
            $db->query("SELECT FIRST 1 USUARIO_ID FROM AMPAR_CONF_USUARIOS_ACTIVIDADES");
        } catch(Exception $e) {
            $sql = "CREATE TABLE AMPAR_CONF_USUARIOS_ACTIVIDADES (
                USUARIO_ID INTEGER NOT NULL,
                ACTIVIDAD_CODIGO VARCHAR(50) NOT NULL,
                PRIMARY KEY (USUARIO_ID, ACTIVIDAD_CODIGO)
            )";
            $db->execute($sql);
            
            // Seed de asignaciones predeterminadas para almacenistas
            $sqlAlm = "
                SELECT DISTINCT U.USUARIO_ID
                FROM AMPAR_CAT_USUARIOS U
                JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID
                JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
                WHERE UPPER(P.PERFIL_NOMBRE) LIKE '%ALMACEN%'
            ";
            $resAlm = $db->query($sqlAlm);
            if (!empty($resAlm)) {
                foreach($resAlm as $alm) {
                    $uid = (int)$alm['USUARIO_ID'];
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'ESCANEO_MALETAS')"); } catch(Exception $ex){}
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'ESCANEO_ALMACENES')"); } catch(Exception $ex){}
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'RECEPCIONES')"); } catch(Exception $ex){}
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'ENTRADAS')"); } catch(Exception $ex){}
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'SALIDAS')"); } catch(Exception $ex){}
                    try { $db->execute("INSERT INTO AMPAR_CONF_USUARIOS_ACTIVIDADES (USUARIO_ID, ACTIVIDAD_CODIGO) VALUES ($uid, 'TRASPASOS')"); } catch(Exception $ex){}
                }
            }
        }
        $db->close();
    }

    /**
     * Poor Man's Cron:
     * Verifica diariamente si los almacenistas cumplieron sus tareas. 
     * Se puede llamar silenciosamente desde el header.php.
     */
    function autoVerificarActividades() {
        $lastRunFile = __DIR__ . '/../cron/last_run.txt';
        $hoy = date('Y-m-d');
        
        if (!file_exists($lastRunFile)) {
            // Si no existe, comenzamos a revisar desde hace 2 días para backfill
            file_put_contents($lastRunFile, date('Y-m-d', strtotime('-2 days')));
        }
        
        $lastRunDate = file_get_contents($lastRunFile);
        $lastRunDate = trim($lastRunDate);
        
        // Si ya revisamos hoy completamente, no hacemos nada
        if ($lastRunDate === $hoy) {
            return;
        }

        // Determinar qué fechas revisar (desde lastRunDate + 1 día hasta hoy)
        $fechasARevisar = [];
        $start = strtotime($lastRunDate . ' +1 day');
        $end = strtotime($hoy);
        
        for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
            $fechaLoop = date('Y-m-d', $i);
            
            // Si es la fecha de hoy, solo revisar si ya pasaron las 19 hrs (7 PM)
            if ($fechaLoop === $hoy) {
                if (date('H') < 19) {
                    continue; // Todavía no es hora de revisar el día actual
                }
            }
            
            $fechasARevisar[] = $fechaLoop;
        }

        if (empty($fechasARevisar)) {
            return; // Nada que evaluar en este momento
        }

        $db = new FirebirdConnection();
        
        // 1. Asegurar tipo de ticket 'Actividad'
        $sqlCheckTipo = "SELECT TICKETTIPO_ID FROM AMPAR_TICKETTIPO WHERE UPPER(TICKETTIPO_NOMBRE) = 'ACTIVIDAD'";
        $resTipo = $db->query($sqlCheckTipo);
        $ticketTipoId = 0;
        if (empty($resTipo)) {
            $db->execute("INSERT INTO AMPAR_TICKETTIPO (TICKETTIPO_NOMBRE) VALUES ('Actividad')");
            $resTipo2 = $db->query($sqlCheckTipo);
            if (!empty($resTipo2)) {
                $ticketTipoId = $resTipo2[0]['TICKETTIPO_ID'];
            }
        } else {
            $ticketTipoId = $resTipo[0]['TICKETTIPO_ID'];
        }

        require_once(__DIR__ . "/notificaciones.php");
        require_once(__DIR__ . "/whatsapp.php");

        // 2. Evaluar cada fecha pendiente
        $ultimoEvaluadoConExito = $lastRunDate;
        
        foreach ($fechasARevisar as $fechaEv) {
            
            $diaSemana = date('N', strtotime($fechaEv));
            if ($diaSemana == 6 || $diaSemana == 7) {
                // Es fin de semana, no evaluamos pero avanzamos la fecha
                $ultimoEvaluadoConExito = $fechaEv;
                continue;
            }

            // --- MALETAS ---
            $sqlMaletasGlob = "SELECT ALMACEN_ALMACEN_MS AS ALMACEN_ID, TRIM(ALMACEN_FOLIO) AS FOLIO, TRIM(ALMACEN_NOMBRE) AS NOMBRE FROM AMPAR_HIS_ALMACEN WHERE UPPER(TRIM(ALMACEN_FOLIO)) LIKE 'ML%' AND ALMACEN_STATUS = 17";
            $resMaletasGlob = $db->query($sqlMaletasGlob);
            
            if (!empty($resMaletasGlob)) {
                foreach ($resMaletasGlob as $maleta) {
                    $folioStr = strtoupper($maleta['FOLIO']);
                    $nombreStr = $maleta['NOMBRE'];
                    $almacenId = (int)$maleta['ALMACEN_ID'];
                    
                    // Verificar si esta maleta específica fue escaneada este día después de las 08:00
                    $sqlEscaneo = "SELECT 1 FROM AMPAR_HIS_ESCANEO WHERE UPPER(TRIM(ESCANEO_FOLIO)) = '" . $folioStr . "' AND CAST(ESCANEO_FECHA AS DATE) = '" . $fechaEv . "' AND EXTRACT(HOUR FROM ESCANEO_FECHA) >= 8";
                    $resEscaneo = $db->query($sqlEscaneo);
                    
                    if (empty($resEscaneo)) {
                        // NO fue escaneada, crear ticket de incidencia individual
                        $concepto = "Incidencia en escaneo de maleta del $fechaEv. La maleta $folioStr ($nombreStr) no fue escaneada.";
                        $folioTicket = "ACT-ML-" . $almacenId . "-" . date('Ymd', strtotime($fechaEv));
                        
                        $sqlTicket = "INSERT INTO AMPAR_TICKETS (TICKET_STATUS, TICKET_TIPO, TICKET_FOLIO, TICKET_CONCEPTO, TICKET_FECHA) VALUES (1, ?, ?, ?, ?)";
                        try {
                            $fechaFormatDB = date('Y-m-d H:i:s', strtotime($fechaEv . " 19:00:00"));
                            $db->execute($sqlTicket, [$ticketTipoId, $folioTicket, $concepto, $fechaFormatDB]);
                        } catch (Exception $e) {}
                        
                        // Notificar a usuarios asignados A ESTA MALETA específica que tengan la actividad
                        $sqlNotificar = "
                            SELECT U.USUARIO_ID
                            FROM AMPAR_CAT_USUARIOS U
                            JOIN AMPAR_CONF_USUARIOS_ACTIVIDADES CA ON CA.USUARIO_ID = U.USUARIO_ID
                            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                            WHERE U.USUARIO_ACTIVO = 1 
                            AND CA.ACTIVIDAD_CODIGO = 'ESCANEO_MALETAS'
                            AND UA.USUARIOSALMACENES_ALMACENID = $almacenId
                        ";
                        $encargados = $db->query($sqlNotificar);
                        
                        if (!empty($encargados)) {
                            foreach ($encargados as $encargado) {
                                $uid = (int)$encargado['USUARIO_ID'];
                                // Notificación Virtual
                                notificaciones::crear($uid, 0, 'SISTEMA', "Incidencia Escaneo ($folioStr)", "No se completó el escaneo de la maleta $folioStr el día $fechaEv.");
                                
                                // Notificación WhatsApp
                                $telefono = whatsapp::getTelefonoUsuario($uid);
                                if ($telefono) {
                                    whatsapp::enviar($telefono, "¡Hola! 🚨\nSe ha generado una incidencia porque no se completó el escaneo diario de la maleta *$folioStr* ($nombreStr) correspondiente al día $fechaEv.");
                                }
                            }
                        }
                    }
                }
            }

            // --- ALMACENES ---
            $sqlAlmacenesGlob = "SELECT ALMACEN_ID, TRIM(ALMACEN_FOLIO) AS FOLIO, TRIM(ALMACEN_NOMBRE) AS NOMBRE FROM AMPAR_HIS_ALMACEN WHERE UPPER(TRIM(ALMACEN_FOLIO)) LIKE 'AL%' AND ALMACEN_STATUS = 17 AND (ALMACEN_TIPOALMACEN IS NULL OR ALMACEN_TIPOALMACEN <> 4)";
            $resAlmacenesGlob = $db->query($sqlAlmacenesGlob);
            
            if (!empty($resAlmacenesGlob)) {
                foreach ($resAlmacenesGlob as $almacen) {
                    $folioStr = strtoupper($almacen['FOLIO']);
                    $nombreStr = $almacen['NOMBRE'];
                    $almacenId = (int)$almacen['ALMACEN_ID'];
                    
                    // Verificar si este almacén específico fue escaneado este día después de las 08:00
                    $sqlEscaneo = "SELECT 1 FROM AMPAR_HIS_ESCANEO WHERE UPPER(TRIM(ESCANEO_FOLIO)) = '" . $folioStr . "' AND CAST(ESCANEO_FECHA AS DATE) = '" . $fechaEv . "' AND EXTRACT(HOUR FROM ESCANEO_FECHA) >= 8";
                    $resEscaneo = $db->query($sqlEscaneo);
                    
                    if (empty($resEscaneo)) {
                        // NO fue escaneado, crear ticket
                        $concepto = "Incidencia en escaneo de almacén del $fechaEv. El almacén $folioStr ($nombreStr) no fue escaneado.";
                        $folioTicket = "ACT-AL-" . $almacenId . "-" . date('Ymd', strtotime($fechaEv));
                        
                        $sqlTicket = "INSERT INTO AMPAR_TICKETS (TICKET_STATUS, TICKET_TIPO, TICKET_FOLIO, TICKET_CONCEPTO, TICKET_FECHA) VALUES (1, ?, ?, ?, ?)";
                        try {
                            $fechaFormatDB = date('Y-m-d H:i:s', strtotime($fechaEv . " 19:00:00"));
                            $db->execute($sqlTicket, [$ticketTipoId, $folioTicket, $concepto, $fechaFormatDB]);
                        } catch (Exception $e) {}
                        
                        // Notificar a usuarios asignados A ESTE ALMACÉN específico que tengan la actividad
                        $sqlNotificar = "
                            SELECT U.USUARIO_ID
                            FROM AMPAR_CAT_USUARIOS U
                            JOIN AMPAR_CONF_USUARIOS_ACTIVIDADES CA ON CA.USUARIO_ID = U.USUARIO_ID
                            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                            WHERE U.USUARIO_ACTIVO = 1 
                            AND CA.ACTIVIDAD_CODIGO = 'ESCANEO_ALMACENES'
                            AND UA.USUARIOSALMACENES_ALMACENID = $almacenId
                        ";
                        $encargados = $db->query($sqlNotificar);
                        
                        if (!empty($encargados)) {
                            foreach ($encargados as $encargado) {
                                $uid = (int)$encargado['USUARIO_ID'];
                                // Notificación Virtual
                                notificaciones::crear($uid, 0, 'SISTEMA', "Incidencia Escaneo ($folioStr)", "No se completó el escaneo del almacén $folioStr el día $fechaEv.");
                                
                                // Notificación WhatsApp
                                $telefono = whatsapp::getTelefonoUsuario($uid);
                                if ($telefono) {
                                    whatsapp::enviar($telefono, "¡Hola! 🚨\nSe ha generado una incidencia porque no se completó el escaneo diario del almacén *$folioStr* ($nombreStr) correspondiente al día $fechaEv.");
                                }
                            }
                        }
                    }
                }
            }
            
            $ultimoEvaluadoConExito = $fechaEv;
        }

        $db->close();
        
        // Guardamos la última fecha evaluada correctamente
        file_put_contents($lastRunFile, $ultimoEvaluadoConExito);
    }

    /**
     * Envia recordatorios diarios por WhatsApp a las 3:00 PM
     */
    function autoEnviarRecordatorios() {
        $hoy = date('Y-m-d');
        
        // No enviar en fines de semana
        $diaSemana = date('N');
        if ($diaSemana == 6 || $diaSemana == 7) {
            return;
        }

        if (date('H') < 15) {
            return; // Aún no es hora de enviar el recordatorio
        }

        $lastRunFile = __DIR__ . "/last_reminder_run.txt";
        $lastRunDate = file_exists($lastRunFile) ? trim(file_get_contents($lastRunFile)) : '';

        if ($lastRunDate === $hoy) {
            return; // Ya se enviaron los recordatorios hoy
        }

        $db = new FirebirdConnection();
        require_once(__DIR__ . "/whatsapp.php");
        require_once(__DIR__ . "/notificaciones.php");

        // --- RECORDATORIOS MALETAS ---
        $sqlMaletasGlob = "SELECT ALMACEN_ALMACEN_MS AS ALMACEN_ID, TRIM(ALMACEN_FOLIO) AS FOLIO, TRIM(ALMACEN_NOMBRE) AS NOMBRE FROM AMPAR_HIS_ALMACEN WHERE UPPER(TRIM(ALMACEN_FOLIO)) LIKE 'ML%' AND ALMACEN_STATUS = 17";
        $resMaletasGlob = $db->query($sqlMaletasGlob);
        
        if (!empty($resMaletasGlob)) {
            foreach ($resMaletasGlob as $maleta) {
                $folioStr = strtoupper($maleta['FOLIO']);
                $nombreStr = $maleta['NOMBRE'];
                $almacenId = (int)$maleta['ALMACEN_ID'];
                
                // Verificar si fue escaneada
                $sqlEscaneo = "SELECT 1 FROM AMPAR_HIS_ESCANEO WHERE UPPER(TRIM(ESCANEO_FOLIO)) = '" . $folioStr . "' AND CAST(ESCANEO_FECHA AS DATE) = '" . $hoy . "' AND EXTRACT(HOUR FROM ESCANEO_FECHA) >= 8";
                $resEscaneo = $db->query($sqlEscaneo);
                
                if (empty($resEscaneo)) {
                    // Obtener encargados
                    $sqlNotificar = "
                        SELECT U.USUARIO_ID
                        FROM AMPAR_CAT_USUARIOS U
                        JOIN AMPAR_CONF_USUARIOS_ACTIVIDADES CA ON CA.USUARIO_ID = U.USUARIO_ID
                        JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                        WHERE U.USUARIO_ACTIVO = 1 
                        AND CA.ACTIVIDAD_CODIGO = 'ESCANEO_MALETAS'
                        AND UA.USUARIOSALMACENES_ALMACENID = $almacenId
                    ";
                    $encargados = $db->query($sqlNotificar);
                    if (!empty($encargados)) {
                        foreach ($encargados as $encargado) {
                            $uid = (int)$encargado['USUARIO_ID'];
                            $telefono = whatsapp::getTelefonoUsuario($uid);
                            if ($telefono) {
                                whatsapp::enviar($telefono, "¡Hola! 🔔\nEste es un recordatorio de que tienes hasta las 7:00 PM para completar el escaneo diario de la maleta *$folioStr* ($nombreStr).");
                            }
                        }
                    }
                }
            }
        }

        // --- RECORDATORIOS ALMACENES ---
        $sqlAlmacenesGlob = "SELECT ALMACEN_ID, TRIM(ALMACEN_FOLIO) AS FOLIO, TRIM(ALMACEN_NOMBRE) AS NOMBRE FROM AMPAR_HIS_ALMACEN WHERE UPPER(TRIM(ALMACEN_FOLIO)) LIKE 'AL%' AND ALMACEN_STATUS = 17 AND (ALMACEN_TIPOALMACEN IS NULL OR ALMACEN_TIPOALMACEN <> 4)";
        $resAlmacenesGlob = $db->query($sqlAlmacenesGlob);
        
        if (!empty($resAlmacenesGlob)) {
            foreach ($resAlmacenesGlob as $almacen) {
                $folioStr = strtoupper($almacen['FOLIO']);
                $nombreStr = $almacen['NOMBRE'];
                $almacenId = (int)$almacen['ALMACEN_ID'];
                
                // Verificar si fue escaneado
                $sqlEscaneo = "SELECT 1 FROM AMPAR_HIS_ESCANEO WHERE UPPER(TRIM(ESCANEO_FOLIO)) = '" . $folioStr . "' AND CAST(ESCANEO_FECHA AS DATE) = '" . $hoy . "' AND EXTRACT(HOUR FROM ESCANEO_FECHA) >= 8";
                $resEscaneo = $db->query($sqlEscaneo);
                
                if (empty($resEscaneo)) {
                    $sqlNotificar = "
                        SELECT U.USUARIO_ID
                        FROM AMPAR_CAT_USUARIOS U
                        JOIN AMPAR_CONF_USUARIOS_ACTIVIDADES CA ON CA.USUARIO_ID = U.USUARIO_ID
                        JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                        WHERE U.USUARIO_ACTIVO = 1 
                        AND CA.ACTIVIDAD_CODIGO = 'ESCANEO_ALMACENES'
                        AND UA.USUARIOSALMACENES_ALMACENID = $almacenId
                    ";
                    $encargados = $db->query($sqlNotificar);
                    if (!empty($encargados)) {
                        foreach ($encargados as $encargado) {
                            $uid = (int)$encargado['USUARIO_ID'];
                            $telefono = whatsapp::getTelefonoUsuario($uid);
                            if ($telefono) {
                                whatsapp::enviar($telefono, "¡Hola! 🔔\nEste es un recordatorio de que tienes hasta las 7:00 PM para completar el escaneo diario del almacén *$folioStr* ($nombreStr).");
                            }
                        }
                    }
                }
            }
        }

        $db->close();
        file_put_contents($lastRunFile, $hoy);
    }

    /**
     * Devuelve la lista de Folios/Nombres de las maletas asignadas al usuario que NO han sido escaneadas hoy después de las 16:00
     */
    function getMaletasFaltantes($usuarioid, $fechaDate = null) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT TRIM(A.ALMACEN_FOLIO) AS FOLIO, TRIM(A.ALMACEN_NOMBRE) AS NOMBRE
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ALMACEN_MS
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'ML%'
            AND A.ALMACEN_STATUS = 17
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND NOT EXISTS (
                SELECT 1 FROM AMPAR_HIS_ESCANEO E 
                WHERE E.ESCANEO_RESPONSABLEID = " . (int)$usuarioid . " 
                AND UPPER(TRIM(E.ESCANEO_FOLIO)) = UPPER(TRIM(A.ALMACEN_FOLIO))
                AND CAST(E.ESCANEO_FECHA AS DATE) = " . ($fechaDate ? "'" . $fechaDate . "'" : 'CURRENT_DATE') . "
                AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            )
        ";
        $res = $db->query($sql);
        $db->close();
        return $res ? $res : [];
    }

    /**
     * Devuelve la lista de Folios/Nombres de los almacenes asignados al usuario que NO han sido escaneados hoy después de las 16:00
     */
    function getAlmacenesFaltantes($usuarioid, $fechaDate = null) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT TRIM(A.ALMACEN_FOLIO) AS FOLIO, TRIM(A.ALMACEN_NOMBRE) AS NOMBRE
            FROM AMPAR_HIS_ALMACEN A
            JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_ALMACENID = A.ALMACEN_ID
            WHERE UPPER(TRIM(A.ALMACEN_FOLIO)) LIKE 'AL%'
            AND A.ALMACEN_STATUS = 17
            AND (A.ALMACEN_TIPOALMACEN IS NULL OR A.ALMACEN_TIPOALMACEN <> 4)
            AND UA.USUARIOSALMACENES_USUARIOID = " . (int)$usuarioid . "
            AND NOT EXISTS (
                SELECT 1 FROM AMPAR_HIS_ESCANEO E 
                WHERE E.ESCANEO_RESPONSABLEID = " . (int)$usuarioid . " 
                AND UPPER(TRIM(E.ESCANEO_FOLIO)) = UPPER(TRIM(A.ALMACEN_FOLIO))
                AND CAST(E.ESCANEO_FECHA AS DATE) = " . ($fechaDate ? "'" . $fechaDate . "'" : 'CURRENT_DATE') . "
                AND EXTRACT(HOUR FROM E.ESCANEO_FECHA) >= 8
            )
        ";
        $res = $db->query($sql);
        $db->close();
        return $res ? $res : [];
    }
}
?>
