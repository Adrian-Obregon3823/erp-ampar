<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['artcompra_id']) ? (int)$_POST['artcompra_id'] : 0;

try{
  if ($id <= 0) throw new Exception('ID inválido');

  $db = new FirebirdConnection(); // transacción por defecto
  $sql = "DELETE FROM AMPAR_CAT_ARTCOMPRA WHERE ARTCOMPRA_ID = ?";
  $ok  = $db->execute($sql, [$id]);
  if (!$ok) throw new Exception('No se pudo eliminar');

  $db->commit();
  echo json_encode(['ok'=>true]);

} catch(Throwable $e){
  if (isset($db)) $db->rollback();
  // Si hay FK, FB regresa error; lo devolvemos
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
