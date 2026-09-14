<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
require_once("../vendor/autoload.php"); // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

try {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { ob_end_clean(); }
    }

    $db = new FirebirdConnection();

    // 1) Artículos
    $arts = $db->query("
      SELECT AR.ARTICULO_ID, X.ARTICULO_CLAVE, AR.NOMBRE AS ARTICULO_NOMBRE
      FROM ARTICULOS AR
      LEFT JOIN (
        SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO AS ARTICULO_CLAVE, ARTICULO_ID
        FROM CLAVES_ARTICULOS
        WHERE ROL_CLAVE_ART_ID = 17
      ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
      WHERE AR.ESTATUS = 'A'
      ORDER BY X.ARTICULO_CLAVE, AR.NOMBRE
    ");

    // 2) Proveedores que actualmente tienen precios (para hacer las columnas)
    $provs = $db->query("
      SELECT DISTINCT PR.PROVEEDOR_ID, PR.NOMBRE
      FROM AMPAR_CAT_ARTCOMPRA P
      JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
      WHERE PR.NOMBRE IS NOT NULL AND TRIM(PR.NOMBRE) <> ''
      ORDER BY PR.NOMBRE
    ");
    if (!$provs) $provs = [];

    // 3) Precios (Solo nos importa el SUBTOTAL)
    $precios = $db->query("
      SELECT P.ARTCOMPRA_ARTICULOID AS ARTICULO_ID,
             P.ARTCOMPRA_PROVEEDORID AS PROVEEDOR_ID,
             P.ARTCOMPRA_SUBTOTAL AS SUBTOTAL
      FROM AMPAR_CAT_ARTCOMPRA P
    ");
    
    // Indexar precios: [ARTICULO_ID][PROVEEDOR_ID] = SUBTOTAL
    // Usaremos PROVEEDOR_ID = 0 para el PRECIO BASE (PROVEEDORID IS NULL)
    $byArt = [];
    foreach ($precios ?: [] as $p) {
        $aid = (int)$p['ARTICULO_ID'];
        $pid = is_null($p['PROVEEDOR_ID']) ? 0 : (int)$p['PROVEEDOR_ID'];
        $byArt[$aid][$pid] = (float)$p['SUBTOTAL'];
    }

    // 4) Armar Excel
    $spread = new Spreadsheet();
    $sh = $spread->getActiveSheet();
    $sh->setTitle('Precios Matriz');

    // Headers fijos
    $headers = [
      'A' => 'ARTICULO_ID (No modificar)',
      'B' => 'REFERENCIA (Clave)',
      'C' => 'NOMBRE_ARTICULO (Informativo)',
      'D' => 'PRECIO_BASE',
    ];
    
    // Headers dinámicos para proveedores
    $colLetter = 'E';
    $provCols = []; // [PROVEEDOR_ID => Letra]
    foreach ($provs as $pr) {
        $headers[$colLetter] = trim($pr['NOMBRE']);
        $provCols[(int)$pr['PROVEEDOR_ID']] = $colLetter;
        $colLetter++; // Siguiente letra (PHP maneja 'Z' -> 'AA')
    }

    foreach($headers as $col=>$name){ 
        $sh->setCellValue("{$col}1", $name); 
    }
    
    // Estilo headers
    $lastCol = chr(ord($colLetter)-1); // Esto funciona si no pasa de Z. Para que sea seguro, tomaremos la lista de keys.
    $lastCol = array_key_last($headers);
    
    $sh->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
    // Color de fondo para que sepan que es editable a partir de D
    $sh->getStyle("D1:{$lastCol}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3'); // Verde clarito
    $sh->freezePane('A2');

    // Llenar datos
    $row = 2;
    foreach ($arts ?: [] as $a) {
        $aid   = (int)$a['ARTICULO_ID'];
        $clave = (string)($a['ARTICULO_CLAVE'] ?? '');
        $nom   = (string)$a['ARTICULO_NOMBRE'];

        $sh->setCellValueExplicit("A{$row}", (string)$aid, DataType::TYPE_STRING);
        $sh->setCellValueExplicit("B{$row}", $clave, DataType::TYPE_STRING);
        $sh->setCellValueExplicit("C{$row}", $nom, DataType::TYPE_STRING);
        
        // Precio Base
        if (isset($byArt[$aid][0])) {
            $sh->setCellValue("D{$row}", $byArt[$aid][0]);
        }
        
        // Precios Proveedores
        foreach ($provs as $pr) {
            $pid = (int)$pr['PROVEEDOR_ID'];
            if (isset($byArt[$aid][$pid])) {
                $c = $provCols[$pid];
                $sh->setCellValue("{$c}{$row}", $byArt[$aid][$pid]);
            }
        }
        
        $row++;
    }

    // Formato numérico para precios
    if ($row > 2) {
        $sh->getStyle("D2:{$lastCol}".($row-1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    }

    foreach(array_keys($headers) as $col){
        $sh->getColumnDimension($col)->setAutoSize(true);
    }

    // -------- Headers HTTP --------
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="catalogo_precios_compra.xlsx"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

    $writer = new Xlsx($spread);
    if (function_exists('ini_get') && ini_get('zlib.output_compression')) {
        @ini_set('zlib.output_compression', 'Off');
    }
    $writer->save('php://output');
    exit;

} catch (Throwable $e) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { ob_end_clean(); }
    }
    $spread = new Spreadsheet();
    $spread->getActiveSheet()->setTitle('Error');
    $spread->getActiveSheet()->setCellValue('A1', 'Error: '.$e->getMessage());
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="catalogo_precios_error.xlsx"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
    $writer = new Xlsx($spread);
    $writer->save('php://output');
    exit;
}
