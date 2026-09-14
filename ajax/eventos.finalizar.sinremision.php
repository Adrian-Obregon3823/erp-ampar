<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type:text/html; charset=utf-8');

try{
  $eventoid = intval($_POST['eventoid']??0);
  if($eventoid<=0) throw new Exception('Evento inválido');

  $db = new FirebirdConnection();
  $sqlVal = "
    SELECT E.EVENTO_STATUSGENERAL,
           COALESCE(R.REMISION_ID,0) AS REMISION_ID,
           COALESCE(R.REMISION_STATUS,0) AS REMISION_STATUS
    FROM AMPAR_HIS_EVENTOS E
    LEFT JOIN AMPAR_HIS_REMISIONES R ON R.REMISION_EVENTOID = E.EVENTO_ID
    WHERE E.EVENTO_ID = {$eventoid}
  ";
  $rows = $db->query($sqlVal);
  if ($rows == 0 || !is_array($rows)) throw new Exception('No existe el evento');
  $row = $rows[0];

  if ((int)$row['EVENTO_STATUSGENERAL'] !== 15) throw new Exception('El evento no está iniciado');
  if ((int)$row['REMISION_ID'] > 0 && (int)$row['REMISION_STATUS'] != 5) throw new Exception('El evento tiene una remisión activa');

  $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 3 WHERE EVENTO_ID = {$eventoid}");

  // Bitácora (opcional)
  $usr   = $_SESSION['ampar']['usuario']  ?? 'sistema';
  $usrid = $usr['USUARIO_ID']?? 'NULL';
  $ip    = $_SERVER['REMOTE_ADDR'] ?? null; $ip = $ip ? ("'".str_replace("'","''",$ip)."'") : "NULL";
  $usrS  = $usr['USUARIO_CORREO']?? '';
  $db->execute("INSERT INTO AMPAR_BITACORA (BITACORA_FECHA,BITACORA_COMENTARIO,BITACORA_USUARIOID,BITACORA_USUARIO,BITACORA_USUARIOIP,BITACORA_EVENTOID)
                VALUES (CURRENT_TIMESTAMP,'Finalizado sin remisión',{$usrid},'{$usrS}',{$ip},{$eventoid})");

  $db->close();
  echo ""; // éxito
}catch(Throwable $e){
  http_response_code(200);
  echo $e->getMessage();
}
?>