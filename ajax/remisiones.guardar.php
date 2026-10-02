<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar remision
*********************************************************************************
*/
?>
<?php
$remisiones = new remisiones();

$proveedorid = isset($_POST['proveedorid']) ? $_POST['proveedorid'] : [];
$idarticulosfiltro = isset($_POST['idarticulofiltro']) ? $_POST['idarticulofiltro'] : [];
$cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : [];
$subtotalarticulosfiltro = isset($_POST['preciounitario_sub']) ? $_POST['preciounitario_sub'] : [];
$ivaarticulosfiltro = isset($_POST['preciounitario_iva']) ? $_POST['preciounitario_iva'] : [];
$totalarticulosfiltro = isset($_POST['preciounitario_total']) ? $_POST['preciounitario_total'] : [];
$subtotalproveedor = isset($_POST['subproveedor']) ? $_POST['subproveedor'] : [];
$ivaproveedor = isset($_POST['ivaproveedor']) ? $_POST['ivaproveedor'] : [];
$totalproveedor = isset($_POST['totalproveedor']) ? $_POST['totalproveedor'] : [];

$remarticulosid = isset($_POST['remarticulosid']) ? $_POST['remarticulosid'] : [];
$remarticulossubtotal = isset($_POST['remarticulossubtotal']) ? $_POST['remarticulossubtotal'] : [];
$remarticulosiva = isset($_POST['remarticulosiva']) ? $_POST['remarticulosiva'] : [];
$remarticulostotal = isset($_POST['remarticulostotal']) ? $_POST['remarticulostotal'] : [];
$remalmacen = isset($_POST['remalmacen']) ? $_POST['remalmacen'] : null;
$remproyectoid = isset($_POST['remproyectoid']) && $_POST['remproyectoid'] !== "" ? $_POST['remproyectoid'] : null;

// Separar artículos en stock/proveedor y equipo capital
$articulos_stock = [];
$subtotales_stock = [];
$ivas_stock = [];
$totales_stock = [];

$articulos_ec = [];
$subtotales_ec = [];
$ivas_ec = [];
$totales_ec = [];

for ($i = 0; $i < count($remarticulosid); $i++) {
    $id = $remarticulosid[$i];
    // Equipo capital tiene prefijo 'ec_' o id negativo en front
    if (strpos($id, 'ec_') === 0 || (is_numeric($id) && (int)$id < 0)) {
        $articulos_ec[] = $id;
        $subtotales_ec[] = $remarticulossubtotal[$i] ?? 0;
        $ivas_ec[] = $remarticulosiva[$i] ?? 0;
        $totales_ec[] = $remarticulostotal[$i] ?? 0;
    } else {
        $articulos_stock[] = $id;
        $subtotales_stock[] = $remarticulossubtotal[$i] ?? 0;
        $ivas_stock[] = $remarticulosiva[$i] ?? 0;
        $totales_stock[] = $remarticulostotal[$i] ?? 0;
    }
}

$ids_creados = [];

// 1. Remisión de Stock y Proveedor
if (!empty($articulos_stock) || !empty($proveedorid)) {
    $id_stock = $remisiones->nuevaremision(
        $_POST['remsucalmacen'],
        $_POST['remeventoid'],
        $articulos_stock,
        $subtotales_stock,
        $ivas_stock,
        $totales_stock,
        $proveedorid,
        $idarticulosfiltro,
        $cantidades,
        $subtotalarticulosfiltro,
        $ivaarticulosfiltro,
        $totalarticulosfiltro,
        $subtotalproveedor,
        $ivaproveedor,
        $totalproveedor,
        $remalmacen,
        $remproyectoid
    );
    if ($id_stock) {
        $ids_creados[] = $id_stock;
    }
}

// 2. Remisión de Equipo Capital
if (!empty($articulos_ec)) {
    // Para equipo capital, no enviamos proveedor ni cantidades proveedor
    $id_ec = $remisiones->nuevaremision(
        $_POST['remsucalmacen'],
        $_POST['remeventoid'],
        $articulos_ec,
        $subtotales_ec,
        $ivas_ec,
        $totales_ec,
        [], [], [], [], [], [], [], [], [],
        $remalmacen,
        $remproyectoid
    );
    if ($id_ec) {
        $ids_creados[] = $id_ec;
    }
}

if (!empty($ids_creados)) {
    echo implode(',', $ids_creados);
} else {
    echo "Error al crear remisiones (ningún artículo seleccionado o error de BD).";
}
?>