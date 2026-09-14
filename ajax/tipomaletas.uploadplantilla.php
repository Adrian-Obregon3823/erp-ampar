<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $result = [];
    $arti = new articulos();

    // Saltar encabezado
    for ($i = 1; $i < count($rows); $i++) {
        $clave    = isset($rows[$i][0]) ? trim($rows[$i][0]) : '';
        $articulo = isset($rows[$i][1]) ? trim($rows[$i][1]) : '';
        $cantidad = isset($rows[$i][2]) ? trim($rows[$i][2]) : '';

        // Validar formato
        $claveValida    = !empty($clave);
        $articuloValido = !empty($articulo);
        $cantidadValida = ctype_digit($cantidad) && intval($cantidad) >= 0;

        if ($claveValida && $articuloValido && $cantidadValida) {
            $resultadoart = $arti->buscarcvearticulo($clave);
            $estado = ($resultadoart === null) ? 'no_encontrado' : 'encontrado';
        } else {
            $estado = 'formato_incorrecto';
            $resultadoart = null;
        }

        $result[] = [
            'id'        => $resultadoart,
            'clave'     => $clave,
            'articulo'  => $articulo,
            'cantidad'  => $cantidad,
            'estado'    => $estado
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
}
?>