<?php include_once("../includes/includes.php"); ?>
<?php include_once("../includes/head.php"); ?>
<?php
$rfid = new rfid();
$res = $rfid->getescaneosbyid(base64_decode($_GET['escaneoid']));
// Decodificar el JSON de detalles que mandó la API de FastAPI
$detalles = isset($res[0]['DETALLES_JSON']) ? json_decode($res[0]['DETALLES_JSON'], true) : ['correctos' => [], 'faltantes' => [], 'extras' => []];

// Función para agrupar elementos idénticos
function agrupar_items_scan($items)
{
    if (!is_array($items)) return [];
    $counts = [];
    foreach ($items as $item) {
        if (!isset($counts[$item])) $counts[$item] = 0;
        $counts[$item]++;
    }
    return $counts;
}

$grouped = [
    'correctos' => agrupar_items_scan($detalles['correctos'] ?? []),
    'faltantes' => agrupar_items_scan($detalles['faltantes'] ?? []),
    'sobrantes'  => agrupar_items_scan($detalles['extras'] ?? $detalles['sobrantes'] ?? [])
];

// Obtener todas las etiquetas/folios para consultar su descripción
$all_folios = array_unique(array_merge(
    array_keys($grouped['correctos']),
    array_keys($grouped['faltantes']),
    array_keys($grouped['sobrantes'])
));

$descripciones = [];
if (!empty($all_folios)) {
    $db = new FirebirdConnection();
    // Preparamos los folios para la consulta IN (...)
    $folios_quoted = array_map(function($f) { return "'" . addslashes($f) . "'"; }, $all_folios);
    $in_clause = implode(',', $folios_quoted);
    
    $sqlDesc = "
        SELECT st.STOCK_FOLIO, a.NOMBRE AS ARTICULO_NOMBRE
        FROM AMPAR_HIS_STOCK st
        LEFT JOIN AMPAR_HIS_ESDET ed ON ed.ESDET_ID = st.STOCK_ESDETID
        LEFT JOIN ARTICULOS a ON a.ARTICULO_ID = ed.ESDET_ARTICULOID
        WHERE st.STOCK_FOLIO IN ($in_clause)
    ";
    $resDesc = $db->query($sqlDesc);
    if ($resDesc) {
        foreach ($resDesc as $row) {
            $descripciones[$row['STOCK_FOLIO']] = $row['ARTICULO_NOMBRE'];
        }
    }
    $db->close();
}

// Función auxiliar para mostrar el nombre
function mostrar_nombre_articulo($folio, $descripciones) {
    if (isset($descripciones[$folio]) && !empty($descripciones[$folio])) {
        return $folio . ' - ' . $descripciones[$folio];
    }
    return $folio;
}
?>

<style>
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 600;
        border: 1px solid transparent;
        border-top-left-radius: .25rem;
        border-top-right-radius: .25rem;
    }

    .nav-tabs .nav-link.active {
        color: #000;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }

    .table-custom thead th {
        background-color: #f8f9fa;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }

    .table-custom tbody td {
        vertical-align: middle;
        color: #212529;
    }

    .qty-badge {
        background-color: #e9ecef;
        color: #000;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        margin-right: 10px;
        border: 1px solid #ced4da;
    }

    .text-contrast-success {
        color: #0f5132;
        font-weight: 600;
    }

    .text-contrast-danger {
        color: #842029;
        font-weight: 600;
    }

    .text-contrast-warning {
        color: #664d03;
        font-weight: 600;
    }
</style>

<div class="col-12">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 text-dark">Detalle de Escaneo <small class="text-muted">#<?= $res[0]['RFID_ID']; ?></small></h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-dark border-bottom pb-2">Información General</h5>
                    <table class="table table-sm table-borderless mt-3">
                        <tr>
                            <th width="150" class="text-muted">ID ESCANEO</th>
                            <td class="text-dark font-weight-bold"><?= $res[0]['RFID_ID']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">FOLIO MALETA</th>
                            <td class="text-dark font-weight-bold"><?= $res[0]['RFID_FOLIO']; ?> <?= !empty($res[0]['MALETA_NOMBRE']) ? ' - ' . htmlentities($res[0]['MALETA_NOMBRE']) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="text-dark border-bottom pb-2">Contexto</h5>
                    <table class="table table-sm table-borderless mt-3">
                        <tr>
                            <th width="150" class="text-muted">FECHA</th>
                            <td class="text-dark"><?= $res[0]['RFID_FECHA']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">RESPONSABLE</th>
                            <td class="text-dark"><?= $res[0]['RESPONSABLE_NOMBRE'] ?: '-' ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <h5 class="text-dark mb-3">Resultado de Validación</h5>

            <ul class="nav nav-tabs" id="scanTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="correctos-tab" data-toggle="tab" href="#correctos" role="tab">
                        Correctos <span class="badge badge-success ml-1 text-white"><?= count($detalles['correctos'] ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="faltantes-tab" data-toggle="tab" href="#faltantes" role="tab">
                        Faltantes <span class="badge badge-danger ml-1 text-white"><?= count($detalles['faltantes'] ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sobrantes-tab" data-toggle="tab" href="#sobrantes" role="tab">
                        Extras <span class="badge badge-warning text-dark ml-1"><?= count($detalles['extras'] ?? $detalles['sobrantes'] ?? []) ?></span>
                    </a>
                </li>
            </ul>

            <div class="tab-content border-left border-right border-bottom p-4 bg-white" id="scanTabsContent">
                <!-- Tab Correctos -->
                <div class="tab-pane fade show active" id="correctos" role="tabpanel">
                    <?php if (empty($grouped['correctos'])): ?>
                        <p class="text-muted italic">No hay artículos correctos en este escaneo.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>CANT.</th>
                                        <th>DESCRIPCIÓN DEL ARTÍCULO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped['correctos'] as $nombre => $cant): ?>
                                        <tr>
                                            <td width="100"><span class="qty-badge"><?= $cant ?></span></td>
                                            <td class="text-contrast-success"><?= mostrar_nombre_articulo($nombre, $descripciones) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Faltantes -->
                <div class="tab-pane fade" id="faltantes" role="tabpanel">
                    <?php if (empty($grouped['faltantes'])): ?>
                        <p class="text-muted italic">Escaneo completo: No hay artículos faltantes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>CANT.</th>
                                        <th>DESCRIPCIÓN DEL ARTÍCULO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped['faltantes'] as $nombre => $cant): ?>
                                        <tr>
                                            <td width="100"><span class="qty-badge"><?= $cant ?></span></td>
                                            <td class="text-contrast-danger"><?= mostrar_nombre_articulo($nombre, $descripciones) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Sobrantes -->
                <div class="tab-pane fade" id="sobrantes" role="tabpanel">
                    <?php if (empty($grouped['sobrantes'])): ?>
                        <p class="text-muted italic">No se detectaron artículos extras.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>CANT.</th>
                                        <th>DESCRIPCIÓN DEL ARTÍCULO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped['sobrantes'] as $nombre => $cant): ?>
                                        <tr>
                                            <td width="100"><span class="qty-badge"><?= $cant ?></span></td>
                                            <td class="text-contrast-warning"><?= mostrar_nombre_articulo($nombre, $descripciones) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>