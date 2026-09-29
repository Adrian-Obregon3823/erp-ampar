<?php

class eventos
{

    function geteventos($usuarioid, $responsableid = "", $statusgeneral = "")
    {
        $db = new FirebirdConnection();
        $sql = "
        SELECT 
            E.*, 
            TE.*, 
            TES.*, 
            TEG.*, 
            UES.USUARIO_NOMBRE ESPECIALISTA_NOMBRE, 
            UC.USUARIO_NOMBRE CHOFER_NOMBRE, 
            EG.*,
            EE.STATUS_NOMBRE STATUSESPECIALISTA, 
            EE.STATUS_COLOR STATUSCOLORESPECIALISTA,
            EC.STATUS_NOMBRE STATUSCHOFER, 
            EC.STATUS_COLOR STATUSCOLORCHOFER,
            (
                SELECT FIRST 1 REMISION_ID 
                FROM AMPAR_HIS_REMISIONES 
                WHERE REMISION_EVENTOID = E.EVENTO_ID 
                  AND REMISION_STATUS = 3
            ) REMISION_ID,
            (
                SELECT FIRST 1 EVIDENCIA_ID 
                FROM AMPAR_ENTREGAEVENTO 
                WHERE EVENTO_ID = E.EVENTO_ID
            ) EVIDENCIA_REC_ID
        FROM AMPAR_HIS_EVENTOS E
        LEFT JOIN AMPAR_CAT_TIPOEVENTO TE 
            ON TIPOEVENTO_ID = EVENTO_TIPOEVENTO
        LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TES 
            ON TES.TIPOEVENTOSUBGRUPO_ID = TIPOEVENTO_SUBGRUPOID
        LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO TEG 
            ON TEG.TIPOEVENTOGRUPO_ID = TIPOEVENTOSUBGRUPO_GRUPOID
        LEFT JOIN AMPAR_CAT_USUARIOS UES 
            ON UES.USUARIO_ID = EVENTO_ESPECIALISTAID
        LEFT JOIN AMPAR_CONF_STATUS EE 
            ON EE.STATUS_ID = EVENTO_ESPECIALISTAIDSTATUS
        LEFT JOIN AMPAR_CAT_USUARIOS UC 
            ON UC.USUARIO_ID = EVENTO_CHOFERID
        LEFT JOIN AMPAR_CONF_STATUS EC 
            ON EC.STATUS_ID = EVENTO_CHOFERIDSTATUS
        LEFT JOIN AMPAR_CONF_STATUS EG 
            ON EG.STATUS_ID = EVENTO_STATUSGENERAL
    ";

        // 🔹 Verificar si es Administrador (TIPOID = 1)
        $isAdmin = false;
        $sqlAdmin = "SELECT FIRST 1 1 FROM AMPAR_CAT_USUARIOSTIPOPERMISOS WHERE USUARIOSTIPOPERMISOS_USUARIOID = " . $usuarioid . " AND USUARIOSTIPOPERMISOS_TIPOID = 1";
        $resAdmin = $db->query($sqlAdmin);
        if(!empty($resAdmin)) {
            $isAdmin = true;
        }

        if ($isAdmin) {
            $sql .= " WHERE 1=1 ";
        } else {
            $sql .= " WHERE (
                EVENTO_SUCURSALID IN (
                    SELECT USUARIOSSUCURSALES_SUCURSALID
                    FROM AMPAR_CAT_USUARIOSSUCURSALES
                    WHERE USUARIOSSUCURSALES_USUARIOID = " . $usuarioid . "
                )
                OR EVENTO_ESPECIALISTAID = " . $usuarioid . "
                OR EVENTO_CHOFERID = " . $usuarioid . "
            ) ";
        }

        if ($responsableid <> "") {
            $sql .= " AND (EVENTO_ESPECIALISTAID = " . $responsableid . " OR EVENTO_CHOFERID = " . $responsableid . ")";
        }

        if ($statusgeneral !== "" && is_numeric($statusgeneral)) {
            if ((int)$statusgeneral == 15) {
                $sql .= " AND EVENTO_STATUSGENERAL = 15";
            } else {
                $sql .= " AND EVENTO_STATUSGENERAL = " . (int)$statusgeneral;
            }
        }

        $sql .= " ORDER BY EVENTO_ID DESC";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function geteventosparainvitacion($usuarioid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                E.*,
                EG.*,
                TE.*,
                TES.*,
                TEG.*,
                UES.USUARIO_NOMBRE AS ESPECIALISTA_NOMBRE,
                UC.USUARIO_NOMBRE AS CHOFER_NOMBRE,
                (
                    SELECT FIRST 1 EVIDENCIA_ID 
                    FROM AMPAR_ENTREGAEVENTO 
                    WHERE EVENTO_ID = E.EVENTO_ID
                ) AS EVIDENCIA_REC_ID,
                -- FLAG que indica si puede solicitar como especialista
                CASE 
                    WHEN E.EVENTO_ESPECIALISTAID IS NULL
                        AND UP_ESPECIALISTA.USUARIOSTIPOPERMISOS_USUARIOID IS NOT NULL
                    THEN 1 ELSE 0 
                END AS PUEDE_SOLICITAR_ESPECIALISTA,
                -- FLAG que indica si puede solicitar como chofer
                CASE
                    WHEN E.EVENTO_CHOFERID IS NULL
                        AND UP_CHOFER.USUARIOSTIPOPERMISOS_USUARIOID IS NOT NULL
                    THEN 1 ELSE 0
                END AS PUEDE_SOLICITAR_CHOFER
            FROM AMPAR_HIS_EVENTOS E
            LEFT JOIN AMPAR_CONF_STATUS EG ON EG.STATUS_ID = EVENTO_STATUSGENERAL
            LEFT JOIN AMPAR_CAT_TIPOEVENTO TE ON TE.TIPOEVENTO_ID = E.EVENTO_TIPOEVENTO
            LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TES ON TES.TIPOEVENTOSUBGRUPO_ID = TE.TIPOEVENTO_SUBGRUPOID
            LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO TEG ON TEG.TIPOEVENTOGRUPO_ID = TES.TIPOEVENTOSUBGRUPO_GRUPOID
            LEFT JOIN AMPAR_CAT_USUARIOS UES ON UES.USUARIO_ID = E.EVENTO_ESPECIALISTAID
            LEFT JOIN AMPAR_CAT_USUARIOS UC ON UC.USUARIO_ID = E.EVENTO_CHOFERID
            LEFT JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS UP_ESPECIALISTA ON UP_ESPECIALISTA.USUARIOSTIPOPERMISOS_USUARIOID = " . $usuarioid . " AND UP_ESPECIALISTA.USUARIOSTIPOPERMISOS_TIPOID = 2
            LEFT JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS UP_CHOFER ON UP_CHOFER.USUARIOSTIPOPERMISOS_USUARIOID = " . $usuarioid . " AND UP_CHOFER.USUARIOSTIPOPERMISOS_TIPOID = 3
            LEFT JOIN AMPAR_CAT_USUARIOSSUBGRUPOS SU ON SU.USUARIOSSUBGRUPOS_USUARIOID = " . $usuarioid . " AND SU.USUARIOSSUBGRUPOS_SUBGRUPOID = TES.TIPOEVENTOSUBGRUPO_ID
            WHERE 
                E.EVENTO_SUCURSALID IN (
                    SELECT USUARIOSSUCURSALES_SUCURSALID
                    FROM AMPAR_CAT_USUARIOSSUCURSALES
                    WHERE USUARIOSSUCURSALES_USUARIOID = " . $usuarioid . "
                )
            AND (
                (E.EVENTO_ESPECIALISTAID IS NULL 
                    AND UP_ESPECIALISTA.USUARIOSTIPOPERMISOS_USUARIOID IS NOT NULL)
                OR
                (E.EVENTO_CHOFERID IS NULL 
                    AND UP_CHOFER.USUARIOSTIPOPERMISOS_USUARIOID IS NOT NULL)
            )
        ";
        $sql .= " ORDER BY E.EVENTO_FECHAI";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfocalendario($usuarioid = "")
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                EVENTO_ID as ID,
                EVENTO_FOLIO || ' ' || TIPOEVENTO_NOMBRE AS TITLE,
                EVENTO_FECHAI,
                EVENTO_FECHAF,
                CASE 
                    -- Rojo: si esta en status cancelado
                    WHEN EVENTO_STATUSGENERAL = 5 THEN 'D12604'
                    -- Morado oscuro: si esta en status iniciado
                    WHEN EVENTO_STATUSGENERAL = 15 THEN '5B07B0'
                    -- Celeste: si esta en status en remision
                    WHEN EVENTO_STATUSGENERAL = 16 THEN '62ADCC'
                    -- Naranja: si esta en status en recepcion
                    WHEN EVENTO_STATUSGENERAL = 29 THEN 'F0AD4E'
                    -- Violeta: si esta en status en revisión (8)
                    WHEN EVENTO_STATUSGENERAL = 8 THEN '8B5CF6'
                    -- Azul Marino: si esta en status Finalizado
                    WHEN EVENTO_STATUSGENERAL = 3 THEN '122694'
                    -- Verde: ambos asignados y confirmados
                    WHEN EVENTO_ESPECIALISTAID IS NOT NULL 
                        AND EVENTO_CHOFERID IS NOT NULL
                        AND EVENTO_ESPECIALISTAIDSTATUS = 19 
                        AND EVENTO_CHOFERIDSTATUS = 19 THEN '19A834'
                    -- Amarillo: ambos asignados pero alguno no confirmado
                    WHEN EVENTO_ESPECIALISTAID IS NOT NULL 
                        AND EVENTO_CHOFERID IS NOT NULL
                        AND (EVENTO_ESPECIALISTAIDSTATUS <> 19 OR EVENTO_CHOFERIDSTATUS <> 19) THEN 'FCCF08'
                    -- Naranja: ninguno asignado o solo uno asignado
                    WHEN EVENTO_ESPECIALISTAID IS NULL OR EVENTO_CHOFERID IS NULL THEN 'F57D27'
                    -- Color por defecto
                    ELSE 'FFFFFF'
                END AS COLOR
            FROM AMPAR_HIS_EVENTOS
            LEFT JOIN AMPAR_CAT_TIPOEVENTO ON TIPOEVENTO_ID = EVENTO_TIPOEVENTO
        ";
        if ($usuarioid <> "") {
            $sql .= "
                WHERE EVENTO_ESPECIALISTAID = " . $usuarioid . " OR EVENTO_CHOFERID = " . $usuarioid . "
            ";
        }
        $sql .= "
            ORDER BY EVENTO_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();

        $eventos = [];

        foreach ($result as $row) {
            // Agregar evento al array
            $eventos[] = [
                'id' => $row['ID'],
                'title' => trim($row['TITLE']),  // Ya convertido en texto
                'start' => $row['EVENTO_FECHAI'],
                'end' => $row['EVENTO_FECHAF'],
                'color' => '#' . $row['COLOR'],
                'textColor' => 'black',          // letra negra
                'allDay' => false
            ];
        }
        return $eventos;
    }

    function getpermisosespecialista($sucursalid, $subgrupo, $usuarioid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT FIRST 1 1
            FROM AMPAR_CAT_USUARIOS U
            JOIN AMPAR_CAT_USUARIOSSUCURSALES US ON US.USUARIOSSUCURSALES_USUARIOID = U.USUARIO_ID
            JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS TP ON TP.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            WHERE U.USUARIO_ID = " . $usuarioid . "
            AND US.USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "
            AND TP.USUARIOSTIPOPERMISOS_TIPOID = 2
        ";
        $result = $db->query($sql);
        $db->close();
        return ($result == 0) ? false : true;
    }

    function getpermisoschofer($sucursalid, $usuarioid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT FIRST 1 1
            FROM AMPAR_CAT_USUARIOS U
            JOIN AMPAR_CAT_USUARIOSSUCURSALES US ON US.USUARIOSSUCURSALES_USUARIOID = U.USUARIO_ID
            JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS TP ON TP.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            WHERE U.USUARIO_ID = " . $usuarioid . "
            AND US.USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "
            AND TP.USUARIOSTIPOPERMISOS_TIPOID = 3
        ";
        $result = $db->query($sql);
        $db->close();
        return ($result == 0) ? false : true;
    }

