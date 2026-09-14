<?php

class rfid {

    function getescaneos(){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                h.ESCANEO_ID AS RFID_ID,
                h.ESCANEO_FOLIO AS RFID_FOLIO,
                h.ESCANEO_FECHA AS RFID_FECHA,
                u.USUARIO_NOMBRE AS RESPONSABLE_NOMBRE,
                a.ALMACEN_NOMBRE AS MALETA_NOMBRE
            FROM AMPAR_HIS_ESCANEO h
            LEFT JOIN AMPAR_CAT_USUARIOS u ON u.USUARIO_ID = h.ESCANEO_RESPONSABLEID
            LEFT JOIN AMPAR_HIS_ALMACEN a ON UPPER(TRIM(a.ALMACEN_FOLIO)) = UPPER(TRIM(h.ESCANEO_FOLIO))
            " . (($GLOBALS['isAdmin'] ?? false) ? "" : "WHERE h.ESCANEO_RESPONSABLEID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0)) . "
            ORDER BY h.ESCANEO_ID DESC
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function getescaneosbyid($rfidbd){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                h.ESCANEO_ID AS RFID_ID,
                h.ESCANEO_FOLIO AS RFID_FOLIO,
                h.ESCANEO_FECHA AS RFID_FECHA,
                h.ESCANEO_DETALLES AS DETALLES_JSON,
                u.USUARIO_NOMBRE AS RESPONSABLE_NOMBRE,
                a.ALMACEN_NOMBRE AS MALETA_NOMBRE
            FROM AMPAR_HIS_ESCANEO h
            LEFT JOIN AMPAR_CAT_USUARIOS u ON u.USUARIO_ID = h.ESCANEO_RESPONSABLEID
            LEFT JOIN AMPAR_HIS_ALMACEN a ON UPPER(TRIM(a.ALMACEN_FOLIO)) = UPPER(TRIM(h.ESCANEO_FOLIO))
            WHERE h.ESCANEO_ID = ".$rfidbd."
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function validacion($epcs){
        $db = new FirebirdConnection();
        
        // Buscar artículos por EPC directamente
        $epcs_str = implode("','", array_map('addslashes', $epcs));
        $sql_articulos = "
            SELECT STOCK_ID, STOCK_FOLIO, STOCK_TEMPETIQUETA, STOCK_ALMACENIDACTUAL 
            FROM AMPAR_HIS_STOCK
            WHERE STOCK_TEMPETIQUETA IN ('$epcs_str')
            AND STOCK_STOCKSTATUSID IN (1,2)
        ";
        $articulos = $db->query($sql_articulos);
        $db->close();

        //insertar el escaneo
        $lastid = $this->guardar_rfid();

        if ($articulos <> 0){
            $epcs_articulos = array_column($articulos, 'STOCK_TEMPETIQUETA');

            // Recorrer cada EPC escaneado
            foreach ($epcs as $epc) {
                if (in_array($epc, $epcs_articulos)) {
                    // Es un artículo escaneado
                    $stock = $this->buscar_fila($articulos, 'STOCK_TEMPETIQUETA', $epc);
                    $this->guardar_rfiddet($lastid, $epc, $stock['STOCK_ALMACENIDACTUAL'], $stock['STOCK_ID'], 'ART');
                } else {
                    // Etiqueta extra (no encontrada)
                    $this->guardar_rfiddet($lastid, $epc, null, null, 'EXTRA');
                }
            }

            echo json_encode(['status' => 'OK', 'rfid_id' => $lastid, 'articulos_encontrados' => count($articulos)]);
        } else {
            // No se encontró ningún artículo
            foreach ($epcs as $epc) {
                $this->guardar_rfiddet($lastid, $epc, null, null, 'EXTRA');
            }
            echo json_encode(['status' => 'OK', 'rfid_id' => $lastid, 'mensaje' => 'No se encontraron artículos con esos EPCs']);
        }
    }

    function buscar_valor($array, $campo_buscar, $valor, $campo_retorno) {
        foreach ($array as $fila) {
            if ($fila[$campo_buscar] === $valor) {
                return $fila[$campo_retorno];
            }
        }
        return null;
    }
    
    function buscar_fila($array, $campo, $valor) {
        foreach ($array as $fila) {
            if ($fila[$campo] === $valor) return $fila;
        }
        return null;
    }
    
    function guardar_rfid() {
        $db = new FirebirdConnection();
        $sql = "
            INSERT INTO AMPAR_RFID (RFID_FECHA, RFID_ESCANER)
            VALUES (CURRENT_TIMESTAMP,'TEST')
        ";
        $id_insertado = $db->executeconreturning($sql,'RFID_ID');
        $db->close();
        return $id_insertado;
    }

    function guardar_rfiddet($rfidid,$epc, $almacen_id, $stock_id, $tipo) {
        $db = new FirebirdConnection();
        $epc = addslashes($epc);
        $tipo = addslashes($tipo);
        $sql = "
            INSERT INTO AMPAR_RFIDDET 
                (
                    RFIDDET_RFID,
                    RFIDDET_EPC, 
                    RFIDDET_STOCKFOLIO, 
                    RFIDDET_ALMACENFOLIO, 
                    RFIDDET_TIPO
                )
                VALUES 
                (
                    ".$rfidid.",
                    '".$epc."', 
                    " . ($stock_id ?? 'NULL') . ", 
                    " . ($almacen_id ?? 'NULL') . ", 
                    '".$tipo."'
                )
        ";
        $db->execute($sql);
        $db->close();
    }
    
    function traspasoconsultastatusfolio($folio) {
        $folio = $this->normalizarFolio($folio);
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                TRASPASO_STATUS, 
                INVENTARIOSTATUS_NOMBRE,
                TRASPASODET_AALMACENID,
                TRASPASO_ID,
                TRASPASO_FOLIO
            FROM 
                AMPAR_HIS_TRASPASO t 
                LEFT JOIN AMPAR_INVENTARIOSTATUS s ON INVENTARIOSTATUS_ID = TRASPASO_STATUS 
                LEFT JOIN AMPAR_HIS_TRASPASODET ON TRASPASO_ID = TRASPASODET_TRASPASOID
            WHERE 
                TRIM(LEADING '0' FROM SUBSTRING(TRASPASO_FOLIO FROM 1 FOR POSITION('-' IN TRASPASO_FOLIO) - 1)) || 
                SUBSTRING(TRASPASO_FOLIO FROM POSITION('-' IN TRASPASO_FOLIO)) = '".$folio."'
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function normalizarFolio($folio) {
        // Separar en dos partes: antes y después del guion
        $partes = explode('-', $folio, 2);
        
        if (count($partes) == 2) {
            $parteNumerica = ltrim($partes[0], '0'); // Quitar ceros a la izquierda
            if ($parteNumerica === '') $parteNumerica = '0'; // Asegura que no quede vacío
            return $parteNumerica . '-' . $partes[1];
        }
    
        // Si no tiene guion, devuelve tal cual
        return $folio;
    }

    function arraycomparacionmaletatraspasobyfolio($almacenid,$traspasoid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                'ALMACEN' FUENTE, INVENTARIODET_ID INVDETID, INVENTARIODET_ARTICULOID ARTICULOID, TEMP_ETIQUETA
            FROM AMPAR_INVENTARIODET 
            LEFT JOIN AMPAR_INVENTARIO ON INVENTARIO_ID = INVENTARIODET_INVENTARIOID
            WHERE
                INVENTARIO_STATUS = 2
                AND INVENTARIODET_ALMACENID = ".$almacenid."
            UNION ALL
            SELECT 
                'TRASPASO', TRASPASODET_INVENTARIODETID, INVENTARIODET_ARTICULOID ARTICULOID, TEMP_ETIQUETA
            FROM AMPAR_HIS_TRASPASO 
            LEFT JOIN AMPAR_HIS_TRASPASODET ON TRASPASO_ID = TRASPASODET_TRASPASOID
            LEFT JOIN AMPAR_INVENTARIODET ON INVENTARIODET_ID = TRASPASODET_INVENTARIODETID
            WHERE TRASPASO_ID = '".$traspasoid."'
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function comparacionescaner($dbResults,$epcs,$traspasoid,$traspasofolio){
        // --- Indexar los resultados de BD por etiqueta ---
        $dbByEpc = [];
        foreach ($dbResults as $row) {
            $dbByEpc[$row['TEMP_ETIQUETA']] = $row;
        }
        // --- Unir claves de ambos arrays para full outer join ---
        $allKeys = array_unique(array_merge(
            array_values($epcs),
            array_keys($dbByEpc)
        ));
        // --- Construir el resultado comparado ---
        $comparacion = [];
        foreach ($allKeys as $etiqueta) {
            $enJson = in_array($etiqueta, $epcs, true);
            $enDb   = isset($dbByEpc[$etiqueta]);

            $comparacion[] = [
                // etiqueta según JSON (o cadena vacía)
                'etiqueta_json'  => $enJson ? $etiqueta : '',
                // etiqueta según BD  (o cadena vacía)
                'etiqueta_bd'    => $enDb   ? $etiqueta : '',
                // campos adicionales de la BD, o null si no existe
                'FUENTE'         => $enDb ? $dbByEpc[$etiqueta]['FUENTE'] : null,
                'INVDETID'       => $enDb ? $dbByEpc[$etiqueta]['INVDETID'] : null,
                'ARTICULOID'     => $enDb ? $dbByEpc[$etiqueta]['ARTICULOID'] : null,
            ];
        }
        // $comparacion de array resultante
        $allMatched = true;
        $missing   = [];

        foreach ($comparacion as $item) {
            // Si falta en uno de los dos lados
            if (empty($item['etiqueta_json']) || empty($item['etiqueta_bd'])) {
                $allMatched = false;
                $missing[]  = $item;
            }
        }

        if ($allMatched) {
            // ✅ Todo coincide, SE AUTORIZA EL FOLIO DE TRASPASO
            $almacenes = new almacenes();
            $almacenes->updatestatustraspasos($traspasoid,2);
            // CANCELAR TICKETS PREVIOS DEL MISMO TRASPASO
            $db = new FirebirdConnection();
            $this->cancelacionticket($db,'CANCELADO POR EL SISTEMA POR VALIDACIÓN CORRECTA',1,$traspasoid);
            $db->close();
            return "Solicitud de Transferencia validada correctamente.";
        } else {
            // ⚠️ Hubo discrepancias: $missing contiene sólo los que fallaron
            $folioticket = $this->crearticket(1,$traspasoid,'Incidencias en validación de etiquetas EPC, Folio de Traspaso:'.$traspasofolio,$comparacion);
            return "Incidencias en validación, se creó ticket con folio ".$folioticket." favor de dar seguimiento.";
        }

    }

    function crearticket($tipo,$bdid,$concepto,$comparacion){
        $db = new FirebirdConnection();
        //CREAR TICKET
        $sql = "
            Insert into AMPAR_TICKETS
            (          
                TICKET_FECHA,
                TICKET_FOLIO,
                TICKET_TIPO,
                TICKET_BDID,
                TICKET_STATUS,
                TICKET_CONCEPTO
            )
            VALUES
            (
                CURRENT_TIMESTAMP, 
                (
                    SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(TICKET_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                    FROM AMPAR_TICKETS
                ),
                '".$tipo."',
                ".$bdid.",
                1,
                '".$concepto."'
            )
        ";
        $id_insertado = $db->executeconreturning($sql,'TICKET_ID');
        $sql2 = "select TICKET_FOLIO FROM AMPAR_TICKETS WHERE TICKET_ID = ".$id_insertado;
        $resfolio = $db->query($sql2);
        $folioticket = $resfolio[0]['TICKET_FOLIO'];
        if ($tipo==3){
            //CONSULTAR EL DETALLE DEL ESCANEO
            $resescaneos = $this->getescaneosbyid($bdid);
            if ($resescaneos<>0){
                foreach ($resescaneos as $re){
                    if ($re['RFIDDET_TIPO'] == 'FALTANTE'){
                        $sql3 = "
                        INSERT INTO AMPAR_TICKETSDET
                        (   
                            TICKETDET_TICKETID,
                            TICKETDET_ETIQUETAESCANER,
                            TICKETDET_ETIQUETABD,
                            TICKETDET_FUENTE,
                            TICKETDET_INVDETID
                        )
                        VALUES
                        (
                            ".$id_insertado.",
                            NULL,
                            ".$re['RFIDDET_EPC'].",
                            'ALMACEN',
                            ".$re['RFIDDET_STOCKFOLIO']."
                        )
                        ";
                        $db->execute($sql3);
                    }
                }
            }else{
                echo "No se encontró información";
            }
        }else{
            //INSERTAR DETALLE DE TICKET
            foreach ($comparacion as $row){
                $sql3 = "
                INSERT INTO AMPAR_TICKETSDET
                (   
                    TICKETDET_TICKETID,
                    TICKETDET_ETIQUETAESCANER,
                    TICKETDET_ETIQUETABD,
                    TICKETDET_FUENTE,
                    TICKETDET_INVDETID
                )
                VALUES
                (
                    ".$id_insertado.",
                    ".(isset($row['etiqueta_json'])?"'".$row['etiqueta_json']."'":'null').",
                    ".(isset($row['etiqueta_bd'])?"'".$row['etiqueta_bd']."'":'null').",
                    ".(isset($row['FUENTE'])?"'".$row['FUENTE']."'":'null').",
                    ".(isset($row['INVDETID'])?$row['INVDETID']:'null')."
                )
                ";
                $db->execute($sql3);
            }
            $this->cancelacionticket($db,'CANCELADO POR EL SISTEMA POR LA CREACIÓN DE TICKET CON FOLIO:'.$folioticket,$tipo,$bdid,$id_insertado);
        }
        $db->close();
        return $folioticket;
    }

    function cancelacionticket($db,$comentario,$tipoticket,$bdid,$id_insertado=""){
        //PONER CANCELADOS TODOS LOS TICKETS QUE SE ENCUENTREN EN STATUS GUARDADOS
        $sql4 = "
            UPDATE AMPAR_TICKETS
            SET 
            TICKET_STATUS = 3,
            TICKET_FECHACIERRE = CURRENT_TIMESTAMP,
            TICKET_COMENTARIOCIERRE = '".$comentario."'
            WHERE 
            TICKET_TIPO = ".$tipoticket."
            AND TICKET_BDID = ".$bdid."
            AND TICKET_STATUS = 1
        ";
        if ($id_insertado <> ""){
            $sql4 .= " AND TICKET_ID <> ".$id_insertado;
        }
        //echo $sql4;
        $db->execute($sql4);
    }

    function eventoconsultastatusfolio($folio) {
        $folio = $this->normalizarFolio($folio);
        $db = new FirebirdConnection();
        $sql = "
            SELECT * FROM AMPAR_HIS_EVENTOS
            LEFT JOIN AMPAR_HIS_EVENTOSMALETAS ON EVENTOMALETA_EVENTOID = EVENTO_ID
            WHERE 
            TRIM(LEADING '0' FROM SUBSTRING(EVENTO_FOLIO FROM 1 FOR POSITION('-' IN EVENTO_FOLIO) - 1)) || 
            SUBSTRING(EVENTO_FOLIO FROM POSITION('-' IN EVENTO_FOLIO)) = '".$folio."'
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function arraycomparacionmaletaeventobyfolio($almacenid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT 
                'ALMACEN' FUENTE, INVENTARIODET_ID INVDETID, INVENTARIODET_ARTICULOID ARTICULOID, TEMP_ETIQUETA
            FROM AMPAR_INVENTARIODET 
            LEFT JOIN AMPAR_INVENTARIO ON INVENTARIO_ID = INVENTARIODET_INVENTARIOID
            WHERE
                INVENTARIO_STATUS = 2
                AND INVENTARIODET_ALMACENID = ".$almacenid."
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function comparacionescanereventos($dbResults,$tipo_evento,$epcs,$eventoid,$eventofolio){
        // --- Indexar los resultados de BD por etiqueta ---
        $dbByEpc = [];
        foreach ($dbResults as $row) {
            $dbByEpc[$row['TEMP_ETIQUETA']] = $row;
        }
        // --- Unir claves de ambos arrays para full outer join ---
        $allKeys = array_unique(array_merge(
            array_values($epcs),
            array_keys($dbByEpc)
        ));
        // --- Construir el resultado comparado ---
        $comparacion = [];
        foreach ($allKeys as $etiqueta) {
            $enJson = in_array($etiqueta, $epcs, true);
            $enDb   = isset($dbByEpc[$etiqueta]);

            $comparacion[] = [
                // etiqueta según JSON (o cadena vacía)
                'etiqueta_json'  => $enJson ? $etiqueta : '',
                // etiqueta según BD  (o cadena vacía)
                'etiqueta_bd'    => $enDb   ? $etiqueta : '',
                // campos adicionales de la BD, o null si no existe
                'FUENTE'         => $enDb ? $dbByEpc[$etiqueta]['FUENTE'] : null,
                'INVDETID'       => $enDb ? $dbByEpc[$etiqueta]['INVDETID'] : null,
                'ARTICULOID'     => $enDb ? $dbByEpc[$etiqueta]['ARTICULOID'] : null,
            ];
        }
        // $comparacion de array resultante
        $allMatched = true;
        $missing   = [];

        foreach ($comparacion as $item) {
            // Si falta en uno de los dos lados
            if (empty($item['etiqueta_json']) || empty($item['etiqueta_bd'])) {
                $allMatched = false;
                $missing[]  = $item;
            }
        }

        if ($tipo_evento == 1){
            $te = "salida";
        }else if ($tipo_evento == 2){
            $te = "entrada";
        }else{
            $te = "NA";
        }

        if ($allMatched) {
            // ✅ Todo coincide
            // CANCELAR TICKETS PREVIOS DEL MISMO EVENTO
            $db = new FirebirdConnection();
            $this->cancelacionticket($db,'CANCELADO POR EL SISTEMA POR VALIDACIÓN CORRECTA',2,$eventoid);
            $db->close();
            return "Evento de ".$te." validado correctamente.";
        } else {
            // ⚠️ Hubo discrepancias: $missing contiene sólo los que fallaron
            $folioticket = $this->crearticket(2,$eventoid,'Incidencias en validación de etiquetas EPC, Folio de Evento:'.$eventofolio,$comparacion);
            return "Incidencias en validación de escaneo de ".$te.", se creó ticket con folio ".$folioticket." favor de dar seguimiento.";
        }

    }
    
}

?>