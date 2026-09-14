<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type:text/html; charset=utf-8');

try{
  $eventoid = intval($_POST['eventoid']??0);
  if($eventoid<=0) throw new Exception('Evento inválido');
  if(!isset($_FILES['archivo']) || $_FILES['archivo']['error']!==UPLOAD_ERR_OK) throw new Exception('Archivo requerido');

  $db = new FirebirdConnection();
  $rows = $db->query("SELECT EVENTO_STATUSGENERAL, COALESCE(EVENTO_CLIENTEID,0) AS CLIENTEID
                      FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID={$eventoid}");
  if ($rows==0 || !is_array($rows)) throw new Exception('No existe el evento');
  $row = $rows[0];

  if ((int)$row['EVENTO_STATUSGENERAL'] !== 3)    throw new Exception('El evento no está finalizado');
  if ((int)$row['CLIENTEID'] <= 0)                throw new Exception('El evento no es de cliente');

  // Guardar archivo
  $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
  if(!in_array($ext,['pdf','jpg','jpeg','png'])) throw new Exception('Formato no permitido');

  $baseDir = __DIR__.'/../uploads/occliente';
  if(!is_dir($baseDir)) @mkdir($baseDir,0775,true);
  if(!is_dir($baseDir)) throw new Exception('No se pudo preparar carpeta');

  $fname = 'OC_'.$eventoid.'_'.date('Ymd_His').'.'.$ext;
  $dest  = $baseDir.DIRECTORY_SEPARATOR.$fname;
  if(!move_uploaded_file($_FILES['archivo']['tmp_name'],$dest)) throw new Exception('Error al guardar archivo');

  // TODO: INSERT pendiente
  // $notas  = trim($_POST['notas']??'');
  // $notasS = str_replace("'","''",$notas);
  // $pathRel= 'uploads/occliente/'.$fname;
  // $db->execute("INSERT INTO AMPAR_EVENTOS_OC (EOC_EVENTOID,EOC_PATH,EOC_NOTAS,EOC_FECHA)
  //               VALUES ({$eventoid},'{$pathRel}','{$notasS}',CURRENT_TIMESTAMP)");

  // Cambiar status a 22
  $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 22 WHERE EVENTO_ID = {$eventoid}");

  // Bitácora
  $usr   = $_SESSION['ampar']['usuario']  ?? 'sistema';
  $usrid = $usr['USUARIO_ID']?? 'NULL';
  $usrS  = $usr['USUARIO_CORREO']?? '';
  $pathS = str_replace("'","''",'uploads/occliente/'.$fname);
  $db->execute("INSERT INTO AMPAR_BITACORA (BITACORA_FECHA,BITACORA_COMENTARIO,BITACORA_USUARIOID,BITACORA_USUARIO,BITACORA_EVENTOID)
                VALUES (CURRENT_TIMESTAMP,'OC cliente subida: {$pathS}',{$usrid},'{$usrS}',{$eventoid})");

  $db->close();
  echo ""; // éxito
}catch(Throwable $e){
  http_response_code(200);
  echo $e->getMessage();
}
?>