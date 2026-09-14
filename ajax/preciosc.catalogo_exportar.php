<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
require_once("../vendor/autoload.php"); // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;


try {
    // IMPORTANTÍSIMO: limpiar cualquier buffer previo (BOM, ecos, warnings)
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
      ORDER BY X.ARTICULO_CLAVE, AR.NOMBRE
    ");

    // 2) Precios existentes + proveedor
    $precios = $db->query("
      SELECT P.ARTCOMPRA_ID,
             P.ARTCOMPRA_ARTICULOID   AS ARTICULO_ID,
             X.ARTICULO_CLAVE,
             A.NOMBRE                 AS ARTICULO_NOMBRE,
             P.ARTCOMPRA_PROVEEDORID    AS PROVEEDOR_ID,
             C.NOMBRE                 AS PROVEEDOR_NOMBRE,
             P.ARTCOMPRA_SUBTOTAL     AS SUBTOTAL,
             P.ARTCOMPRA_IVA          AS IVA,
             P.ARTCOMPRA_TOTAL        AS TOTAL
      FROM AMPAR_CAT_ARTCOMPRA P
      JOIN ARTICULOS A ON A.ARTICULO_ID = P.ARTCOMPRA_ARTICULOID
      LEFT JOIN PROVEEDORES C ON C.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
      LEFT JOIN (
        SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO AS ARTICULO_CLAVE, ARTICULO_ID
        FROM CLAVES_ARTICULOS
        WHERE ROL_CLAVE_ART_ID = 17
      ) X ON X.ARTICULO_ID = A.ARTICULO_ID
      ORDER BY X.ARTICULO_CLAVE, C.NOMBRE
    ");

    // 3) Indexar precios por artículo
    $byArt = [];
    foreach ($precios ?: [] as $p) {
        $byArt[(int)$p['ARTICULO_ID']][] = $p;
    }

    // 4) Armar Excel
    $spread = new Spreadsheet();
    $sh = $spread->getActiveSheet();
    $sh->setTitle('Precios');

    $headers = [
      'A' => 'ARTCOMPRA_ID (NO MOVER)',
      'B' => 'ARTICULO_ID (NO MOVER)',
      'C' => 'ARTICULO_CLAVE (NO MOVER)',
      'D' => 'ARTICULO_NOMBRE (NO MOVER)',
      'E' => 'TIPO (BASE/PROVEEDOR)',
      'F' => 'PROVEEDOR_ID (si TIPO=PROVEEDOR)',
      'G' => 'PROVEEDOR_NOMBRE (informativo)',
      'H' => 'SUBTOTAL',
      'I' => 'IVA',
      'J' => 'TOTAL',
    ];
    foreach($headers as $col=>$name){ $sh->setCellValue("{$col}1", $name); }
    $sh->getStyle('A1:J1')->getFont()->setBold(true);
    $sh->freezePane('A2');

    $row = 2;
    foreach ($arts ?: [] as $a) {
        $aid   = (int)$a['ARTICULO_ID'];
        $clave = (string)($a['ARTICULO_CLAVE'] ?? '');
        $nom   = (string)$a['ARTICULO_NOMBRE'];

        if (!empty($byArt[$aid])) {
            foreach ($byArt[$aid] as $p) {
                $sh->setCellValueExplicit("A{$row}", (string)$p['ARTCOMPRA_ID'], DataType::TYPE_STRING);
                $sh->setCellValueExplicit("B{$row}", (string)$p['ARTICULO_ID'], DataType::TYPE_STRING);
                $sh->setCellValueExplicit("C{$row}", $clave, DataType::TYPE_STRING);
                $sh->setCellValueExplicit("D{$row}", $nom, DataType::TYPE_STRING);
                $sh->setCellValueExplicit("E{$row}", ($p['PROVEEDOR_ID']===null?'BASE':'PROVEEDOR'), DataType::TYPE_STRING);
                $sh->setCellValueExplicit("F{$row}", ($p['PROVEEDOR_ID']===null?'':(string)$p['PROVEEDOR_ID']), DataType::TYPE_STRING);
                $sh->setCellValueExplicit("G{$row}", (string)($p['PROVEEDOR_NOMBRE'] ?? ''), DataType::TYPE_STRING);
                // Montos: números (si llegan null, deja vacío)
                $sh->setCellValue("H{$row}", is_null($p['SUBTOTAL']) ? '' : (float)$p['SUBTOTAL']);
                $sh->setCellValue("I{$row}", is_null($p['IVA'])      ? '' : (float)$p['IVA']);
                $sh->setCellValue("J{$row}", is_null($p['TOTAL'])    ? '' : (float)$p['TOTAL']);
                $row++;
            }
        } else {
            // Fila placeholder BASE
            $sh->setCellValueExplicit("A{$row}", '', DataType::TYPE_STRING);
            $sh->setCellValueExplicit("B{$row}", (string)$aid, DataType::TYPE_STRING);
            $sh->setCellValueExplicit("C{$row}", $clave, DataType::TYPE_STRING);
            $sh->setCellValueExplicit("D{$row}", $nom, DataType::TYPE_STRING);
            $sh->setCellValueExplicit("E{$row}", 'BASE', DataType::TYPE_STRING);
            $sh->setCellValueExplicit("F{$row}", '', DataType::TYPE_STRING);
            $sh->setCellValueExplicit("G{$row}", '', DataType::TYPE_STRING);
            $sh->setCellValue("H{$row}", '');
            $sh->setCellValue("I{$row}", '');
            $sh->setCellValue("J{$row}", '');
            $row++;
        }
    }

    // Formato numérico
    if ($row > 2) {
        $sh->getStyle("H2:J".($row-1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    }

    // Validación de TIPO: en PhpSpreadsheet, para rango grande conviene clonar por bloque
    $dv = new DataValidation();
    $dv->setType(DataValidation::TYPE_LIST);
    $dv->setErrorStyle(DataValidation::STYLE_STOP);
    $dv->setAllowBlank(false);
    $dv->setShowInputMessage(true);
    $dv->setShowErrorMessage(true);
    $dv->setShowDropDown(true);
    $dv->setFormula1('"BASE,PROVEEDOR"');

    $maxRow = max($row-1, 2);
    for ($r = 2; $r <= $maxRow; $r++) {
        $cellDv = clone $dv;
        $sh->getCell("E{$r}")->setDataValidation($cellDv);
    }

    // Proteger hoja (bloquear IDs/nombres)
    $sh->getProtection()->setPassword('precios!2024');
    $sh->getProtection()->setSheet(true);
    $spread->getDefaultStyle()->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);

    foreach (['E','F','H','I','J'] as $col) {
        $sh->getStyle($col."2:".$col.$maxRow)->getProtection()
           ->setLocked(Protection::PROTECTION_UNPROTECTED);
    }

    foreach(array_keys($headers) as $col){
        $sh->getColumnDimension($col)->setAutoSize(true);
    }

    // -------- Headers HTTP seguros (después de limpiar buffers) --------
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="catalogo_precios.xlsx"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

    $writer = new Xlsx($spread);

    // Por si tu servidor tiene zlib.output_compression=On, esto evita corrupción
    if (function_exists('ini_get') && ini_get('zlib.output_compression')) {
        @ini_set('zlib.output_compression', 'Off');
    }

    $writer->save('php://output');
    exit;

} catch (Throwable $e) {
    // En un export, evita mandar HTML; si quieres, loguea y manda XLSX vacío con una hoja "Error"
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
