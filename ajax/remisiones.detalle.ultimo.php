<?php include_once("../includes/includes.php");
$remisionid = (int)($_GET['remisionid'] ?? 0);
$stockid    = (int)($_GET['stockid'] ?? 0);

$db = new FirebirdConnection();
$row = $db->query("
  SELECT FIRST 1 RA.REMISIONARTICULO_ID, RA.REMISIONARTICULO_SUBTOTAL, RA.REMISIONARTICULO_IVA, RA.REMISIONARTICULO_TOTAL,
         S.STOCK_FOLIO
  FROM AMPAR_HIS_REMISIONESARTICULOS RA
  JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = RA.REMISIONARTICULO_STOCKID
  WHERE RA.REMISIONARTICULO_REMISIONID = ?
    AND (RA.REMISIONARTICULO_STOCKID = ? OR S.STOCK_ARTICULOID = ?)
  ORDER BY RA.REMISIONARTICULO_ID DESC
", [$remisionid, $stockid, $stockid]);
$db->close();
echo json_encode($row ? $row[0] : new stdClass());
?>