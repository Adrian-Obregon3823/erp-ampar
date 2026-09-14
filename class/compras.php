<?php

class compras{

    function guardar($sucursalid,$motivo,$articulos){
        $db = new FirebirdConnection();
        $sql = "
            Insert into AMPAR_COMPRA
            (          
                COMPRA_FECHA,
                COMPRA_FOLIO,
                COMPRA_SUCURSALID,
                COMPRA_MOTIVO,
                COMPRA_STATUSID
            )
            VALUES
            (
                CURRENT_TIMESTAMP, 
                (
                    SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(COMPRA_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
                    || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2))
                    FROM AMPAR_COMPRA
                ),
                ".$sucursalid.",
                '".$motivo."',
                1
            )
        ";
        $id_insertado = $db->executeconreturning($sql,'COMPRA_ID');
        $db->close();
        if ($id_insertado) {
            $this->guardardet($id_insertado,$articulos);
        } else {
            echo "Error al insertar registro.";
        }
    }

    function guardardet($idcompra,$articulos){
        $db = new FirebirdConnection();
        foreach ($articulos as $ar){
            $sql = "
                Insert into AMPAR_COMPRADET
                (          
                    COMPRADET_COMPRAID,
                    COMPRADET_ARTICULOID,
                    COMPRADET_CANTIDAD
                )
                VALUES
                (
                    ".$idcompra.",
                    ".$ar['articulo_id'].",
                    ".$ar['cantidad']."
                )
            ";
            $db->execute($sql);
        }
        $db->close();
    }

    function getcompras(){
        $db = new FirebirdConnection();
        $sql = "
            select 
            * 
            FROM AMPAR_COMPRA 
            LEFT JOIN AMPAR_CONF_STATUS ON STATUS_ID = COMPRA_STATUSID
            LEFT JOIN ampar_cat_sucursales ON SUCURSAL_ID = COMPRA_SUCURSALID
            ORDER BY COMPRA_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getinfocomprabyid($compraid){
        $db = new FirebirdConnection();
        $sql = "
            select 
            C.*, CD.*, ES.*, S.*, A.NOMBRE ARTICULO_NOMBRE,  AR.CLAVE_ARTICULO
            FROM AMPAR_COMPRA C
            LEFT JOIN AMPAR_COMPRADET CD ON COMPRA_ID = COMPRADET_COMPRAID
            LEFT JOIN AMPAR_CONF_STATUS ES ON STATUS_ID = COMPRA_STATUSID
            LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = COMPRA_SUCURSALID
            LEFT JOIN ARTICULOS A on ARTICULO_ID = COMPRADET_ARTICULOID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) AR ON AR.ARTICULO_ID = A.ARTICULO_ID
            WHERE COMPRA_ID = ".$compraid."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

}

?>