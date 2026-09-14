<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase principal para gestión de bitacora
*********************************************************************************
*/
?>
<?php
class bitacora{

    public static function getip(){
      $ips = [];
      
      // Get real visitor IP behind CloudFlare network
      if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
          $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $ips['cf_connecting_ip'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
      }
      
      $client  = @$_SERVER['HTTP_CLIENT_IP'];
      $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
      $remote  = $_SERVER['REMOTE_ADDR'];
      
      if(filter_var($client, FILTER_VALIDATE_IP)) {
          $ip = $client;
      } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
          $ip = $forward;
      } else {
          $ip = $remote;
      }
      
      $ips['client_ip'] = $client;
      $ips['forwarded_ip'] = $forward;
      $ips['remote_addr'] = $remote;
      $ips['final_ip'] = $ip;

      $user_ips = json_encode($ips);
      
      return $ip;
    }

    public static function guardar($comentario,$usuarioid,$usuario,$sucursalid=null,$almacenid=null,$stockid=null,$eventoid=null){
      $ips = [];
      
      // Get real visitor IP behind CloudFlare network
      if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
          $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $ips['cf_connecting_ip'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
      }
      
      $client  = @$_SERVER['HTTP_CLIENT_IP'];
      $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
      $remote  = $_SERVER['REMOTE_ADDR'];
      
      if(filter_var($client, FILTER_VALIDATE_IP)) {
          $ip = $client;
      } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
          $ip = $forward;
      } else {
          $ip = $remote;
      }
      
      $ips['client_ip'] = $client;
      $ips['forwarded_ip'] = $forward;
      $ips['remote_addr'] = $remote;
      $ips['final_ip'] = $ip;

      $user_ips = json_encode($ips);
      
      $db = new FirebirdConnection();

      $sql = "
        INSERT INTO AMPAR_BITACORA
        (
          BITACORA_FECHA,
          BITACORA_COMENTARIO,
          BITACORA_USUARIOID,
          BITACORA_USUARIO,
          BITACORA_USUARIOIP,
          BITACORA_SUCURSALID_MS,
          BITACORA_ALMACENID,
          BITACORA_STOCKID,
          BITACORA_EVENTOID
        )
        values
        (
          CURRENT_TIMESTAMP,
          '".$comentario."',
          ".$usuarioid.",
          '".$usuario."',
          '".$ips['final_ip']."',
          ".(($sucursalid==null)?'null':$sucursalid).",
          ".(($almacenid==null)?'null':$almacenid).",
          ".(($stockid==null)?'null':$stockid).",
          ".(($eventoid==null)?'null':$eventoid)."
        )
      ";
      $db->execute($sql);
  		$db->Close();
    }

    public static function guardardb($db,$comentario,$usuarioid,$usuario,$sucursalid=null,$almacenid=null,$stockid=null,$eventoid=null){
      $ips = [];
      
      // Get real visitor IP behind CloudFlare network
      if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
          $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          $ips['cf_connecting_ip'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
      }
      
      $client  = @$_SERVER['HTTP_CLIENT_IP'];
      $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
      $remote  = $_SERVER['REMOTE_ADDR'];
      
      if(filter_var($client, FILTER_VALIDATE_IP)) {
          $ip = $client;
      } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
          $ip = $forward;
      } else {
          $ip = $remote;
      }
      
      $ips['client_ip'] = $client;
      $ips['forwarded_ip'] = $forward;
      $ips['remote_addr'] = $remote;
      $ips['final_ip'] = $ip;

      $user_ips = json_encode($ips);

      $sql = "
        INSERT INTO AMPAR_BITACORA
        (
          BITACORA_FECHA,
          BITACORA_COMENTARIO,
          BITACORA_USUARIOID,
          BITACORA_USUARIO,
          BITACORA_USUARIOIP,
          BITACORA_SUCURSALID_MS,
          BITACORA_ALMACENID,
          BITACORA_STOCKID,
          BITACORA_EVENTOID
        )
        values
        (
          CURRENT_TIMESTAMP,
          '".$comentario."',
          ".$usuarioid.",
          '".$usuario."',
          '".$ips['final_ip']."',
          ".(($sucursalid==null)?'null':$sucursalid).",
          ".(($almacenid==null)?'null':$almacenid).",
          ".(($stockid==null)?'null':$stockid).",
          ".(($eventoid==null)?'null':$eventoid)."
        )
      ";
      $db->execute($sql);
  
    }

    function getbitacora($anio, $fecha_inicio, $fecha_fin, $usuario, $ip, $comentario, $sucursal, $almacen, $articulo){
      $db = new FirebirdConnection();
      $sql = "
        SELECT 
          BITACORA_ID, BITACORA_FECHA, BITACORA_USUARIO, BITACORA_USUARIOID, BITACORA_USUARIOIP, CAST(SUBSTRING(BITACORA_COMENTARIO FROM 1 FOR 2000) AS VARCHAR(2000)) AS BITACORA_COMENTARIO,
          S.NOMBRE SUCURSAL_NOMBRE, ALMACEN_NOMBRE, STOCK_FOLIO, AR.NOMBRE ARTICULO_NOMBRE,
          CLAVE_ARTICULO ARTICULO_CLAVE,
          U.USUARIO_NOMBRE
        FROM AMPAR_BITACORA B
        LEFT JOIN AMPAR_CAT_USUARIOS U ON USUARIO_ID = BITACORA_USUARIOID
        LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = BITACORA_SUCURSALID_MS
        LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = BITACORA_ALMACENID
        LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = BITACORA_STOCKID
        LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
        LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
        WHERE 1 = 1
      ";

      if ($anio) $sql .= " AND EXTRACT(YEAR FROM BITACORA_FECHA) = ".$anio;
      if ($fecha_inicio) $sql .= " AND BITACORA_FECHA >= '".$fecha_inicio." 00:00:01'";
      if ($fecha_fin) $sql .= " AND BITACORA_FECHA <= '".$fecha_fin." 23:59:59'";
      if ($usuario) $sql .= " AND BITACORA_USUARIO CONTAINING '".$usuario."'";
      if ($ip) $sql .= " AND BITACORA_USUARIOIP CONTAINING ".$ip;
      if ($comentario) $sql .= " AND BITACORA_COMENTARIO CONTAINING '".$comentario."'";
      if ($sucursal) $sql .= " AND SUCURSAL_ID = ".$sucursal;
      if ($almacen) $sql .= " AND ALMACEN_ID = ".$almacen;
      if ($articulo) $sql .= " (AND STOCK_FOLIO CONTAINING '".$articulo."') OR (AND CLAVE_ARTICULO CONTAINING '".$articulo."') OR (AND AR.NOMBRE CONTAINING '".$articulo."')";

      $sql .=  " ORDER BY BITACORA_ID DESC";
      
      $res = $db->query($sql);
      $db->Close();
      return $res;
    }

    function getbitacoradetalle($sucursalid, $almacenid, $eventoid, $stockid){
      $db = new FirebirdConnection();
      $sql = "
        SELECT 
          BITACORA_ID, BITACORA_FECHA, BITACORA_USUARIO, BITACORA_USUARIOID, BITACORA_USUARIOIP, CAST(SUBSTRING(BITACORA_COMENTARIO FROM 1 FOR 2000) AS VARCHAR(2000)) AS BITACORA_COMENTARIO,
          S.NOMBRE SUCURSAL_NOMBRE, ALMACEN_NOMBRE, STOCK_FOLIO, AR.NOMBRE ARTICULO_NOMBRE,
          CLAVE_ARTICULO ARTICULO_CLAVE,
          U.USUARIO_NOMBRE
        FROM AMPAR_BITACORA B
        LEFT JOIN AMPAR_CAT_USUARIOS U ON USUARIO_ID = BITACORA_USUARIOID
        LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = BITACORA_SUCURSALID_MS
        LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = BITACORA_ALMACENID
        LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = BITACORA_STOCKID
        LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
        LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
        WHERE 1 = 1
      ";

      if ($sucursalid) $sql .= " AND BITACORA_SUCURSALID = ".$sucursalid;
      if ($almacenid) $sql .= " AND BITACORA_ALMACENID = ".$almacenid;
      if ($eventoid) $sql .= " AND BITACORA_EVENTOID = ".$eventoid;
      if ($stockid) $sql .= " AND BITACORA_STOCKID = ".$stockid;

      $sql .=  " ORDER BY BITACORA_ID DESC";
      
      $res = $db->query($sql);
      $db->Close();
      return $res;
    }

    function getbitacorabyid($bitacoraid){
      $db = new FirebirdConnection();
      $sql = "
        SELECT 
          BITACORA_ID, BITACORA_FECHA, BITACORA_USUARIO, BITACORA_USUARIOID, BITACORA_USUARIOIP, CAST(BITACORA_COMENTARIO AS VARCHAR(1000)) BITACORA_COMENTARIO,
          S.NOMBRE SUCURSAL_NOMBRE, ALMACEN_NOMBRE, STOCK_FOLIO, AR.NOMBRE ARTICULO_NOMBRE,
          CLAVE_ARTICULO ARTICULO_CLAVE
        FROM AMPAR_BITACORA B
        LEFT JOIN ampar_cat_sucursales S ON SUCURSAL_ID = BITACORA_SUCURSALID_MS
        LEFT JOIN AMPAR_HIS_ALMACEN A ON ALMACEN_ID = BITACORA_ALMACENID
        LEFT JOIN AMPAR_HIS_STOCK ST ON STOCK_ID = BITACORA_STOCKID
        LEFT JOIN ARTICULOS AR ON ARTICULO_ID = STOCK_ARTICULOID
        LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
        WHERE BITACORA_ID = ".$bitacoraid."
      ";
      
      $res = $db->query($sql);
      $db->Close();
      return $res;
    }

    public static function printarray($registro){
      $salida = [];
      foreach ($registro as $campo => $valor) {
        if ($valor<>''){
          $salida[] = "<strong>".$campo.":</strong> ".$valor;
        }
      }
      return implode(", ", $salida);
    }

    public static function printarraycomparacion($original,$cambio){
      $salida = [];
      foreach ($original as $campo => $valorOriginal) {
          if (isset($cambio[$campo]) && $cambio[$campo] !== $valorOriginal) {
              $valorNuevo = $cambio[$campo];
              $salida[] = "<strong>".$campo.":</strong> ".$valorOriginal." → ".$valorNuevo;
          }
      }
      return implode(", ", $salida);
    }

}
?>