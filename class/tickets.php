<?php

class tickets {
    
    function gettickets() {
        $db = new FirebirdConnection();
        $sql = "
            SELECT * FROM 
            AMPAR_TICKETS 
            LEFT JOIN AMPAR_CONF_STATUS  ON STATUS_ID = TICKET_STATUS
            LEFT JOIN AMPAR_TICKETTIPO ON TICKETTIPO_ID = TICKET_TIPO
            LEFT JOIN AMPAR_HIS_ESCANEO ON ESCANEO_ID = TICKET_BDID
            " . (($GLOBALS['isAdmin'] ?? false) ? "" : "WHERE ESCANEO_RESPONSABLEID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0)) . "
            ORDER BY TICKET_ID DESC
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function getticketbyid($ticketid) {
        $db = new FirebirdConnection();
        $sql = "
            SELECT * FROM 
            AMPAR_TICKETS 
            LEFT JOIN AMPAR_TICKETSDET ON TICKETDET_TICKETID = TICKET_ID
            LEFT JOIN AMPAR_HIS_STOCK ON STOCK_ID = TICKETDET_INVDETID
            LEFT JOIN AMPAR_HIS_ESDET ON ESDET_ID = STOCK_ESDETID
            LEFT JOIN ARTICULOS ar on ar.ARTICULO_ID = STOCK_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = ar.ARTICULO_ID
            LEFT JOIN AMPAR_CONF_STATUS  ON STATUS_ID = TICKET_STATUS
            LEFT JOIN AMPAR_TICKETTIPO ON TICKETTIPO_ID = TICKET_TIPO
            LEFT JOIN AMPAR_HIS_ESCANEO ON ESCANEO_ID = TICKET_BDID
            LEFT JOIN AMPAR_CAT_USUARIOS ON USUARIO_ID = ESCANEO_RESPONSABLEID
            WHERE TICKET_ID = ".$ticketid."
        ";
        $res = $db->query($sql);
        $db->close();
        return $res;
    }

    function updatestatusticket($id,$status,$comentario){
        $db = new FirebirdConnection();
        $sql = " 
            UPDATE AMPAR_TICKETS SET 
            TICKET_STATUS = ".$status.",
            TICKET_FECHACIERRE = CURRENT_TIMESTAMP,
            TICKET_COMENTARIOCIERRE = '".$comentario."'
            WHERE TICKET_ID = ".$id."
        ";
        $db->execute($sql);
        $db->close();
    }
    
}

?>