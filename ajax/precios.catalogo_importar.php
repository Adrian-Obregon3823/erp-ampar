<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
require_once("../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

try {
  // Validar archivo
  if (empty($_FILES['file']['tmp_name'])) {
    throw new Exception("Archivo no recibido.");
  }

  // Recomendado para archivos grandes
  @ini_set('max_execution_time', '300');
  @ini_set('memory_limit', '512M');

  $db = new FirebirdConnection();

  // Cargar Excel
  $spread = IOFactory::load($_FILES['file']['tmp_name']);
  $sh = $spread->getActiveSheet();
  $lastRow = $sh->getHighestRow();

  $resultado = ['creados'=>0, 'actualizados'=>0, 'errores'=>[]];

  // Iniciar transacción
  $didBegin = false;
  if (method_exists($db,'beginTransaction')) { 
    try { $db->beginTransaction(); $didBegin = true; } catch (Throwable $e) {}
  }

  // Iterar filas (desde 2)
  for ($r = 2; $r <= $lastRow; $r++) {
    try {
      $ARTPRECIO_ID = trim((string)$sh->getCell("A$r")->getValue());       // puede venir vacío
      $ARTICULO_ID  = (int)trim((string)$sh->getCell("B$r")->getValue());  // obligatorio
      $TIPO         = strtoupper(trim((string)$sh->getCell("E$r")->getValue())); // BASE o CLIENTE
      $CLI_RAW      = trim((string)$sh->getCell("F$r")->getValue());       // CLIENTE_ID para CLIENTE
      $SUBTOTAL     = $sh->getCell("H$r")->getCalculatedValue();
      $IVA          = $sh->getCell("I$r")->getCalculatedValue();
      $TOTAL        = $sh->getCell("J$r")->getCalculatedValue();
    } catch (Throwable $e) {
      $resultado['errores'][] = "Fila $r: Error al leer datos (posible fórmula inválida). Detalles: " . $e->getMessage();
      continue;
    }

    // Fila vacía => saltar
    if ($ARTPRECIO_ID==='' && $ARTICULO_ID===0 && $TIPO==='' && $CLI_RAW==='' && $SUBTOTAL==='' && $IVA==='' && $TOTAL==='') {
      continue;
    }

    // Validaciones
    if ($ARTICULO_ID <= 0) { $resultado['errores'][] = "Fila $r: ARTICULO_ID inválido."; continue; }
    if ($TIPO !== 'BASE' && $TIPO !== 'CLIENTE') { $resultado['errores'][] = "Fila $r: TIPO debe ser BASE o CLIENTE."; continue; }

    $CLIENTE_ID = null;
    if ($TIPO === 'CLIENTE') {
      if ($CLI_RAW === '') { $resultado['errores'][] = "Fila $r: Falta CLIENTE_ID para TIPO=CLIENTE."; continue; }
      if (!ctype_digit($CLI_RAW)) { $resultado['errores'][] = "Fila $r: CLIENTE_ID debe ser numérico."; continue; }
      $CLIENTE_ID = (int)$CLI_RAW;
    }

    $SUBTOTAL = ($SUBTOTAL === '' || $SUBTOTAL === null) ? 0 : (float)$SUBTOTAL;
    $IVA      = ($IVA === '' || $IVA === null) ? 0 : (float)$IVA;
    $TOTAL    = ($TOTAL === '' || $TOTAL === null) ? 0 : (float)$TOTAL;

    if ($SUBTOTAL < 0 || $IVA < 0 || $TOTAL < 0) {
      $resultado['errores'][] = "Fila $r: Montos negativos no permitidos.";
      continue;
    }

    try {
      if ($ARTPRECIO_ID !== '') {
        // UPDATE por ID (preserva IDs)
        $db->execute("
          UPDATE AMPAR_CAT_ARTPRECIO
             SET ARTPRECIO_ARTICULOID = ?,
                 ARTPRECIO_CLIENTEID  = ?,
                 ARTPRECIO_SUBTOTAL   = ?,
                 ARTPRECIO_IVA        = ?,
                 ARTPRECIO_TOTAL      = ?
           WHERE ARTPRECIO_ID = ?
        ", [ $ARTICULO_ID, $CLIENTE_ID, $SUBTOTAL, $IVA, $TOTAL, (int)$ARTPRECIO_ID ]);
        $resultado['actualizados']++;
      } else {
        // UPSERT por (ARTICULO_ID, CLIENTE_ID) considerando NULL como base
        if ($CLIENTE_ID === null) {
          $ex = $db->query("
            SELECT ARTPRECIO_ID FROM AMPAR_CAT_ARTPRECIO
             WHERE ARTPRECIO_ARTICULOID = ?
               AND ARTPRECIO_CLIENTEID IS NULL
          ", [ $ARTICULO_ID ]);
        } else {
          $ex = $db->query("
            SELECT ARTPRECIO_ID FROM AMPAR_CAT_ARTPRECIO
             WHERE ARTPRECIO_ARTICULOID = ?
               AND ARTPRECIO_CLIENTEID = ?
          ", [ $ARTICULO_ID, $CLIENTE_ID ]);
        }

        if ($ex && isset($ex[0]['ARTPRECIO_ID'])) {
          $db->execute("
            UPDATE AMPAR_CAT_ARTPRECIO
               SET ARTPRECIO_SUBTOTAL = ?, ARTPRECIO_IVA = ?, ARTPRECIO_TOTAL = ?
             WHERE ARTPRECIO_ID = ?
          ", [ $SUBTOTAL, $IVA, $TOTAL, $ex[0]['ARTPRECIO_ID'] ]);
          $resultado['actualizados']++;
        } else {
          $db->execute("
            INSERT INTO AMPAR_CAT_ARTPRECIO
              (ARTPRECIO_ARTICULOID, ARTPRECIO_CLIENTEID, ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL)
            VALUES (?, ?, ?, ?, ?)
          ", [ $ARTICULO_ID, $CLIENTE_ID, $SUBTOTAL, $IVA, $TOTAL ]);
          $resultado['creados']++;
        }
      }
    } catch (Throwable $e) {
      $resultado['errores'][] = "Fila $r: ".$e->getMessage();
      continue;
    }
  }

  if ($didBegin && method_exists($db,'commit')) { $db->commit(); }
  $db->close();

  echo json_encode(['ok'=>true, 'resultado'=>$resultado], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($db) && method_exists($db,'rollback')) { $db->rollback(); }
  if (isset($db)) $db->close();
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