    function geteventobyid($eventoid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
            E.EVENTO_CLIENTEID,E.EVENTO_HOSPITALID,E.EVENTO_DOCTORREFERIDORID,E.EVENTO_DOCTORINTERVENCIONISTAID,E.EVENTO_TIPOEVENTO,E.EVENTO_ESPECIALISTAID,E.EVENTO_CHOFERID,E.EVENTO_FECHAI,E.EVENTO_FECHAF,E.EVENTO_FECHACREACION,E.EVENTO_ID,E.EVENTO_ESPECIALISTAIDSTATUS,E.EVENTO_CHOFERIDSTATUS,E.EVENTO_STATUSGENERAL,E.EVENTO_SUCURSALID, E.EVENTO_ALMACENID, ALMEV.ALMACEN_NOMBRE AS EVENTO_ALMACEN_NOMBRE, E.EVENTO_FOLIO,E.EVENTO_FOLIO,CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO, S.STATUS_NOMBRE STATUS_NOMBREGRAL, S.STATUS_COLOR STATUS_COLORGRAL, E.EVENTO_NOMBREPARTICULAR, E.EVENTO_PRESUPUESTOPARTICULAR, E.EVENTO_CLIENTETIPOID,
            H.*, C.NOMBRE, EM.*, M.ALMACEN_TIPOMALETAID, M.ALMACEN_TIPOALMACEN, M.ALMACEN_SUCURSAL_MS,
            COALESCE(M.ALMACEN_ID, EM.EVENTOMALETA_MALETAID) AS ALMACEN_ID,
            COALESCE(M.ALMACEN_NOMBRE, ART_EC.NOMBRE || ' (EQUIPO CAPITAL)', ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS ALMACEN_NOMBRE,
            COALESCE(M.ALMACEN_FOLIO, EC_EQ.FOLIO, ST_EQ.STOCK_FOLIO) AS ALMACEN_FOLIO,
            TE.*, UES.USUARIO_NOMBRE ESPECIALISTA_NOMBRE, UCH.USUARIO_NOMBRE CHOFER_NOMBRE, DATEDIFF(HOUR FROM EVENTO_FECHAI TO EVENTO_FECHAF) AS DURACION_HORAS,
            EE.STATUS_NOMBRE STATUSESPECIALISTA, EC.STATUS_NOMBRE STATUSCHOFER, EE.STATUS_COLOR STATUSCOLORESPECIALISTA, EC.STATUS_COLOR STATUSCOLORCHOFER,
            TIPOEVENTOGRUPO_ID, TIPOEVENTOGRUPO_NOMBRE, TIPOEVENTOSUBGRUPO_ID, TIPOEVENTOSUBGRUPO_NOMBRE, SU.SUCURSAL_ID, SU.NOMBRE SUCURSAL_NOMBRE, COALESCE((
                SELECT AVG(
                    CASE 
                        WHEN tmd.TIPOMALETADET_CANTIDADSUGERIDA > 0 THEN 
                            100.0 * 
                            (
                                CASE 
                                    WHEN COALESCE(c.CANTIDAD, 0) > tmd.TIPOMALETADET_CANTIDADSUGERIDA 
                                    THEN tmd.TIPOMALETADET_CANTIDADSUGERIDA
                                    ELSE COALESCE(c.CANTIDAD, 0)
                                END
                            ) / tmd.TIPOMALETADET_CANTIDADSUGERIDA
                        ELSE 0
                    END
                )
                FROM AMPAR_HIS_TIPOMALETADET tmd
                LEFT JOIN (
                    SELECT 
                        STOCK_ARTICULOID, 
                        STOCK_ALMACENIDACTUAL,
                        COUNT(*) AS CANTIDAD
                    FROM AMPAR_HIS_STOCK S
                    WHERE  S.STOCK_STOCKSTATUSID = 1 OR S.STOCK_STOCKSTATUSID = 2
                    GROUP BY STOCK_ARTICULOID, STOCK_ALMACENIDACTUAL
                ) c ON c.STOCK_ARTICULOID = tmd.TIPOMALETADET_ARTICULOID AND c.STOCK_ALMACENIDACTUAL = M.ALMACEN_ID
                WHERE tmd.TIPOMALETADET_TIPOMALETAID = M.ALMACEN_TIPOMALETAID
            ), 100.00) AS PORCENTAJE,
            MER.MEDICO_NOMBRE DOCTORREFERIDOR_NOMBRE,
            MEI.MEDICO_NOMBRE DOCTORINTERVENCIONISTA_NOMBRE
            FROM AMPAR_HIS_EVENTOS E
            LEFT JOIN AMPAR_HIS_ALMACEN ALMEV ON ALMEV.ALMACEN_ID = E.EVENTO_ALMACENID
            LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = EVENTO_STATUSGENERAL
            LEFT JOIN AMPAR_CAT_HOSPITALES H ON HOSPITAL_ID = EVENTO_HOSPITALID
            LEFT JOIN CLIENTES C ON CLIENTE_ID = EVENTO_CLIENTEID
            LEFT JOIN AMPAR_HIS_EVENTOSMALETAS EM ON EVENTOMALETA_EVENTOID = EVENTO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN M ON M.ALMACEN_ID = EVENTOMALETA_MALETAID
            LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID < 0
            LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
            LEFT JOIN AMPAR_EQUIPOCAPITAL EC_EQ ON EC_EQ.EQUIPOCAPITAL_ID = -EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID < 0
            LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC_EQ.ARTICULO_ID
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = EVENTO_SUCURSALID
            LEFT JOIN AMPAR_CAT_TIPOEVENTO TE  ON TIPOEVENTO_ID = EVENTO_TIPOEVENTO
            LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TES  ON TIPOEVENTOSUBGRUPO_ID = TIPOEVENTO_SUBGRUPOID
            LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO TEG  ON TEG.TIPOEVENTOGRUPO_ID = TIPOEVENTOSUBGRUPO_GRUPOID
            LEFT JOIN AMPAR_CAT_USUARIOS UES  ON UES.USUARIO_ID = EVENTO_ESPECIALISTAID
            LEFT JOIN AMPAR_CONF_STATUS EE ON EE.STATUS_ID = EVENTO_ESPECIALISTAIDSTATUS
            LEFT JOIN AMPAR_CAT_USUARIOS UCH  ON UCH.USUARIO_ID = EVENTO_CHOFERID
            LEFT JOIN AMPAR_CONF_STATUS EC ON EC.STATUS_ID = EVENTO_CHOFERIDSTATUS
            LEFT JOIN AMPAR_CAT_MEDICOS MER ON MER.MEDICO_ID = EVENTO_DOCTORREFERIDORID
            LEFT JOIN AMPAR_CAT_MEDICOS MEI ON MEI.MEDICO_ID = EVENTO_DOCTORINTERVENCIONISTAID
            WHERE EVENTO_ID = " . $eventoid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getproveedoresbyeventoid($eventoid)
    {
        $db = new FirebirdConnection();
        $sql = "
            select 
            EVENTOPROVEEDOR_ID, PROVEEDOR_ID, CAST(ep.EVENTOPROVEEDOR_OBSERVACIONES AS VARCHAR(1000)) AS EVENTOPROVEEDOR_OBSERVACIONES, p.NOMBRE NOMBREPROVEEDOR
            from 
            AMPAR_HIS_EVENTOPROVEEDOR ep
            LEFT JOIN PROVEEDORES p ON PROVEEDOR_ID = EVENTOPROVEEDOR_PROVEEDORID
            WHERE EVENTOPROVEEDOR_EVENTOID = " . $eventoid . "
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    // 1) Agrega $ignorarEventoId como último parámetro
    function eventoEmpalmadoDetallado($db, $efechai, $efechaf, $eespecialista, $echofer, $emaletaid, $ignorarEventoId = null)
    {
        $mensajes = [];

        // Tolerancia (1 hora)
        $tolerancia = 3600;
        $inicio = date('Y-m-d H:i:s', strtotime($efechai) - $tolerancia);
        $fin    = date('Y-m-d H:i:s', strtotime($efechaf) + $tolerancia);

        // Armamos cláusula para excluir el propio evento si se edita
        $andExcluye = "";
        $paramsExcl = [];
        if (!empty($ignorarEventoId)) {
            $andExcluye = " AND EVENTO_ID <> ? ";
            $paramsExcl[] = $ignorarEventoId;
        }

        // 1) Conflicto por especialista (solo si trae valor)
        if (!empty($eespecialista) && strtoupper($eespecialista) !== 'NULL') {
            $sql_esp = "
                SELECT FIRST 1 EVENTO_ID
                FROM AMPAR_HIS_EVENTOS
                WHERE EVENTO_STATUSGENERAL IN (14,15)
                AND EVENTO_ESPECIALISTAID = ?
                AND (
                        (EVENTO_FECHAI BETWEEN ? AND ?) OR
                        (EVENTO_FECHAF BETWEEN ? AND ?) OR
                        (? BETWEEN EVENTO_FECHAI AND EVENTO_FECHAF) OR
                        (? BETWEEN EVENTO_FECHAI AND EVENTO_FECHAF)
                    )
                {$andExcluye}
            ";
            $res1 = $db->query($sql_esp, array_merge([$eespecialista, $inicio, $fin, $inicio, $fin, $inicio, $fin], $paramsExcl));
            if (is_array($res1) && count($res1) > 0) {
                $mensajes[] = "Ya hay un evento para el especialista en ese horario.";
            }
        }

        // 2) Conflicto por chofer (solo si trae valor)
        if (!empty($echofer) && strtoupper($echofer) !== 'NULL') {
            $sql_chofer = "
                SELECT FIRST 1 EVENTO_ID
                FROM AMPAR_HIS_EVENTOS
                WHERE EVENTO_STATUSGENERAL IN (14,15)
                AND EVENTO_CHOFERID = ?
                AND (
                        (EVENTO_FECHAI BETWEEN ? AND ?) OR
                        (EVENTO_FECHAF BETWEEN ? AND ?) OR
                        (? BETWEEN EVENTO_FECHAI AND EVENTO_FECHAF) OR
                        (? BETWEEN EVENTO_FECHAI AND EVENTO_FECHAF)
                    )
                {$andExcluye}
            ";
            $res2 = $db->query($sql_chofer, array_merge([$echofer, $inicio, $fin, $inicio, $fin, $inicio, $fin], $paramsExcl));
            if (is_array($res2) && count($res2) > 0) {
                $mensajes[] = "Ya hay un evento para el chofer en ese horario.";
            }
        }

        // 3) Conflicto por maletas (solo si hay maletas)
        if (!empty($emaletaid) && is_array($emaletaid)) {
            // place-holders para IN (...)
            $placeholders = implode(',', array_fill(0, count($emaletaid), '?'));
            $sql_maletas = "
                SELECT DISTINCT COALESCE(a.ALMACEN_NOMBRE, ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS ALMACEN_NOMBRE
                FROM AMPAR_HIS_EVENTOS e
                JOIN AMPAR_HIS_EVENTOSMALETAS m ON e.EVENTO_ID = m.EVENTOMALETA_EVENTOID
                LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = m.EVENTOMALETA_MALETAID
                LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -m.EVENTOMALETA_MALETAID
                LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
                WHERE e.EVENTO_STATUSGENERAL IN (14,15)
                AND (
                        (e.EVENTO_FECHAI BETWEEN ? AND ?) OR
                        (e.EVENTO_FECHAF BETWEEN ? AND ?) OR
                        (? BETWEEN e.EVENTO_FECHAI AND e.EVENTO_FECHAF) OR
                        (? BETWEEN e.EVENTO_FECHAI AND e.EVENTO_FECHAF)
                    )
                AND m.EVENTOMALETA_MALETAID IN ($placeholders)
                {$andExcluye}
            ";
            $params = array_merge([$inicio, $fin, $inicio, $fin, $inicio, $fin], $emaletaid, $paramsExcl);
            $conflictos = $db->query($sql_maletas, $params);

            if (is_array($conflictos) && count($conflictos) > 0) {
                $nombres = array_column($conflictos, 'ALMACEN_NOMBRE');
                $mensajes[] = "Las siguientes maletas ya están asignadas en ese horario: " . implode(", ", $nombres);
            }
        }

        return $mensajes;
    }

    function nuevoevento(
        $esucalmacen,
        $etipoevento,
        $edescripcion,
        $tipocliente,
        $eclienteid,
        $nombreparticular,
        $presupuestoparticular,
        $efechai,
        $efechaf,
        $elugar,
        $emedicor,
        $emedicoi,
        $eespecialista,
        $echofer,
        $emaletaid,
        $proveedores,
        $observaciones,
        $ealmacen = null
    ) {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        // Para bitácora
        $sqlBit = "INSERT INTO AMPAR_BITACORA
                    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
                VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)";

        // 1) Validar empalmes (fuera de transacción explícita)
        $conflictos = $this->eventoEmpalmadoDetallado($db, $efechai, $efechaf, $eespecialista, $echofer, $emaletaid);
        if (count($conflictos) > 0) {
            echo implode("\n", $conflictos);
            $db->close();
            return;
        }

        // 2) Normalizar fechas del front
        $fi = DateTime::createFromFormat('Y-m-d\TH:i', $efechai);
        $ff = DateTime::createFromFormat('Y-m-d\TH:i', $efechaf);
        if ($fi) $efechai = $fi->format('Y-m-d H:i:s');
        if ($ff) $efechaf = $ff->format('Y-m-d H:i:s');

        // 3) Cliente / Particular (Campo libre: si no es ID numérico de catálogo, se guarda en EVENTO_NOMBREPARTICULAR y EVENTO_CLIENTEID queda NULL)
        $valCli = trim((string)$eclienteid);
        if (is_numeric($valCli) && (int)$valCli > 0) {
            $clienteaux = (int)$valCli;
            $nombrepaux = "";
        } else {
            $clienteaux = null;
            $nombrepaux = $valCli;
        }
        $presupuestopaux = ($tipocliente == "cliente") ? "" : $presupuestoparticular;

        // === Transacción
        $didBegin = false;
        if (method_exists($db, 'inTransaction') && method_exists($db, 'beginTransaction')) {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $didBegin = true;
            }
        } else if (method_exists($db, 'beginTransaction')) {
            try {
                $db->beginTransaction();
                $didBegin = true;
            } catch (Throwable $e) {
            }
        }

        // Buffers para correos y notificaciones (todo sin enviar aún)
        $emailsToSend        = []; // cada item: ['to'=>..,'name'=>..,'subject'=>..,'html'=>..]
        $notificationsToSend = []; // cada item: ['usuarioid'=>..,'eventoid'=>..,'tipo'=>..,'titulo'=>..,'mensaje'=>..]
        try {

            // 4) Insert evento
            $sql = "
                INSERT INTO AMPAR_HIS_EVENTOS
                (
                    EVENTO_FOLIO, EVENTO_SUCURSALID, EVENTO_ALMACENID, EVENTO_FECHACREACION, EVENTO_FECHAI, EVENTO_FECHAF,
                    EVENTO_CONCEPTO, EVENTO_CLIENTEID, EVENTO_NOMBREPARTICULAR, EVENTO_PRESUPUESTOPARTICULAR,
                    EVENTO_HOSPITALID, EVENTO_DOCTORREFERIDORID, EVENTO_DOCTORINTERVENCIONISTAID,
                    EVENTO_TIPOEVENTO, EVENTO_ESPECIALISTAID, EVENTO_CHOFERID,
                    EVENTO_ESPECIALISTAIDSTATUS, EVENTO_CHOFERIDSTATUS, EVENTO_STATUSGENERAL, EVENTO_CLIENTETIPOID  
                )
                VALUES
                (
                    (
                        SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(EVENTO_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0')
                            || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                        FROM AMPAR_HIS_EVENTOS
                    ),
                    " . $esucalmacen . ",
                    " . (($ealmacen == "" || $ealmacen == null) ? "NULL" : $ealmacen) . ",
                    CURRENT_TIMESTAMP,
                    '" . $efechai . "',
                    '" . $efechaf . "',
                    '" . $edescripcion . "',
                    " . (($clienteaux === "" || $clienteaux === null) ? "NULL" : "'" . str_replace("'", "''", (string)$clienteaux) . "'") . ",
                    " . (($nombrepaux === "" || $nombrepaux === null) ? "NULL" : "'" . str_replace("'", "''", (string)$nombrepaux) . "'") . ",
                    " . (($presupuestopaux === "" || $presupuestopaux === null) ? "NULL" : "'" . str_replace("'", "''", (string)$presupuestopaux) . "'") . ",
                    " . $elugar . ",
                    " . $emedicor . ",
                    " . $emedicoi . ",
                    " . $etipoevento . ",
                    " . $eespecialista . ",
                    " . $echofer . ",
                    " . (($eespecialista == 'NULL') ? 21 : 20) . ",
                    " . (($echofer == 'NULL') ? 21 : 20) . ",
                    14,
                    " . $tipocliente . "
                )
            ";
            $eventoid = $db->executeconreturning($sql, 'EVENTO_ID');
            if (!$eventoid) {
                throw new Exception("Error al insertar registro.");
            }

            // 5) Maletas
            if (!empty($emaletaid) && is_array($emaletaid)) {
                foreach ($emaletaid as $mal) {
                    $db->execute("
                        INSERT INTO AMPAR_HIS_EVENTOSMALETAS (EVENTOMALETA_EVENTOID, EVENTOMALETA_MALETAID)
                        VALUES (?, ?)
                    ", [$eventoid, $mal]);
                }
            }

            // 6) Proveedores
            if (!empty($proveedores) && is_array($proveedores)) {
                foreach ($proveedores as $i => $proveedor_id) {
                    $obs = $observaciones[$i] ?? '';
                    $db->execute("
                        INSERT INTO AMPAR_HIS_EVENTOPROVEEDOR
                        (EVENTOPROVEEDOR_EVENTOID, EVENTOPROVEEDOR_PROVEEDORID, EVENTOPROVEEDOR_OBSERVACIONES)
                        VALUES (?, ?, ?)
                    ", [$eventoid, $proveedor_id, $obs]);
                }
            }

            // 7) Info para bitácora + para mails (TODO en UNA sola consulta base)
            $infoevento = $db->query("
                SELECT
                    E.EVENTO_ID, E.EVENTO_FOLIO, CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
                    E.EVENTO_FECHAI, E.EVENTO_FECHAF, DATEDIFF(HOUR FROM EVENTO_FECHAI TO EVENTO_FECHAF) AS DURACION_HORAS,
                    E.EVENTO_STATUSGENERAL, S.STATUS_NOMBRE STATUS_NOMBREGRAL,
                    E.EVENTO_SUCURSALID, SU.SUCURSAL_ID, SU.NOMBRE SUCURSAL_NOMBRE,
                    E.EVENTO_CLIENTEID, C.NOMBRE CLIENTE_NOMBRE, E.EVENTO_HOSPITALID, H.HOSPITAL_NOMBRE,
                    E.EVENTO_TIPOEVENTO, TE.TIPOEVENTO_NOMBRE,
                    TESG.TIPOEVENTOSUBGRUPO_ID, TESG.TIPOEVENTOSUBGRUPO_NOMBRE,
                    TEG.TIPOEVENTOGRUPO_ID, TEG.TIPOEVENTOGRUPO_NOMBRE,
                    E.EVENTO_DOCTORINTERVENCIONISTAID, MI.MEDICO_NOMBRE MEDICO_INTERVENCIONISTA,
                    E.EVENTO_DOCTORREFERIDORID, MR.MEDICO_NOMBRE MEDICO_REFERIDOR,
                    E.EVENTO_ESPECIALISTAID, UES.USUARIO_NOMBRE ESPECIALISTA_NOMBRE,
                    E.EVENTO_ESPECIALISTAIDSTATUS, EE.STATUS_NOMBRE STATUSESPECIALISTA,
                    E.EVENTO_CHOFERID, UCH.USUARIO_NOMBRE CHOFER_NOMBRE,
                    E.EVENTO_CHOFERIDSTATUS, EC.STATUS_NOMBRE STATUSCHOFER,
                    E.EVENTO_FECHACREACION
                FROM AMPAR_HIS_EVENTOS E
                LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = E.EVENTO_SUCURSALID
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = EVENTO_STATUSGENERAL
                LEFT JOIN AMPAR_CAT_HOSPITALES H ON HOSPITAL_ID = EVENTO_HOSPITALID
                LEFT JOIN CLIENTES C ON CLIENTE_ID = EVENTO_CLIENTEID
                LEFT JOIN AMPAR_CAT_MEDICOS MI ON MI.MEDICO_ID = E.EVENTO_DOCTORINTERVENCIONISTAID
                LEFT JOIN AMPAR_CAT_MEDICOS MR ON MR.MEDICO_ID = E.EVENTO_DOCTORREFERIDORID
                LEFT JOIN AMPAR_CAT_TIPOEVENTO TE ON TIPOEVENTO_ID = EVENTO_TIPOEVENTO
                LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TESG ON TESG.TIPOEVENTOSUBGRUPO_ID = TE.TIPOEVENTO_SUBGRUPOID
                LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO TEG ON TEG.TIPOEVENTOGRUPO_ID = TESG.TIPOEVENTOSUBGRUPO_GRUPOID
                LEFT JOIN AMPAR_CAT_USUARIOS UES ON UES.USUARIO_ID = EVENTO_ESPECIALISTAID
                LEFT JOIN AMPAR_CONF_STATUS EE ON EE.STATUS_ID = EVENTO_ESPECIALISTAIDSTATUS
                LEFT JOIN AMPAR_CAT_USUARIOS UCH ON UCH.USUARIO_ID = EVENTO_CHOFERID
                LEFT JOIN AMPAR_CONF_STATUS EC ON EC.STATUS_ID = EVENTO_CHOFERIDSTATUS
                WHERE E.EVENTO_ID = ?
            ", [$eventoid]);
            $ev = $infoevento[0];

            // Maletas (para bitácora y mail)
            $maletasRows = $db->query("
                SELECT 
                    COALESCE(a.ALMACEN_ID, em.EVENTOMALETA_MALETAID) AS ALMACEN_ID,
                    COALESCE(a.ALMACEN_FOLIO, EC_EQ.FOLIO, ST_EQ.STOCK_FOLIO) AS ALMACEN_FOLIO,
                    COALESCE(a.ALMACEN_NOMBRE, ART_EC.NOMBRE || ' (EQUIPO CAPITAL)', ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS ALMACEN_NOMBRE,
                    em.EVENTOMALETA_ID
                FROM AMPAR_HIS_EVENTOSMALETAS em
                LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = em.EVENTOMALETA_MALETAID
                LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
                LEFT JOIN AMPAR_EQUIPOCAPITAL EC_EQ ON EC_EQ.EQUIPOCAPITAL_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC_EQ.ARTICULO_ID
                WHERE em.EVENTOMALETA_EVENTOID = ?
            ", [$eventoid]);

            // Bitácora general
            $comentarioGeneral = 'SOLICITUD DE EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong> (Sucursal: ' . $ev['SUCURSAL_NOMBRE'] . ') Campos:' . bitacora::printarray($ev);
            $db->execute($sqlBit, [
                $comentarioGeneral,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $ev['EVENTO_SUCURSALID'] ?? null,
                null,
                null,
                $ev['EVENTO_ID'] ?? null
            ]);

            // Bitácora maletas
            foreach ($maletasRows as $ie) {
                if (!empty($ie['EVENTOMALETA_ID'])) {
                    $almIdBit = isset($ie['ALMACEN_ID']) && (int)$ie['ALMACEN_ID'] > 0 ? (int)$ie['ALMACEN_ID'] : null;
                    $stkIdBit = isset($ie['ALMACEN_ID']) && (int)$ie['ALMACEN_ID'] < 0 ? abs((int)$ie['ALMACEN_ID']) : null;
                    $comentarioMaleta = 'MALETA <b>' . $ie['ALMACEN_FOLIO'] . '-' . $ie['ALMACEN_NOMBRE'] . '</b> ASIGNADA A EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong> (Sucursal: ' . $ev['SUCURSAL_NOMBRE'] . ')';
                    $db->execute($sqlBit, [
                        $comentarioMaleta,
                        $usersesion['USUARIO_ID'],
                        $usersesion['USUARIO_CORREO'],
                        $ipuser,
                        $ev['EVENTO_SUCURSALID'] ?? null,
                        $almIdBit,
                        $stkIdBit,
                        $ev['EVENTO_ID'] ?? null
                    ]);
                }
            }

            // ===== Preparar datos para correos (sin enviar aún) =====
            // Armamos un arreglo $evMail con la info necesaria + maletas como lista
            $evMail = $ev;
            $evMail['_MALETAS'] = array_map(function ($r) {
                return $r['ALMACEN_FOLIO'] . ' - ' . $r['ALMACEN_NOMBRE'];
            }, $maletasRows ?: []);

            // 7.1 Asignación directa (especialista / chofer) ó invitación masiva
            $ctaUrl = $this->urlEvento($eventoid);

            // ESPECIALISTA
            if ($eespecialista <> "NULL" && $eespecialista !== null && $eespecialista !== '') {
                // correo de la persona asignada
                $row = $db->query("SELECT USUARIO_CORREO, USUARIO_NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?", [$eespecialista]);
                if ($row && !empty($row[0]['USUARIO_CORREO'])) {
                    $titulo = "Asignación de Especialista – Evento " . $evMail['EVENTO_FOLIO'];
                    $intro  = "Has sido asignado(a) como <b>Especialista</b> al siguiente evento.";
                    $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Ver y confirmar", $ctaUrl);
                    $emailsToSend[] = [
                        'to' => $row[0]['USUARIO_CORREO'],
                        'name' => $row[0]['USUARIO_NOMBRE'],
                        'subject' => $titulo,
                        'html' => $html
                    ];
                }
                // Notificación interna
                $notificationsToSend[] = [
                    'usuarioid' => $eespecialista,
                    'eventoid'  => $eventoid,
                    'tipo'      => 'ESPECIALISTA',
                    'titulo'    => 'Invitación a evento ' . $evMail['EVENTO_FOLIO'],
                    'mensaje'   => 'Fuiste asignado(a) como Especialista al evento ' . $evMail['EVENTO_FOLIO']
                ];
            } else {
                // invitación masiva a especialistas de la sucursal y subgrupo
                $destEsp = $db->query("
                    SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
                    FROM AMPAR_CAT_USUARIOSSUCURSALES S
                    JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
                    JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
                    WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
                    AND T.USUARIOSTIPOPERMISOS_TIPOID = 2
                    ORDER BY U.USUARIO_NOMBRE
                ", [$evMail['SUCURSAL_ID'] ?? $evMail['EVENTO_SUCURSALID']]);
                if ($destEsp) {
                    $titulo = "Invitación – Evento " . $evMail['EVENTO_FOLIO'] . " (" . $evMail['TIPOEVENTO_NOMBRE'] . ")";
                    $intro  = "Se ha creado un evento y estás invitado(a) como <b>Especialista</b>. Si te interesa participar, ingresa al portal para confirmar tu asistencia.";
                    $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Entrar y postularme", $ctaUrl);
                    foreach ($destEsp as $d) {
                        if (!empty($d['USUARIO_CORREO'])) {
                            $emailsToSend[] = [
                                'to' => $d['USUARIO_CORREO'],
                                'name' => $d['USUARIO_NOMBRE'],
                                'subject' => $titulo,
                                'html' => $html
                            ];
                        }
                        // Notificación interna
                        $notificationsToSend[] = [
                            'usuarioid' => $d['USUARIO_ID'],
                            'eventoid'  => $eventoid,
                            'tipo'      => 'ESPECIALISTA',
                            'titulo'    => 'Invitación a evento ' . $evMail['EVENTO_FOLIO'],
                            'mensaje'   => 'Hay un nuevo evento disponible (' . $evMail['TIPOEVENTO_NOMBRE'] . ') que requiere Especialista. Acepta o rechaza la invitación.'
                        ];
                    }
                }
            }

            // CHOFER
            if ($echofer <> "NULL" && $echofer !== null && $echofer !== '') {
                $row = $db->query("SELECT USUARIO_CORREO, USUARIO_NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?", [$echofer]);
                if ($row && !empty($row[0]['USUARIO_CORREO'])) {
                    $titulo = "Asignación de Chofer – Evento " . $evMail['EVENTO_FOLIO'];
                    $intro  = "Has sido asignado(a) como <b>Chofer</b> al siguiente evento.";
                    $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Ver y confirmar", $ctaUrl);
                    $emailsToSend[] = [
                        'to' => $row[0]['USUARIO_CORREO'],
                        'name' => $row[0]['USUARIO_NOMBRE'],
                        'subject' => $titulo,
                        'html' => $html
                    ];
                }
                // Notificación interna
                $notificationsToSend[] = [
                    'usuarioid' => $echofer,
                    'eventoid'  => $eventoid,
                    'tipo'      => 'CHOFER',
                    'titulo'    => 'Invitación a evento ' . $evMail['EVENTO_FOLIO'],
                    'mensaje'   => 'Fuiste asignado(a) como Chofer al evento ' . $evMail['EVENTO_FOLIO']
                ];
            } else {
                $destCho = $db->query("
                    SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
                    FROM AMPAR_CAT_USUARIOSSUCURSALES S
                    JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
                    JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
                    WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
                    AND T.USUARIOSTIPOPERMISOS_TIPOID = 3
                    ORDER BY U.USUARIO_NOMBRE
                ", [$evMail['SUCURSAL_ID'] ?? $evMail['EVENTO_SUCURSALID']]);
                if ($destCho) {
                    $titulo = "Invitación – Evento " . $evMail['EVENTO_FOLIO'] . " (" . $evMail['TIPOEVENTO_NOMBRE'] . ")";
                    $intro  = "Se ha creado un evento y estás invitado(a) como <b>Chofer</b>. Si te interesa participar, ingresa al portal para confirmar tu asistencia.";
                    $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Entrar y postularme", $ctaUrl);
                    foreach ($destCho as $d) {
                        if (!empty($d['USUARIO_CORREO'])) {
                            $emailsToSend[] = [
                                'to' => $d['USUARIO_CORREO'],
                                'name' => $d['USUARIO_NOMBRE'],
                                'subject' => $titulo,
                                'html' => $html
                            ];
                        }
                        // Notificación interna
                        $notificationsToSend[] = [
                            'usuarioid' => $d['USUARIO_ID'],
                            'eventoid'  => $eventoid,
                            'tipo'      => 'CHOFER',
                            'titulo'    => 'Invitación a evento ' . $evMail['EVENTO_FOLIO'],
                            'mensaje'   => 'Hay un nuevo evento disponible (' . $evMail['TIPOEVENTO_NOMBRE'] . ') que requiere Chofer. Acepta o rechaza la invitación.'
                        ];
                    }
                }
            }

            // PROVEEDORES (solo los agregados)
            if (!empty($proveedores) && is_array($proveedores)) {
                $idsProv = array_values(array_filter(array_map('intval', $proveedores)));
                if (!empty($idsProv)) {
                    $ph = implode(',', array_fill(0, count($idsProv), '?'));
                    $provs = $db->query("SELECT PROVEEDOR_ID, NOMBRE, EMAIL FROM PROVEEDORES WHERE PROVEEDOR_ID IN ($ph)", $idsProv);
                    if ($provs) {
                        $titulo = "Invitación como Proveedor – Evento " . $evMail['EVENTO_FOLIO'];
                        $intro  = "Has sido agregado(a) como <b>Proveedor</b> al siguiente evento.";
                        $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Ver detalles del evento", $ctaUrl);
                        foreach ($provs as $p) {
                            if (!empty($p['EMAIL'])) {
                                $emailsToSend[] = [
                                    'to' => $p['EMAIL'],
                                    'name' => $p['NOMBRE'] ?? 'Proveedor',
                                    'subject' => $titulo,
                                    'html' => $html
                                ];
                            }
                        }
                    }
                }
            }

            // 8) Commit explícito
            if ($didBegin && method_exists($db, 'commit')) {
                $db->commit();
            }

            // 9) Cerrar conexión ANTES de mandar correos
            $db->close();
        } catch (Throwable $e) {
            if ($didBegin && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            $db->close();
            echo "Error al guardar: " . $e->getMessage();
            return;
        }

        // 10) Enviar correos (ya sin tocar BD)
        foreach ($emailsToSend as $m) {
            try {
                $this->enviarMail($m['to'], $m['name'], $m['subject'], $m['html']);
            } catch (Throwable $e) {
                // No detener el flujo si un correo falla: puedes loguearlo si quieres
                // error_log('Fallo envío mail a '.$m['to'].': '.$e->getMessage());
            }
        }

        // 11) Insertar notificaciones internas + WhatsApp
        foreach ($notificationsToSend as $n) {
            try {
                notificaciones::crear($n['usuarioid'], $n['eventoid'], $n['tipo'], $n['titulo'], $n['mensaje']);
            } catch (Throwable $e) {
            }
            // 📱 WhatsApp al usuario asignado
            try {
                $telUsuario = whatsapp::getTelefonoUsuario((int)$n['usuarioid']);
                if ($telUsuario) {
                    // Como $evMail no está definido globalmente en este punto (está dentro de los if anteriores),
                    // usaremos un regex rápido sobre $n['mensaje'] para sacar el folio, o lo sacamos del título
                    $folio = '';
                    if (preg_match('/evento ([\d\-]+)/i', $n['titulo'], $m)) {
                        $folio = $m[1];
                    }
                    $fechaProgramada = !empty($evMail['EVENTO_FECHAI']) ? date('d/m/Y H:i', strtotime($evMail['EVENTO_FECHAI'])) : 'N/A';
                    $rol    = ($n['tipo'] === 'ESPECIALISTA') ? '🔬 Especialista' : '🚗 Chofer';
                    $msgWA  = "{$rol} — *Asignación a Evento*\n";
                    $msgWA .= "Folio: *{$folio}*\n";
                    $msgWA .= "Fecha Prog.: *{$fechaProgramada}*\n";
                    $msgWA .= $n['mensaje'] . "\n";
                    $msgWA .= "Ingresa al sistema para aceptar o rechazar:\n" . $ctaUrl;
                    whatsapp::enviar($telUsuario, $msgWA);
                }
            } catch (Throwable $eWA) {
                error_log("[WhatsApp] Error notificación asignación crear: " . $eWA->getMessage());
            }
        }

        // Éxito (tu front espera cadena vacía)
        echo "";
    }

    public function editarevento($eventoid, $cambiosJson)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        $sqlBit = "INSERT INTO AMPAR_BITACORA
            (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)";

        $parseFrontDate = function ($v) {
            if ($v === null || $v === '') return null;
            if (strpos($v, 'T') !== false) {
                $dt = DateTime::createFromFormat('Y-m-d\TH:i', $v);
                if (!$dt) $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $v);
            } else {
                $dt = DateTime::createFromFormat('Y-m-d H:i', $v);
                if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i:s', $v);
            }
            return $dt ? $dt->format('Y-m-d H:i:s') : null;
        };

        // Buffer de correos (se envían DESPUÉS del commit y close)
        $emailsToSend = []; // cada item: ['to'=>..,'name'=>..,'subject'=>..,'html'=>..]

        // 1) Validar cambios
        $cambios = json_decode($cambiosJson, true);
        if (!is_array($cambios)) {
            echo "No se recibieron cambios válidos";
            $db->close();
            return false;
        }
        if (empty($cambios['cabecera']) && empty($cambios['maletas']) && empty($cambios['proveedores'])) {
            echo "No hay cambios para procesar";
            $db->close();
            return false;
        }

        // 2) Info actual (incluye subgrupo para invitaciones masivas)
        $info = $db->query("
            SELECT
                E.EVENTO_ID, E.EVENTO_FOLIO, CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
                E.EVENTO_FECHAI, E.EVENTO_FECHAF, E.EVENTO_SUCURSALID,
                E.EVENTO_CLIENTEID, E.EVENTO_PRESUPUESTOPARTICULAR,
                E.EVENTO_HOSPITALID, E.EVENTO_DOCTORREFERIDORID, E.EVENTO_DOCTORINTERVENCIONISTAID,
                E.EVENTO_TIPOEVENTO, E.EVENTO_ESPECIALISTAID, E.EVENTO_CHOFERID, EVENTO_CLIENTETIPOID,
                SU.SUCURSAL_ID, SU.NOMBRE AS SUCURSAL_NOMBRE,
                TE.TIPOEVENTO_NOMBRE,
                TESG.TIPOEVENTOSUBGRUPO_ID
            FROM AMPAR_HIS_EVENTOS E
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = E.EVENTO_SUCURSALID
            LEFT JOIN AMPAR_CAT_TIPOEVENTO TE ON TE.TIPOEVENTO_ID = E.EVENTO_TIPOEVENTO
            LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TESG ON TESG.TIPOEVENTOSUBGRUPO_ID = TE.TIPOEVENTO_SUBGRUPOID
            WHERE E.EVENTO_ID = ?
        ", [$eventoid]);
        if (!$info || !isset($info[0])) {
            echo "Evento no encontrado";
            $db->close();
            return false;
        }
        $ev = $info[0];

        // Maletas actuales
        $currMaletas = $db->query("
            SELECT EM.EVENTOMALETA_ID, EM.EVENTOMALETA_MALETAID AS ALMACEN_ID,
                COALESCE(A.ALMACEN_FOLIO, EC_EQ.FOLIO, ST_EQ.STOCK_FOLIO) AS ALMACEN_FOLIO,
                COALESCE(A.ALMACEN_NOMBRE, ART_EC.NOMBRE || ' (EQUIPO CAPITAL)', ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS ALMACEN_NOMBRE
            FROM AMPAR_HIS_EVENTOSMALETAS EM
            LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = EM.EVENTOMALETA_MALETAID
            LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID < 0
            LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
            LEFT JOIN AMPAR_EQUIPOCAPITAL EC_EQ ON EC_EQ.EQUIPOCAPITAL_ID = -EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID < 0
            LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC_EQ.ARTICULO_ID
            WHERE EM.EVENTOMALETA_EVENTOID = ?
        ", [$eventoid]);

        // 3) Valores finales (para empalmes)
        $fechai_final = isset($cambios['cabecera']['fechai'])
            ? $parseFrontDate($cambios['cabecera']['fechai']['nuevo'] ?? null)
            : $ev['EVENTO_FECHAI'];
        $fechaf_final = isset($cambios['cabecera']['fechaf'])
            ? $parseFrontDate($cambios['cabecera']['fechaf']['nuevo'] ?? null)
            : $ev['EVENTO_FECHAF'];

        $especialista_final = isset($cambios['cabecera']['especialista'])
            ? (($cambios['cabecera']['especialista']['nuevo']['id'] ?? '') ?: null)
            : $ev['EVENTO_ESPECIALISTAID'];

        $chofer_final = isset($cambios['cabecera']['chofer'])
            ? (($cambios['cabecera']['chofer']['nuevo']['id'] ?? '') ?: null)
            : $ev['EVENTO_CHOFERID'];

        $maletas_final = [];
        foreach ($currMaletas as $m) {
            $maletas_final[(string)$m['ALMACEN_ID']] = true;
        }
        if (!empty($cambios['maletas'])) {
            foreach ($cambios['maletas'] as $chg) {
                if ($chg['tipo'] === 'agregado' && !empty($chg['almacen_id'])) {
                    $maletas_final[(string)$chg['almacen_id']] = true;
                } elseif ($chg['tipo'] === 'eliminado' && !empty($chg['almacen_id'])) {
                    unset($maletas_final[(string)$chg['almacen_id']]);
                }
            }
        }
        $maletas_final_ids = array_map('intval', array_keys($maletas_final));

        // 4) Validar empalmes si aplica
        $hayCambiosEmpalme = !empty($cambios['cabecera']['fechai']) ||
            !empty($cambios['cabecera']['fechaf']) ||
            !empty($cambios['cabecera']['especialista']) ||
            !empty($cambios['cabecera']['chofer']) ||
            !empty($cambios['maletas']);
        if ($hayCambiosEmpalme) {
            $conflictos = $this->eventoEmpalmadoDetallado(
                $db,
                $fechai_final,
                $fechaf_final,
                $especialista_final ?? 'NULL',
                $chofer_final ?? 'NULL',
                $maletas_final_ids,
                $eventoid
            );
            if (count($conflictos) > 0) {
                echo implode("\n", $conflictos);
                $db->close();
                return false;
            }
        }

        // 5) Transacción (si no hay otra activa)
        $didBegin = false;
        if (method_exists($db, 'inTransaction') && method_exists($db, 'beginTransaction')) {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $didBegin = true;
            }
        } elseif (method_exists($db, 'beginTransaction')) {
            try {
                $db->beginTransaction();
                $didBegin = true;
            } catch (Throwable $e) {
            }
        }

        try {
            // 6) UPDATE cabecera
            $fields = [];
            $params = [];
            $comentariosCab = [];
            $ctaUrl = $this->urlEvento($eventoid);

            if (isset($cambios['cabecera']['descripcion'])) {
                $fields[] = "EVENTO_CONCEPTO = ?";
                $params[] = $cambios['cabecera']['descripcion']['nuevo'] ?? null;
                $comentariosCab[] = "<strong>Descripción</strong>: " . htmlentities($cambios['cabecera']['descripcion']['original'] ?? '') . " → " . htmlentities($cambios['cabecera']['descripcion']['nuevo'] ?? '');
            }
            if (isset($cambios['cabecera']['fechai'])) {
                $fields[] = "EVENTO_FECHAI = ?";
                $params[] = $parseFrontDate($cambios['cabecera']['fechai']['nuevo'] ?? null);
                $comentariosCab[] = "<strong>Fecha Inicio</strong>: " . ($cambios['cabecera']['fechai']['original'] ?? '') . " → " . ($cambios['cabecera']['fechai']['nuevo'] ?? '');
            }
            if (isset($cambios['cabecera']['fechaf'])) {
                $fields[] = "EVENTO_FECHAF = ?";
                $params[] = $parseFrontDate($cambios['cabecera']['fechaf']['nuevo'] ?? null);
                $comentariosCab[] = "<strong>Fecha Fin</strong>: " . ($cambios['cabecera']['fechaf']['original'] ?? '') . " → " . ($cambios['cabecera']['fechaf']['nuevo'] ?? '');
            }

            // Selects con ID + TEXTO
            $mapSelects = [
                'tipoevento'             => ['col' => 'EVENTO_TIPOEVENTO',               'label' => 'Tipo de evento'],
                'lugar'                  => ['col' => 'EVENTO_HOSPITALID',               'label' => 'Lugar'],
                'medico_referidor'       => ['col' => 'EVENTO_DOCTORREFERIDORID',        'label' => 'Médico Referidor'],
                'medico_intervencionista' => ['col' => 'EVENTO_DOCTORINTERVENCIONISTAID', 'label' => 'Médico Intervencionista'],
                'especialista'           => ['col' => 'EVENTO_ESPECIALISTAID',           'label' => 'Especialista'],
                'chofer'                 => ['col' => 'EVENTO_CHOFERID',                 'label' => 'Chofer'],
            ];
            $cambioEsp = false;
            $nuevoEspId = null;
            $origEspId = $ev['EVENTO_ESPECIALISTAID'];
            $cambioCho = false;
            $nuevoChoId = null;
            $origChoId = $ev['EVENTO_CHOFERID'];

            foreach ($mapSelects as $key => $conf) {
                if (isset($cambios['cabecera'][$key])) {
                    $nuevoId = $cambios['cabecera'][$key]['nuevo']['id'] ?? null;
                    $nuevoTx = $cambios['cabecera'][$key]['nuevo']['texto'] ?? '';
                    $origId  = $cambios['cabecera'][$key]['original']['id'] ?? null;
                    $origTx  = $cambios['cabecera'][$key]['original']['texto'] ?? '';

                    $nuevoId = ($nuevoId === '' || $nuevoId === '0') ? null : $nuevoId;

                    $fields[] = $conf['col'] . " = ?";
                    $params[] = $nuevoId;

                    if ($key === 'especialista') {
                        $fields[] = "EVENTO_ESPECIALISTAIDSTATUS = ?";
                        $params[] = ($nuevoId === null ? 21 : 20);
                        $cambioEsp = true;
                        $nuevoEspId = $nuevoId;
                    }
                    if ($key === 'chofer') {
                        $fields[] = "EVENTO_CHOFERIDSTATUS = ?";
                        $params[] = ($nuevoId === null ? 21 : 20);
                        $cambioCho = true;
                        $nuevoChoId = $nuevoId;
                    }

                    $comentariosCab[] = "<strong>{$conf['label']}</strong>: ({$origId} - " . htmlentities($origTx) . ") → ({$nuevoId} - " . htmlentities($nuevoTx) . ")";
                }
            }

            // ===== Tipo de Cliente (ID), Cliente (ID) y Presupuesto =====
            // El front NUEVO manda 'tipocliente_id' 

            $cambioTipoClienteId = isset($cambios['cabecera']['tipocliente_id']);
            $cambioCliente       = isset($cambios['cabecera']['cliente']);
            $cambioPres          = isset($cambios['cabecera']['presupuesto']);

            // 2.1) EVENTO_CLIENTETIPOID
            if ($cambioTipoClienteId) {
                $orig = (string)($cambios['cabecera']['tipocliente_id']['original'] ?? ($ev['EVENTO_CLIENTETIPOID'] ?? ''));
                $nuevo = (string)($cambios['cabecera']['tipocliente_id']['nuevo']    ?? $orig);

                $fields[] = "EVENTO_CLIENTETIPOID = ?";
                $params[] = ($nuevo === '' ? null : (int)$nuevo);

                // Comentario (si traes los textos, opcional)
                $txtOrig  = $cambios['cabecera']['tipocliente_id']['original_text'] ?? '';
                $txtNuevo = $cambios['cabecera']['tipocliente_id']['nuevo_text']    ?? '';
                if ($txtOrig !== '' || $txtNuevo !== '') {
                    $comentariosCab[] = "<strong>Tipo de cliente</strong>: " . htmlentities($txtOrig) . " → " . htmlentities($txtNuevo);
                } else {
                    $comentariosCab[] = "<strong>Tipo de cliente</strong>: {$orig} → {$nuevo}";
                }
            }

            // 2.2) EVENTO_CLIENTEID (ahora campo libre / nombre del paciente)
            if ($cambioCliente) {
                $co = $cambios['cabecera']['cliente']['original'] ?? ['id' => null, 'texto' => ''];
                $cn = $cambios['cabecera']['cliente']['nuevo']    ?? ['id' => null, 'texto' => ''];

                $nuevoClienteVal = trim((string)($cn['texto'] !== '' && $cn['texto'] !== null ? $cn['texto'] : ($cn['id'] ?? '')));
                if (is_numeric($nuevoClienteVal) && (int)$nuevoClienteVal > 0) {
                    $nuevoClienteId = (int)$nuevoClienteVal;
                    $nuevoNombrePart = null;
                } else {
                    $nuevoClienteId = null;
                    $nuevoNombrePart = ($nuevoClienteVal === '' ? null : $nuevoClienteVal);
                }

                $fields[] = "EVENTO_CLIENTEID = ?";
                $params[] = $nuevoClienteId;
                $fields[] = "EVENTO_NOMBREPARTICULAR = ?";
                $params[] = $nuevoNombrePart;

                $oldTxt = ($co['texto'] !== '' && $co['texto'] !== null) ? $co['texto'] : ($co['id'] ?? '');
                $newTxt = ($cn['texto'] !== '' && $cn['texto'] !== null) ? $cn['texto'] : ($cn['id'] ?? '');
                $comentariosCab[] = "<strong>Nombre del paciente</strong>: " . htmlentities((string)$oldTxt) . " → " . htmlentities((string)$newTxt);
            }

            // 2.3) Presupuesto (si lo usas)
            if ($cambioPres) {
                $orig = $cambios['cabecera']['presupuesto']['original'] ?? ($ev['EVENTO_PRESUPUESTOPARTICIPULAR'] ?? $ev['EVENTO_PRESUPUESTOPARTICULAR'] ?? '');
                $nuevo = $cambios['cabecera']['presupuesto']['nuevo']    ?? '';

                $fields[] = "EVENTO_PRESUPUESTOPARTICULAR = ?";
                $params[] = ($nuevo === '' ? null : $nuevo);

                $comentariosCab[] = "<strong>Presupuesto</strong>: " . htmlentities((string)$orig) . " → " . htmlentities((string)$nuevo);
            }

            // 2.4) Sucursal (Detecta si el usuario cambió el Almacén en el frontend)
            $cambioSucursal = isset($cambios['cabecera']['sucursal_id']);
            if ($cambioSucursal) {
                $nuevoSuc = $cambios['cabecera']['sucursal_id']['nuevo'] ?? null;
                $origSuc  = $cambios['cabecera']['sucursal_id']['original'] ?? null;

                if ($nuevoSuc !== '' && $nuevoSuc !== null) {
                    $fields[] = "EVENTO_SUCURSALID = ?";
                    $params[] = $nuevoSuc;

                    $rowN = $db->query("SELECT NOMBRE FROM ampar_cat_sucursales WHERE SUCURSAL_ID = ?", [$nuevoSuc]);
                    $nombN = $rowN ? $rowN[0]['NOMBRE'] : $nuevoSuc;

                    if ($origSuc !== '' && $origSuc !== null) {
                        $rowO = $db->query("SELECT NOMBRE FROM ampar_cat_sucursales WHERE SUCURSAL_ID = ?", [$origSuc]);
                        $nombO = $rowO ? $rowO[0]['NOMBRE'] : $origSuc;
                    } else {
                        $nombO = 'Vacio';
                    }

                    $comentariosCab[] = "<strong>Sucursal</strong>: " . htmlentities($nombO) . " → " . htmlentities($nombN);
                }
            }

            // 2.5) Almacén (NUEVO)
            $cambioAlmacen = isset($cambios['cabecera']['almacen_id']);
            if ($cambioAlmacen) {
                $nuevoAlm = $cambios['cabecera']['almacen_id']['nuevo'] ?? null;

                if ($nuevoAlm !== '' && $nuevoAlm !== null) {
                    $fields[] = "EVENTO_ALMACENID = ?";
                    $params[] = $nuevoAlm;
                    $comentariosCab[] = "<strong>Almacén</strong> modificado al ID: " . $nuevoAlm;
                }
            }

            if (!empty($fields)) {
                $cleanFields = [];
                $cleanParams = [];
                $pIndex = 0;
                foreach ($fields as $f) {
                    $numQ = substr_count($f, '?');
                    if ($numQ == 1) {
                        $val = $params[$pIndex];
                        if ($val === null) {
                            $cleanFields[] = str_replace("= ?", "= NULL", $f);
                        } else {
                            $cleanFields[] = $f;
                            $cleanParams[] = $val;
                        }
                        $pIndex++;
                    } else {
                        // Just in case there are fields without ? or with multiple ?
                        $cleanFields[] = $f;
                    }
                }
                $sqlUpd = "UPDATE AMPAR_HIS_EVENTOS SET " . implode(", ", $cleanFields) . " WHERE EVENTO_ID = ?";
                $cleanParams[] = $eventoid;
                $db->execute($sqlUpd, $cleanParams);

                $comentario = 'EDICIÓN DE EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong> (Sucursal: ' . $ev['SUCURSAL_NOMBRE'] . ') Campos: ' . implode("; ", $comentariosCab);

                // Evitar pasar null explicitamente al execute para la Bitácora
                $sucursal_ms = $ev['EVENTO_SUCURSALID'] ?? null;
                $sqlBitInsert = "INSERT INTO AMPAR_BITACORA
                    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_EVENTOID)
                    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, " . ($sucursal_ms === null ? "NULL" : "?") . ", ?)";

                $bitParams = [
                    $comentario,
                    $usersesion['USUARIO_ID'],
                    $usersesion['USUARIO_CORREO'],
                    $ipuser
                ];
                if ($sucursal_ms !== null) {
                    $bitParams[] = $sucursal_ms;
                }
                $bitParams[] = $eventoid;

                $db->execute($sqlBitInsert, $bitParams);
            }

            // 7) Maletas
            if (!empty($cambios['maletas'])) {
                foreach ($cambios['maletas'] as $m) {
                    if ($m['tipo'] === 'agregado') {
                        $db->execute("
                            INSERT INTO AMPAR_HIS_EVENTOSMALETAS (EVENTOMALETA_EVENTOID, EVENTOMALETA_MALETAID)
                            VALUES (?, ?)
                        ", [$eventoid, $m['almacen_id']]);
                        $coment = 'SE AGREGÓ MALETA <b>' . htmlentities($m['label']) . '</b> A EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong>';
                        $almIdBit = isset($m['almacen_id']) && (int)$m['almacen_id'] > 0 ? (int)$m['almacen_id'] : null;
                        $stkIdBit = isset($m['almacen_id']) && (int)$m['almacen_id'] < 0 ? abs((int)$m['almacen_id']) : null;
                        $db->execute($sqlBit, [
                            $coment,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $ev['EVENTO_SUCURSALID'] ?? null,
                            $almIdBit,
                            $stkIdBit,
                            $eventoid
                        ]);
                    } elseif ($m['tipo'] === 'eliminado') {
                        $db->execute("DELETE FROM AMPAR_HIS_EVENTOSMALETAS WHERE EVENTOMALETA_ID = ?", [$m['eventomaleta_id']]);
                        $coment = 'SE ELIMINÓ MALETA <b>' . htmlentities($m['label']) . '</b> DE EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong>';
                        $almIdBit = isset($m['almacen_id']) && (int)$m['almacen_id'] > 0 ? (int)$m['almacen_id'] : null;
                        $stkIdBit = isset($m['almacen_id']) && (int)$m['almacen_id'] < 0 ? abs((int)$m['almacen_id']) : null;
                        $db->execute($sqlBit, [
                            $coment,
                            $usersesion['USUARIO_ID'],
                            $usersesion['USUARIO_CORREO'],
                            $ipuser,
                            $ev['EVENTO_SUCURSALID'] ?? null,
                            $almIdBit,
                            $stkIdBit,
                            $eventoid
                        ]);
                    }
                }
            }

            // 8) Proveedores
            if (!empty($cambios['proveedores'])) {
                foreach ($cambios['proveedores'] as $p) {
                    switch ($p['tipo']) {
                        case 'agregado':
                            $db->execute("
                                INSERT INTO AMPAR_HIS_EVENTOPROVEEDOR
                                (EVENTOPROVEEDOR_EVENTOID, EVENTOPROVEEDOR_PROVEEDORID, EVENTOPROVEEDOR_OBSERVACIONES)
                                VALUES (?, ?, ?)
                            ", [$eventoid, $p['proveedor_id'], $p['obs'] ?? '']);
                            $coment = 'SE AGREGÓ PROVEEDOR <b>' . htmlentities($p['nombre'] ?? '') . '</b> A EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong>';
                            $db->execute($sqlBit, [
                                $coment,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $ev['EVENTO_SUCURSALID'] ?? null,
                                null,
                                null,
                                $eventoid
                            ]);
                            // preparar correo proveedor agregado
                            if (!empty($p['proveedor_id'])) {
                                $rowP = $db->query("SELECT NOMBRE, EMAIL FROM PROVEEDORES WHERE PROVEEDOR_ID = ?", [(int)$p['proveedor_id']]);
                                if ($rowP && !empty($rowP[0]['EMAIL'])) {
                                    // Armar evMail para plantilla
                                    $evMail = $ev;
                                    // Maletas para correo
                                    $maletasRows = $db->query("
                                        SELECT 
                                            em.EVENTOMALETA_MALETAID AS MID,
                                            COALESCE(a.ALMACEN_FOLIO, EC_EQ.FOLIO, ST_EQ.STOCK_FOLIO) || ' - ' || 
                                            COALESCE(a.ALMACEN_NOMBRE, ART_EC.NOMBRE || ' (EQUIPO CAPITAL)', ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS LABEL
                                        FROM AMPAR_HIS_EVENTOSMALETAS em
                                        LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = em.EVENTOMALETA_MALETAID
                                        LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                                        LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
                                        LEFT JOIN AMPAR_EQUIPOCAPITAL EC_EQ ON EC_EQ.EQUIPOCAPITAL_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                                        LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC_EQ.ARTICULO_ID
                                        WHERE em.EVENTOMALETA_EVENTOID = ?
                                    ", [$eventoid]);
                                    $evMail['_MALETAS'] = array_map(fn($x) => $x['LABEL'], array_filter($maletasRows ?: [], fn($x) => (int)$x['MID'] > 0));
                                    $evMail['_EQUIPOCAPITAL'] = array_map(fn($x) => $x['LABEL'], array_filter($maletasRows ?: [], fn($x) => (int)$x['MID'] < 0));

                                    $titulo = "Invitación como Proveedor – Evento " . $evMail['EVENTO_FOLIO'];
                                    $intro  = "Has sido agregado(a) como <b>Proveedor</b> al siguiente evento.";
                                    $html   = $this->renderMailEvento($evMail, $titulo, $intro, "Ver detalles del evento", $ctaUrl);

                                    $emailsToSend[] = [
                                        'to' => $rowP[0]['EMAIL'],
                                        'name' => $rowP[0]['NOMBRE'] ?? 'Proveedor',
                                        'subject' => $titulo,
                                        'html' => $html
                                    ];
                                }
                            }
                            break;

                        case 'modificado':
                            $nuevoObs = $p['cambios']['obs']['nuevo'] ?? '';
                            $db->execute("
                                UPDATE AMPAR_HIS_EVENTOPROVEEDOR
                                SET EVENTOPROVEEDOR_OBSERVACIONES = ?
                                WHERE EVENTOPROVEEDOR_ID = ?
                            ", [$nuevoObs, $p['eventoproveedor_id']]);
                            $coment = 'SE EDITÓ PROVEEDOR <b>' . htmlentities($p['nombre'] ?? '') . '</b> EN EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong> Campos: OBS: ' . htmlentities($p['cambios']['obs']['original'] ?? '') . ' → ' . htmlentities($nuevoObs);
                            $db->execute($sqlBit, [
                                $coment,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $ev['EVENTO_SUCURSALID'] ?? null,
                                null,
                                null,
                                $eventoid
                            ]);
                            break;

                        case 'eliminado':
                            $db->execute("DELETE FROM AMPAR_HIS_EVENTOPROVEEDOR WHERE EVENTOPROVEEDOR_ID = ?", [$p['eventoproveedor_id']]);
                            $coment = 'SE ELIMINÓ PROVEEDOR <b>' . htmlentities($p['nombre'] ?? '') . '</b> DE EVENTO FOLIO:<strong>' . $ev['EVENTO_FOLIO'] . '</strong>';
                            $db->execute($sqlBit, [
                                $coment,
                                $usersesion['USUARIO_ID'],
                                $usersesion['USUARIO_CORREO'],
                                $ipuser,
                                $ev['EVENTO_SUCURSALID'] ?? null,
                                null,
                                null,
                                $eventoid
                            ]);
                            break;
                    }
                }
            }

            // ===== Preparar correos por cambio de especialista/chofer =====
            // Base evMail para plantilla
            $evMailBase = $ev;
            $maletasRows = $db->query("
                SELECT 
                    em.EVENTOMALETA_MALETAID AS MID,
                    COALESCE(a.ALMACEN_FOLIO, EC_EQ.FOLIO, ST_EQ.STOCK_FOLIO) || ' - ' || 
                    COALESCE(a.ALMACEN_NOMBRE, ART_EC.NOMBRE || ' (EQUIPO CAPITAL)', ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS LABEL
                FROM AMPAR_HIS_EVENTOSMALETAS em
                LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = em.EVENTOMALETA_MALETAID
                LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
                LEFT JOIN AMPAR_EQUIPOCAPITAL EC_EQ ON EC_EQ.EQUIPOCAPITAL_ID = -em.EVENTOMALETA_MALETAID AND em.EVENTOMALETA_MALETAID < 0
                LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC_EQ.ARTICULO_ID
                WHERE em.EVENTOMALETA_EVENTOID = ?
            ", [$eventoid]);
            $evMailBase['_MALETAS'] = array_map(fn($x) => $x['LABEL'], array_filter($maletasRows ?: [], fn($x) => (int)$x['MID'] > 0));
            $evMailBase['_EQUIPOCAPITAL'] = array_map(fn($x) => $x['LABEL'], array_filter($maletasRows ?: [], fn($x) => (int)$x['MID'] < 0));

            $notificationsToSend = [];

            // ESPECIALISTA: si cambió
            if ($cambioEsp) {
                if ($nuevoEspId !== null && $nuevoEspId != $origEspId) {
                    $row = $db->query("SELECT USUARIO_CORREO, USUARIO_NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?", [$nuevoEspId]);
                    if ($row && !empty($row[0]['USUARIO_CORREO'])) {
                        $titulo = "Asignación de Especialista – Evento " . $evMailBase['EVENTO_FOLIO'];
                        $intro  = "Has sido asignado(a) como <b>Especialista</b> al siguiente evento.";
                        $html   = $this->renderMailEvento($evMailBase, $titulo, $intro, "Ver y confirmar", $ctaUrl);
                        $emailsToSend[] = [
                            'to' => $row[0]['USUARIO_CORREO'],
                            'name' => $row[0]['USUARIO_NOMBRE'],
                            'subject' => $titulo,
                            'html' => $html
                        ];
                        $notificationsToSend[] = [
                            'usuarioid' => $nuevoEspId,
                            'eventoid'  => $eventoid,
                            'tipo'      => 'ESPECIALISTA',
                            'titulo'    => 'Asignación de Especialista',
                            'mensaje'   => 'Has sido asignado(a) como Especialista al evento ' . $evMailBase['EVENTO_FOLIO']
                        ];
                    }
                } elseif ($nuevoEspId === null) {
                    // invitación masiva si quedó sin especialista
                    $destEsp = $db->query("
                        SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
                        FROM AMPAR_CAT_USUARIOSSUCURSALES S
                        JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
                        JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
                        WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
                        AND T.USUARIOSTIPOPERMISOS_TIPOID = 2
                        ORDER BY U.USUARIO_NOMBRE
                    ", [$ev['SUCURSAL_ID'] ?? $ev['EVENTO_SUCURSALID']]);
                    if ($destEsp) {
                        $titulo = "Invitación – Evento " . $evMailBase['EVENTO_FOLIO'] . " (" . $ev['TIPOEVENTO_NOMBRE'] . ")";
                        $intro  = "Se ha actualizado el evento y estás invitado(a) como <b>Especialista</b>. Si te interesa participar, ingresa al portal para confirmar tu asistencia.";
                        $html   = $this->renderMailEvento($evMailBase, $titulo, $intro, "Entrar y postularme", $ctaUrl);
                        foreach ($destEsp as $d) {
                            if (!empty($d['USUARIO_CORREO'])) {
                                $emailsToSend[] = [
                                    'to' => $d['USUARIO_CORREO'],
                                    'name' => $d['USUARIO_NOMBRE'],
                                    'subject' => $titulo,
                                    'html' => $html
                                ];
                            }
                            $notificationsToSend[] = [
                                'usuarioid' => $d['USUARIO_ID'],
                                'eventoid'  => $eventoid,
                                'tipo'      => 'ESPECIALISTA',
                                'titulo'    => 'Invitación a evento ' . $evMailBase['EVENTO_FOLIO'],
                                'mensaje'   => 'Se ha actualizado un evento que requiere Especialista. Acepta o rechaza la invitación.'
                            ];
                        }
                    }
                }
            }

            // CHOFER: si cambió
            if ($cambioCho) {
                if ($nuevoChoId !== null && $nuevoChoId != $origChoId) {
                    $row = $db->query("SELECT USUARIO_CORREO, USUARIO_NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?", [$nuevoChoId]);
                    if ($row && !empty($row[0]['USUARIO_CORREO'])) {
                        $titulo = "Asignación de Chofer – Evento " . $evMailBase['EVENTO_FOLIO'];
                        $intro  = "Has sido asignado(a) como <b>Chofer</b> al siguiente evento.";
                        $html   = $this->renderMailEvento($evMailBase, $titulo, $intro, "Ver y confirmar", $ctaUrl);
                        $emailsToSend[] = [
                            'to' => $row[0]['USUARIO_CORREO'],
                            'name' => $row[0]['USUARIO_NOMBRE'],
                            'subject' => $titulo,
                            'html' => $html
                        ];
                        $notificationsToSend[] = [
                            'usuarioid' => $nuevoChoId,
                            'eventoid'  => $eventoid,
                            'tipo'      => 'CHOFER',
                            'titulo'    => 'Asignación de Chofer',
                            'mensaje'   => 'Has sido asignado(a) como Chofer al evento ' . $evMailBase['EVENTO_FOLIO']
                        ];
                    }
                } elseif ($nuevoChoId === null) {
                    // invitación masiva si quedó sin chofer
                    $destCho = $db->query("
                        SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
                        FROM AMPAR_CAT_USUARIOSSUCURSALES S
                        JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
                        JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
                        WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
                        AND T.USUARIOSTIPOPERMISOS_TIPOID = 3
                        ORDER BY U.USUARIO_NOMBRE
                    ", [$ev['SUCURSAL_ID'] ?? $ev['EVENTO_SUCURSALID']]);
                    if ($destCho) {
                        $titulo = "Invitación – Evento " . $evMailBase['EVENTO_FOLIO'] . " (" . $ev['TIPOEVENTO_NOMBRE'] . ")";
                        $intro  = "Se ha actualizado el evento y estás invitado(a) como <b>Chofer</b>. Si te interesa participar, ingresa al portal para confirmar tu asistencia.";
                        $html   = $this->renderMailEvento($evMailBase, $titulo, $intro, "Entrar y postularme", $ctaUrl);
                        foreach ($destCho as $d) {
                            if (!empty($d['USUARIO_CORREO'])) {
                                $emailsToSend[] = [
                                    'to' => $d['USUARIO_CORREO'],
                                    'name' => $d['USUARIO_NOMBRE'],
                                    'subject' => $titulo,
                                    'html' => $html
                                ];
                            }
                            $notificationsToSend[] = [
                                'usuarioid' => $d['USUARIO_ID'],
                                'eventoid'  => $eventoid,
                                'tipo'      => 'CHOFER',
                                'titulo'    => 'Invitación a evento ' . $evMailBase['EVENTO_FOLIO'],
                                'mensaje'   => 'Se ha actualizado un evento que requiere Chofer. Acepta o rechaza la invitación.'
                            ];
                        }
                    }
                }
            }

            // 9) Commit y close
            if ($didBegin && method_exists($db, 'commit')) {
                $db->commit();
            }
            $db->close();

            // 10) Enviar correos (fuera de la BD)
            foreach ($emailsToSend as $m) {
                try {
                    $this->enviarMail($m['to'], $m['name'], $m['subject'], $m['html']);
                } catch (Throwable $e) {
                    // log opcional
                }
            }

            // 11) Insertar notificaciones internas + WhatsApp
            foreach ($notificationsToSend as $n) {
                try {
                    notificaciones::crear($n['usuarioid'], $n['eventoid'], $n['tipo'], $n['titulo'], $n['mensaje']);
                } catch (Throwable $e) {
                }
                
                // 📱 WhatsApp al usuario asignado
                try {
                    $telUsuario = whatsapp::getTelefonoUsuario((int)$n['usuarioid']);
                    if ($telUsuario) {
                        $folio  = $evMailBase['EVENTO_FOLIO'] ?? '';
                        $fechaProgramada = !empty($evMailBase['EVENTO_FECHAI']) ? date('d/m/Y H:i', strtotime($evMailBase['EVENTO_FECHAI'])) : 'N/A';
                        $rol    = ($n['tipo'] === 'ESPECIALISTA') ? '🔬 Especialista' : '🚗 Chofer';
                        $msgWA  = "{$rol} — *Asignación a Evento*\n";
                        $msgWA .= "Folio: *{$folio}*\n";
                        $msgWA .= "Fecha Prog.: *{$fechaProgramada}*\n";
                        $msgWA .= $n['mensaje'] . "\n";
                        $msgWA .= "Ingresa al sistema para aceptar o rechazar:\n" . $ctaUrl;
                        whatsapp::enviar($telUsuario, $msgWA);
                    }
                } catch (Throwable $eWA) {
                    error_log("[WhatsApp] Error notificación asignación: " . $eWA->getMessage());
                }
            }

            echo ""; // éxito

        } catch (Exception $e) {
            if ($didBegin && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            $db->close();
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    function updatestatusevento($id, $status)
    {
        $usersesion = $_SESSION['ampar']['usuario'];
        $ipuser = bitacora::getip();
        $db = new FirebirdConnection();

        $banderabitacora = 0;
        $sqlBit = "INSERT INTO AMPAR_BITACORA
                        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
                        VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)";

        //Si es iniciar hay que cambiar los status de las maletas del evento
        if ($status == 15) {
            //CONSULTAR SI HAY EVENTOS INICIADOS O EN REMISION QUE TENGAN ESAS MALETAS
            $sql0 = "
                SELECT COUNT(*) CONTADOR FROM AMPAR_HIS_EVENTOS
                LEFT JOIN  AMPAR_HIS_EVENTOSMALETAS ON EVENTOMALETA_EVENTOID = EVENTO_ID
                WHERE EVENTOMALETA_MALETAID IN
                (
                    SELECT EVENTOMALETA_MALETAID FROM AMPAR_HIS_EVENTOS
                    LEFT JOIN AMPAR_HIS_EVENTOSMALETAS ON EVENTOMALETA_EVENTOID = EVENTO_ID
                    WHERE EVENTO_ID = " . $id . "
                ) 
                AND (EVENTO_STATUSGENERAL = 15 OR EVENTO_STATUSGENERAL = 16)
                AND EVENTO_ID <> " . $id . "
            ";
            $result0 = $db->query($sql0);
            if ($result0[0]['CONTADOR'] > 0) {
                echo "No se puede inciar el evento ya que una o más maletas se encuentran Iniciadas o en Remisión en otro Evento";
            } else {
                $banderabitacora = 1;
                $sql = "
                    UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = " . $status . " WHERE EVENTO_ID = " . $id . "
                ";
                $db->execute($sql);
            }
        } else if ($status == 5 || $status == 3 || $status == 29) {
            $banderabitacora = 1;
            $sql = "
                UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = " . $status . " WHERE EVENTO_ID = " . $id . "
            ";
            $db->execute($sql);
        }
        if ($banderabitacora == 1) {
            //Guarda en bitácora
            $queryconsultagral = "
                SELECT
                    E.EVENTO_ID, E.EVENTO_FOLIO,CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
                    E.EVENTO_FECHAI,E.EVENTO_FECHAF, DATEDIFF(HOUR FROM EVENTO_FECHAI TO EVENTO_FECHAF) AS DURACION_HORAS, E.EVENTO_STATUSGENERAL, S.STATUS_NOMBRE STATUS_NOMBREGRAL, 
                    E.EVENTO_SUCURSALID, SU.NOMBRE SUCURSAL_NOMBRE, 
                    E.EVENTO_CLIENTEID, C.NOMBRE CLIENTE_NOMBRE, E.EVENTO_HOSPITALID, H.HOSPITAL_NOMBRE, 
                    E.EVENTO_TIPOEVENTO, TE.TIPOEVENTO_NOMBRE, TEG.TIPOEVENTOGRUPO_NOMBRE,
                    E.EVENTO_DOCTORINTERVENCIONISTAID, MI.MEDICO_NOMBRE MEDICO_INTERVENCIONISTA, E.EVENTO_DOCTORREFERIDORID, MR.MEDICO_NOMBRE MEDICO_REFERIDOR,
                    E.EVENTO_ESPECIALISTAID, UES.USUARIO_NOMBRE ESPECIALISTA_NOMBRE, E.EVENTO_ESPECIALISTAIDSTATUS, EE.STATUS_NOMBRE STATUSESPECIALISTA, E.EVENTO_CHOFERID, UCH.USUARIO_NOMBRE CHOFER_NOMBRE, E.EVENTO_CHOFERIDSTATUS, EC.STATUS_NOMBRE STATUSCHOFER,
                    E.EVENTO_FECHACREACION
                FROM AMPAR_HIS_EVENTOS E
                LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = E.EVENTO_SUCURSALID
                LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = EVENTO_STATUSGENERAL
                LEFT JOIN AMPAR_CAT_HOSPITALES H ON HOSPITAL_ID = EVENTO_HOSPITALID
                LEFT JOIN CLIENTES C ON CLIENTE_ID = EVENTO_CLIENTEID
                LEFT JOIN AMPAR_CAT_MEDICOS MI ON MI.MEDICO_ID = E.EVENTO_DOCTORINTERVENCIONISTAID
                LEFT JOIN AMPAR_CAT_MEDICOS MR ON MR.MEDICO_ID = E.EVENTO_DOCTORREFERIDORID
                LEFT JOIN AMPAR_CAT_TIPOEVENTO TE  ON TIPOEVENTO_ID = EVENTO_TIPOEVENTO
                LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO TESG ON TESG.TIPOEVENTOSUBGRUPO_ID = TE.TIPOEVENTO_SUBGRUPOID
                LEFT JOIN AMPAR_CAT_TIPOEVENTOGRUPO TEG ON TEG.TIPOEVENTOGRUPO_ID = TESG.TIPOEVENTOSUBGRUPO_GRUPOID
                LEFT JOIN AMPAR_CAT_USUARIOS UES  ON UES.USUARIO_ID = EVENTO_ESPECIALISTAID
                LEFT JOIN AMPAR_CONF_STATUS EE ON EE.STATUS_ID = EVENTO_ESPECIALISTAIDSTATUS
                LEFT JOIN AMPAR_CAT_USUARIOS UCH  ON UCH.USUARIO_ID = EVENTO_CHOFERID
                LEFT JOIN AMPAR_CONF_STATUS EC ON EC.STATUS_ID = EVENTO_CHOFERIDSTATUS
                WHERE EVENTO_ID = " . $id . "
            ";
            $infoevento = $db->query($queryconsultagral);
            $comentarioGeneral = 'CAMBIO DE STATUS A ' . $infoevento[0]['STATUS_NOMBREGRAL'] . ' DE SOLICITUD DE EVENTO FOLIO:<strong>' . $infoevento[0]['EVENTO_FOLIO'] . '</strong> (Sucursal: ' . $infoevento[0]['SUCURSAL_NOMBRE'] . ') Campos:' . bitacora::printarray($infoevento[0]);
            $db->execute($sqlBit, [
                $comentarioGeneral,
                $usersesion['USUARIO_ID'],
                $usersesion['USUARIO_CORREO'],
                $ipuser,
                $infoevento[0]['EVENTO_SUCURSALID'] ?? null,
                null,
                null,
                $infoevento[0]['EVENTO_ID'] ?? null
            ]);
        }

        // 📱 Notificaciones WhatsApp según el status
        if ($banderabitacora == 1 && !empty($infoevento)) {
            $ev = $infoevento[0];
            try {
                // Obtener nombre del almacén
                $almacenStr = 'N/A';
                try {
                    $resAlm = $db->query("
                        SELECT FIRST 1 a.ALMACEN_NOMBRE AS LABEL
                        FROM AMPAR_HIS_EVENTOS e
                        JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = e.EVENTO_ALMACENID
                        WHERE e.EVENTO_ID = ?
                    ", [$id]);
                    if (!empty($resAlm)) {
                        $almacenStr = $resAlm[0]['LABEL'];
                    }
                } catch(Throwable $e) {}

                if (in_array((int)$status, [3, 5, 29])) {
                    $etiquetas = [3 => '✅ Finalizado', 5 => '❌ Cancelado', 29 => '📥 En Recepción'];
                    $emoji = $etiquetas[(int)$status];
                    $msgEv  = "$emoji *Evento {$ev['EVENTO_FOLIO']}*\n";
                    $msgEv .= "Status: *{$ev['STATUS_NOMBREGRAL']}*\n";
                    $msgEv .= "Almacén: " . $almacenStr . "\n";
                    $msgEv .= "Fecha: " . (!empty($ev['EVENTO_FECHAI']) ? date('d/m/Y', strtotime($ev['EVENTO_FECHAI'])) : '');
                    if (!empty($ev['EVENTO_ESPECIALISTAID'])) {
                        $telEsp = whatsapp::getTelefonoUsuario((int)$ev['EVENTO_ESPECIALISTAID']);
                        if ($telEsp) whatsapp::enviar($telEsp, $msgEv);
                    }
                    if (!empty($ev['EVENTO_CHOFERID'])) {
                        $telCho = whatsapp::getTelefonoUsuario((int)$ev['EVENTO_CHOFERID']);
                        if ($telCho) whatsapp::enviar($telCho, $msgEv);
                    }
                    if ((int)$status === 29) {
                        $telAdmins = whatsapp::getTelefonosAdmins();
                        if (!empty($telAdmins)) {
                            $msgAdmin = "📥 *Evento en Recepción*\nFolio: *{$ev['EVENTO_FOLIO']}*\nAlmacén: " . $almacenStr . "\nRequiere atención.";
                            whatsapp::enviarMultiple($telAdmins, $msgAdmin);
                        }
                    }
                }
                if ((int)$status === 15) {
                    $telAdmins = whatsapp::getTelefonosAdmins();
                    if (!empty($telAdmins)) {
                        $msgAdmin = "🚀 *Evento Iniciado*\nFolio: *{$ev['EVENTO_FOLIO']}*\nAlmacén: " . $almacenStr . "\nEspecialista: " . ($ev['ESPECIALISTA_NOMBRE'] ?? 'N/A') . "\nChofer: " . ($ev['CHOFER_NOMBRE'] ?? 'N/A');
                        whatsapp::enviarMultiple($telAdmins, $msgAdmin);
                    }
                }
            } catch (Throwable $eWA) {
                error_log("[WhatsApp] Error en notificación evento: " . $eWA->getMessage());
            }
        }

        $db->close();
    }

    function confirmarespcho($eventoid, $tipoid, $usuarioid)
    {
        $db = new FirebirdConnection();
        if ($tipoid == 2) {
            $sql = "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ESPECIALISTAIDSTATUS = 19 WHERE EVENTO_ID = " . $eventoid . "";
            $db->execute($sql);
        } else if ($tipoid == 3) {
            $sql = "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_CHOFERIDSTATUS = 19 WHERE EVENTO_ID = " . $eventoid . "";
            $db->execute($sql);
        }
        $db->close();
    }

    function updateespcho($eventoid, $tipoid, $usuarioid, $status)
    {
        $db = new FirebirdConnection();
        if ($tipoid == 2) {
            $sql = "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ESPECIALISTAID = " . $usuarioid . ", EVENTO_ESPECIALISTAIDSTATUS = " . $status . " WHERE EVENTO_ID = " . $eventoid . "";
            $db->execute($sql);
        } else if ($tipoid == 3) {
            $sql = "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_CHOFERID = " . $usuarioid . ", EVENTO_CHOFERIDSTATUS = " . $status . " WHERE EVENTO_ID = " . $eventoid . "";
            $db->execute($sql);
        }
        $db->close();
    }

    function updatestatuseventoespecialista($id, $status)
    {
        $db = new FirebirdConnection();
        $sql = "
            UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ESPECIALISTAIDSTATUS = " . $status . " WHERE EVENTO_ID = " . $id . "
        ";
        $db->execute($sql);
        $db->close();
    }

    function getcatalogotipoeventos($subgrupoid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT TIPOEVENTO_ID ID, TIPOEVENTO_NOMBRE NOMBRE 
            FROM AMPAR_CAT_TIPOEVENTO
            WHERE TIPOEVENTO_SUBGRUPOID = " . $subgrupoid . "
            AND TIPOEVENTO_ACTIVO = 1
        ";
        $sql .= " order by TIPOEVENTO_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogotipoeventossubgrupo($grupoid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT TIPOEVENTOSUBGRUPO_ID ID, TIPOEVENTOSUBGRUPO_NOMBRE NOMBRE 
            FROM AMPAR_CAT_TIPOEVENTOSUBGRUPO
            WHERE TIPOEVENTOSUBGRUPO_GRUPOID = " . $grupoid . "
            AND TIPOEVENTOSUBGRUPO_ACTIVO = 1
        ";
        $sql .= " order by TIPOEVENTOSUBGRUPO_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogotipoeventosgrupo()
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT TIPOEVENTOGRUPO_ID ID, TIPOEVENTOGRUPO_NOMBRE NOMBRE FROM AMPAR_CAT_TIPOEVENTOGRUPO
            WHERE TIPOEVENTOGRUPO_ACTIVO = 1
        ";
        $sql .= " order by TIPOEVENTOGRUPO_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoclientes()
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT CLIENTE_ID ID, NOMBRE FROM CLIENTES
        ";
        $sql .= " order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogolugares($almacenid = "")
    {
        $db = new FirebirdConnection();
        $sql = "
        SELECT 
            HOSPITAL_ID AS ID,
            HOSPITAL_NOMBRE AS NOMBRE
        FROM AMPAR_CAT_HOSPITALES
    ";

        if ($almacenid !== "" && (int)$almacenid > 0) {
            $sql .= " WHERE HOSPITAL_ALMACEN = " . (int)$almacenid;
        }

        $sql .= " ORDER BY HOSPITAL_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogomedicos($almacenid = "", $subgrupoid = "", $grupoid = "")
    {
        $db = new FirebirdConnection();

        $sql = "
        SELECT DISTINCT
            M.MEDICO_ID AS ID,
            M.MEDICO_NOMBRE AS NOMBRE
        FROM AMPAR_CAT_MEDICOS M
        LEFT JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO SG
            ON SG.TIPOEVENTOSUBGRUPO_ID = M.MEDICO_TIPOEVENTOID
        WHERE 1=1
    ";

        if ($almacenid !== "" && (int)$almacenid > 0) {
            $sql .= " AND M.MEDICO_ALMACENID = " . (int)$almacenid;
        }

        if ($subgrupoid !== "" && (int)$subgrupoid > 0) {
            $sql .= " AND M.MEDICO_TIPOEVENTOID = " . (int)$subgrupoid;
        }

        if ($grupoid !== "" && (int)$grupoid > 0) {
            $sql .= " AND SG.TIPOEVENTOSUBGRUPO_GRUPOID = " . (int)$grupoid;
        }

        $sql .= " ORDER BY M.MEDICO_NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoespecialistas($sucursalid, $subgrupo)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT DISTINCT
                U.USUARIO_ID AS ID,
                U.USUARIO_NOMBRE AS NOMBRE
            FROM AMPAR_CAT_USUARIOS U
            INNER JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            WHERE T.USUARIOSTIPOPERMISOS_TIPOID = 2
              AND (
                  EXISTS (
                      SELECT 1 FROM AMPAR_CAT_USUARIOSSUCURSALES S 
                      WHERE S.USUARIOSSUCURSALES_USUARIOID = U.USUARIO_ID 
                      AND S.USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "
                  )
                  OR EXISTS (
                      SELECT 1 FROM AMPAR_CAT_USUARIOSALMACENES UA
                      INNER JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = UA.USUARIOSALMACENES_ALMACENID
                      WHERE UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                      AND A.ALMACEN_SUCURSAL_MS = " . $sucursalid . "
                  )
              )
            ORDER BY 
                U.USUARIO_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogochoferes($sucursalid)
    {
        $db = new FirebirdConnection();
        $sql = "
            SELECT DISTINCT
                U.USUARIO_ID AS ID,
                U.USUARIO_NOMBRE AS NOMBRE
            FROM AMPAR_CAT_USUARIOS U
            INNER JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            WHERE T.USUARIOSTIPOPERMISOS_TIPOID = 3
              AND (
                  EXISTS (
                      SELECT 1 FROM AMPAR_CAT_USUARIOSSUCURSALES S 
                      WHERE S.USUARIOSSUCURSALES_USUARIOID = U.USUARIO_ID 
                      AND S.USUARIOSSUCURSALES_SUCURSALID = " . $sucursalid . "
                  )
                  OR EXISTS (
                      SELECT 1 FROM AMPAR_CAT_USUARIOSALMACENES UA
                      INNER JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = UA.USUARIOSALMACENES_ALMACENID
                      WHERE UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                      AND A.ALMACEN_SUCURSAL_MS = " . $sucursalid . "
                  )
              )
            ORDER BY 
                U.USUARIO_NOMBRE
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoproveedores()
    {
        $db = new FirebirdConnection();
        $sql = "
             SELECT 
             PROVEEDOR_ID ID, NOMBRE, EMAIL
                from proveedores 
        ";
        $sql .= " order by NOMBRE";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getcatalogoproveedoresbyevento($eventoid)
    {
        $db = new FirebirdConnection();
        $sql = "
             SELECT 
                PROVEEDOR_ID ID, NOMBRE, EMAIL 
            from AMPAR_HIS_EVENTOPROVEEDOR EP
                LEFT JOIN PROVEEDORES P ON P.PROVEEDOR_ID = EP.EVENTOPROVEEDOR_PROVEEDORID 
                WHERE
                EVENTOPROVEEDOR_EVENTOID = " . $eventoid . "
        ";
        $sql .= " order by P.NOMBRE";
        $result = $db->query($sql);
        $db->close();

        // Validar que $result sea un array, si no lo es retornar array vacío
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    function getcatalogoeventos($sucalmacenid = "")
    {
        $eventos = [];
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                e.EVENTO_ID ID, 
                e.EVENTO_CONCEPTO NOMBRE, 
                e.EVENTO_FOLIO FOLIO,
                e.EVENTO_STATUSGENERAL STATUS_ID,
                s.STATUS_NOMBRE STATUS_NOMBRE
            FROM AMPAR_HIS_EVENTOs e
            LEFT JOIN AMPAR_CONF_STATUS s ON s.STATUS_ID = e.EVENTO_STATUSGENERAL
            WHERE 1=1
        ";
        // Filtro comentado - puedes activarlo si solo necesitas eventos con status específico
        // where EVENTO_STATUSGENERAL = 15

        if ($sucalmacenid <> "") {
            $sql .= " AND EVENTO_SUCURSALID = " . $sucalmacenid;
        }

        $sql .= " ORDER BY e.EVENTO_FOLIO DESC";

        $result = $db->query($sql);
        $db->close();

        // Validar que $result sea un array
        if (!is_array($result) || empty($result)) {
            return $eventos; // Retornar array vacío si no hay resultados
        }

        foreach ($result as $row) {
            /*
            // Verificar si el campo es un BLOB
            if (!empty($row['NOMBRE'])) {
                $blob_id = $row['NOMBRE'];
                $blob_handle = ibase_blob_open($blob_id);
                $concepto = ibase_blob_get($blob_handle, 8192); // Leer hasta 8 KB
                ibase_blob_close($blob_handle);
            } else {
                $concepto = ''; // En caso de que el campo esté vacío
            }
            */
            $concepto = $row['NOMBRE'];
            // Agregar evento al array
            $eventos[] = [
                'ID' => $row['ID'],
                'FOLIO' => $row['FOLIO'],
                'NOMBRE' => trim($concepto),
                'STATUS_ID' => $row['STATUS_ID'],
                'STATUS_NOMBRE' => trim($row['STATUS_NOMBRE'] ?? ''),
            ];
        }
        return $eventos;
    }

    function getarticulosbyevento($eventoid)
    {
        $db = new FirebirdConnection();
        $sql1 = "
            SELECT 
                st.STOCK_ID ID, st.STOCK_FOLIO FOLIO, a.ARTICULO_ID, a.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO, 
                CAST(em.EVENTOMALETA_MALETAID AS INTEGER) AS MALETA_ID,
                CAST(coalesce(ma.ALMACEN_NOMBRE, ar_eq.NOMBRE || ' (EQUIPO CAPITAL)') AS VARCHAR(150)) AS MALETA_NOMBRE,
                CAST(coalesce(ma.ALMACEN_FOLIO, st_eq.STOCK_FOLIO) AS VARCHAR(50)) AS MALETA_FOLIO,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = e.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = e.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  = e.EVENTO_HOSPITALID),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL
                    FROM AMPAR_CAT_ARTPRECIO AP
                    WHERE AP.ARTPRECIO_ARTICULOID = a.ARTICULO_ID
                        AND AP.ARTPRECIO_CLIENTEID  IS NULL)
                ) AS TOTAL
            FROM AMPAR_HIS_EVENTOS e
            LEFT JOIN AMPAR_HIS_EVENTOSMALETAS em ON em.EVENTOMALETA_EVENTOID = e.EVENTO_ID
            LEFT JOIN AMPAR_HIS_ALMACEN ma ON ma.ALMACEN_ID = em.EVENTOMALETA_MALETAID
            LEFT JOIN AMPAR_HIS_STOCK st_eq ON st_eq.STOCK_ID = -em.EVENTOMALETA_MALETAID
            LEFT JOIN ARTICULOS ar_eq ON ar_eq.ARTICULO_ID = st_eq.STOCK_ARTICULOID
            LEFT JOIN AMPAR_HIS_STOCK st ON (st.STOCK_ALMACENIDACTUAL = em.EVENTOMALETA_MALETAID OR st.STOCK_ID = -em.EVENTOMALETA_MALETAID)
            LEFT JOIN ARTICULOS a ON a.ARTICULO_ID = st.STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = a.ARTICULO_ID
            WHERE 
            st.STOCK_STOCKSTATUSID IN (1,2)
        ";

        if ($eventoid <> "") {
            $sql1 .= " AND e.EVENTO_ID = " . (int)$eventoid;
        }

        $sql = $sql1;
        $result = $db->query($sql);
        $db->close();

        // Validar que $result sea un array, si no lo es retornar array vacío
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    // === Helpers de URL/plantilla ===
    private function baseUrl()
    {
        // Ajusta a tu dominio/base
        // Ej: return $GLOBALS['global_site'] . '/ampar/';
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . $host . '/ampar/';
    }

    private function urlEvento($eventoid)
    {
        return 'https://erp.ampardemexico.com/gui/eventos.php';
    }

    // Info compacta del evento para mail (reutiliza tu query grande)
    private function infoEventoMail($db, $eventoid)
    {
        $row = $this->geteventobyid($eventoid);
        $row = is_array($row) && !empty($row) ? $row[0] : [];
        // Lista de maletas
        $maletas = $db->query("
            SELECT 
                COALESCE(a.ALMACEN_FOLIO, ST_EQ.STOCK_FOLIO) || ' - ' || 
                COALESCE(a.ALMACEN_NOMBRE, ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS LABEL
            FROM AMPAR_HIS_EVENTOSMALETAS em
            LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = em.EVENTOMALETA_MALETAID
            LEFT JOIN AMPAR_HIS_STOCK ST_EQ ON ST_EQ.STOCK_ID = -em.EVENTOMALETA_MALETAID
            LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = ST_EQ.STOCK_ARTICULOID
            WHERE em.EVENTOMALETA_EVENTOID = ?
        ", [$eventoid]);
        $row['_MALETAS'] = array_map(fn($x) => $x['LABEL'], $maletas ?: []);
        return $row;
    }

    // Render HTML del correo
    private function renderMailEvento(array $ev, string $titulo, string $intro, string $ctaText, string $ctaUrl)
    {
        $folio   = htmlentities($ev['EVENTO_FOLIO'] ?? '');
        $desc    = nl2br(htmlentities($ev['EVENTO_CONCEPTO'] ?? ''));
        $fechai  = htmlentities($ev['EVENTO_FECHAI'] ?? '');
        $fechaf  = htmlentities($ev['EVENTO_FECHAF'] ?? '');
        $tipo    = htmlentities($ev['TIPOEVENTO_NOMBRE'] ?? '');
        $lugar   = htmlentities($ev['HOSPITAL_NOMBRE'] ?? '');
        $suc     = htmlentities($ev['SUCURSAL_NOMBRE'] ?? '');
        $drRef   = htmlentities($ev['DOCTORREFERIDOR_NOMBRE'] ?? '');
        $drInt   = htmlentities($ev['DOCTORINTERVENCIONISTA_NOMBRE'] ?? '');
        $cliente = htmlentities($ev['NOMBRE'] ?? $ev['EVENTO_NOMBREPARTICULAR'] ?? '');
        $maletas = $ev['_MALETAS'] ?? [];
        $maletasHtml = empty($maletas) ? '<em>Sin maletas</em>' : '<ul style="margin:8px 0;">' . implode('', array_map(fn($m) => '<li>' . htmlentities($m) . '</li>', $maletas)) . '</ul>';
        $eqCap = $ev['_EQUIPOCAPITAL'] ?? [];
        $eqCapRow = !empty($eqCap) ? '<tr><td style="padding:6px 8px;background:#f5f5f5"><b>Equipo Capital</b></td><td style="padding:6px 8px"><ul style="margin:8px 0;">' . implode('', array_map(fn($m) => '<li>' . htmlentities($m) . '</li>', $eqCap)) . '</ul></td></tr>' : '';

        return '
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:640px">
        <h2 style="margin:0 0 8px">' . $titulo . '</h2>
        <p style="margin:0 0 12px">' . $intro . '</p>
        <table style="border-collapse:collapse;width:100%;font-size:14px">
            <tr><td style="padding:6px 8px;background:#f5f5f5;width:180px"><b>Folio</b></td><td style="padding:6px 8px">' . $folio . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Descripción</b></td><td style="padding:6px 8px">' . $desc . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Tipo</b></td><td style="padding:6px 8px">' . $tipo . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Lugar</b></td><td style="padding:6px 8px">' . $lugar . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Sucursal</b></td><td style="padding:6px 8px">' . $suc . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Fecha inicio</b></td><td style="padding:6px 8px">' . $fechai . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Fecha fin</b></td><td style="padding:6px 8px">' . $fechaf . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Cliente/Particular</b></td><td style="padding:6px 8px">' . $cliente . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Médico referidor</b></td><td style="padding:6px 8px">' . $drRef . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Médico intervencionista</b></td><td style="padding:6px 8px">' . $drInt . '</td></tr>
            <tr><td style="padding:6px 8px;background:#f5f5f5"><b>Maletas</b></td><td style="padding:6px 8px">' . $maletasHtml . '</td></tr>
            ' . $eqCapRow . '
        </table>
        <div style="margin:16px 0">
            <a href="' . $ctaUrl . '" style="display:inline-block;padding:10px 14px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px">' . $ctaText . '</a>
        </div>
        <p style="font-size:12px;color:#666;margin-top:8px">Si el botón no funciona, copia y pega esta URL: ' . $ctaUrl . '</p>
        </div>';
    }

    // === Destinatarios (con correo) ===
    private function destinatariosEspecialistas($sucursalid, $subgrupo)
    {
        $db = new FirebirdConnection();
        $rows = $db->query("
            SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
            FROM AMPAR_CAT_USUARIOSSUCURSALES S
            JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
            JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            JOIN AMPAR_CAT_USUARIOSSUBGRUPOS SG ON SG.USUARIOSSUBGRUPOS_USUARIOID = U.USUARIO_ID
            WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
            AND SG.USUARIOSSUBGRUPOS_SUBGRUPOID = ?
            AND T.USUARIOSTIPOPERMISOS_TIPOID = 2
            ORDER BY U.USUARIO_NOMBRE
        ", [$sucursalid, $subgrupo]);
        $db->close();
        return $rows ?: [];
    }

    private function destinatariosChoferes($sucursalid)
    {
        $db = new FirebirdConnection();
        $rows = $db->query("
            SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_CORREO
            FROM AMPAR_CAT_USUARIOSSUCURSALES S
            JOIN AMPAR_CAT_USUARIOS U ON U.USUARIO_ID = S.USUARIOSSUCURSALES_USUARIOID
            JOIN AMPAR_CAT_USUARIOSTIPOPERMISOS T ON T.USUARIOSTIPOPERMISOS_USUARIOID = U.USUARIO_ID
            WHERE S.USUARIOSSUCURSALES_SUCURSALID = ?
            AND T.USUARIOSTIPOPERMISOS_TIPOID = 3
            ORDER BY U.USUARIO_NOMBRE
        ", [$sucursalid]);
        $db->close();
        return $rows ?: [];
    }

    // Envío genérico
    private function enviarMail($to, $name, $subject, $html)
    {
        $mail = new mail();            // Tu clase
        return $mail->enviar($to, $name, $subject, $html);
    }

    // Correo a persona asignada (especialista o chofer)
    private function correoAsignacionRol($eventoid, $usuarioCorreo, $usuarioNombre, $rolTexto)
    {
        $db = new FirebirdConnection();
        $ev = $this->infoEventoMail($db, $eventoid);
        $db->close();

        $titulo = "Asignación de $rolTexto – Evento " . $ev['EVENTO_FOLIO'];
        $intro  = "Has sido asignado(a) como <b>$rolTexto</b> al siguiente evento.";
        $ctaUrl = $this->urlEvento($eventoid);
        $html   = $this->renderMailEvento($ev, $titulo, $intro, "Ver y confirmar", $ctaUrl);

        $this->enviarMail($usuarioCorreo, $usuarioNombre, $titulo, $html);
    }

    // Invitación masiva por rol (cuando NO se seleccionó nadie en nuevo)
    private function correoInvitacionRolMasiva($eventoid, $rol)
    {
        // $rol: 'especialista' | 'chofer'
        $db  = new FirebirdConnection();
        $ev  = $this->infoEventoMail($db, $eventoid);
        $suc = $ev['SUCURSAL_ID'] ?? $ev['EVENTO_SUCURSALID'] ?? null;
        $sub = $ev['TIPOEVENTOSUBGRUPO_ID'] ?? null;

        $destinatarios = ($rol === 'especialista')
            ? $this->destinatariosEspecialistas($suc, $sub)
            : $this->destinatariosChoferes($suc);

        $titulo = "Invitación – Evento " . $ev['EVENTO_FOLIO'] . " (" . $ev['TIPOEVENTO_NOMBRE'] . ")";
        $intro  = "Se ha creado un evento y estás invitado(a) como <b>" . ucfirst($rol) . "</b>. Si te interesa participar, por favor ingresa al portal y confirma tu asistencia.";
        $ctaUrl = $this->urlEvento($eventoid);
        $html   = $this->renderMailEvento($ev, $titulo, $intro, "Entrar y postularme", $ctaUrl);

        foreach ($destinatarios as $d) {
            if (!empty($d['USUARIO_CORREO'])) {
                $this->enviarMail($d['USUARIO_CORREO'], $d['USUARIO_NOMBRE'], $titulo, $html);
            }
        }
        $db->close();
    }

    // Invitación a proveedor(es) (nuevo y editar cuando se agregan)
    private function correoInvitacionProveedores($eventoid, array $proveedorIds)
    {
        if (empty($proveedorIds)) return;

        $db = new FirebirdConnection();
        $ev = $this->infoEventoMail($db, $eventoid);

        // Obtiene EMAIL/NOMBRE de proveedores
        $ph = implode(',', array_fill(0, count($proveedorIds), '?'));
        $provs = $db->query("SELECT PROVEEDOR_ID, NOMBRE, EMAIL FROM PROVEEDORES WHERE PROVEEDOR_ID IN ($ph)", $proveedorIds);

        $titulo = "Invitación como Proveedor – Evento " . $ev['EVENTO_FOLIO'];
        $intro  = "Has sido agregado(a) como <b>Proveedor</b> al siguiente evento.";
        $ctaUrl = $this->urlEvento($eventoid);
        $html   = $this->renderMailEvento($ev, $titulo, $intro, "Ver detalles del evento", $ctaUrl);

        foreach ($provs as $p) {
            if (!empty($p['EMAIL'])) {
                $this->enviarMail($p['EMAIL'], $p['NOMBRE'] ?? 'Proveedor', $titulo, $html);
            }
        }
        $db->close();
    }

    // Abre una transacción de forma segura (no truena si ya hay una activa)
    private function safeBegin($db)
    {
        $did = false;
        try {
            if (method_exists($db, 'beginTransaction')) {
                if (method_exists($db, 'inTransaction')) {
                    if (!$db->inTransaction()) {
                        $db->beginTransaction();
                        $did = true;
                    }
                } else {
                    try {
                        $db->beginTransaction();
                        $did = true;
                    } catch (Throwable $e) { /* Ya había una activa; ignorar */
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignorar: seguimos sin transacción explícita
        }
        return $did;
    }

    public function getRevflow($eventoid)
    {
        $db = new FirebirdConnection();
        $row = $db->query("SELECT FIRST 1 * FROM AMPAR_EVENTOS_REVFLOW WHERE EVENTO_ID = ?", [$eventoid]);
        $db->close();
        return ($row && isset($row[0])) ? $row[0] : null;
    }
}
