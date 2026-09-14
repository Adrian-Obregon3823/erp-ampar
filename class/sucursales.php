<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* CLASE PRINCIPAL PARA EL MANEJO DE ampar_cat_sucursales
*********************************************************************************
*/
?>
<?php

    class sucursales{

        //Query general para traer sucursales (now almacenes)
        function getsucursales(){
            $db = new FirebirdConnection();
            $sql = "
                SELECT 
                    ALMACEN_ID ID, ALMACEN_FOLIO AS FOLIO, ALMACEN_NOMBRE NOMBRE, 0 ES_MATRIZ, '' CIUDAD_NOMBRE
                FROM AMPAR_HIS_ALMACEN
                WHERE ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2)
                order by ALMACEN_NOMBRE
            ";
            $result = $db->query($sql);
            $db->close();
            return $result;
        }

        //Contiene la información de una sucursal desde microsip (now almacenes)
        function getsucursalbyid($sucursalid){
            $db = new FirebirdConnection();
            $sql = "
                SELECT 
                    A.ALMACEN_ID SUCURSAL_ID, 
                    A.ALMACEN_NOMBRE SUCURSAL_NOMBRE,
                    A.ALMACEN_FOLIO AS SUCURSAL_FOLIO,
                    0 ES_MATRIZ,
                    A.ALMACEN_CALLE NOMBRE_CALLE, A.ALMACEN_COLONIA COLONIA, A.ALMACEN_NUMEXT NUM_EXTERIOR, A.ALMACEN_NUMINT NUM_INTERIOR, '' POBLACION, '' REFERENCIA,
                    C.NOMBRE CIUDAD_NOMBRE, E.NOMBRE ESTADO_NOMBRE, P.NOMBRE PAIS_NOMBRE, A.ALMACEN_CP CODIGO_POSTAL, A.ALMACEN_TELEFONO TELEFONO1, '' TELEFONO2,
                    NULL LUGAR_EXPEDICION_ID, '' LUGAR_EXPEDICION
                FROM AMPAR_HIS_ALMACEN A
                LEFT JOIN CIUDADES C ON C.CIUDAD_ID = A.ALMACEN_MUNICIPIO
                LEFT JOIN ESTADOS E ON E.ESTADO_ID = C.ESTADO_ID
                LEFT JOIN PAISES P ON P.PAIS_ID = E.PAIS_ID
                WHERE A.ALMACEN_ID = ".$sucursalid."
            ";
            $result = $db->query($sql);
            $db->close();
            return $result;
        }

        //Clase que trae las sucursales filtradas o no por permisos de usuario (now almacenes)
        function getcatalogosucursales($usuarioid=""){
            $sql = "
                SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE 
                FROM AMPAR_HIS_ALMACEN
                WHERE ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2)
            ";
            if ($usuarioid <> ""){
                $sql .= "
                    AND ALMACEN_ID IN 
                    (
                        SELECT USUARIOSALMACENES_ALMACENID FROM
                        AMPAR_CAT_USUARIOSALMACENES
                        WHERE 
                        USUARIOSALMACENES_USUARIOID = ".$usuarioid."
                    )
                ";
            }
            $sql .= " order by ALMACEN_NOMBRE";
            $db = new FirebirdConnection();
            $result = $db->query($sql);
            $db->close();
            return $result;
        }

    }

?>