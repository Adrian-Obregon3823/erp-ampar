<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/inventarioglobal.php");

$invGlobal = new InventarioGlobal();
$maletaId = isset($_GET['maleta_id']) ? (int)$_GET['maleta_id'] : 0;
$busqueda = $_GET['busqueda'] ?? '';
$grupo_linea_id = $_GET['grupo_linea_id'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';

if ($maletaId == 0) {
    echo '<p class="text-muted text-center">ID de maleta no válido.</p>';
    exit;
}

$articulos = $invGlobal->getArticulosByAlmacen($maletaId, $busqueda, $grupo_linea_id, $categoria_id);
?>

<?php if (is_array($articulos) && count($articulos) > 0): ?>
    <?php
    $totalFisica = 0;
    $totalTransito = 0;
    $totalGeneral = 0;
    foreach ($articulos as $row) {
        $totalFisica += (int)($row['CANTIDAD_FISICA'] ?? 0);
        $totalTransito += (int)($row['CANTIDAD_TRANSITO'] ?? 0);
        $totalGeneral += (int)($row['CANTIDAD_TOTAL'] ?? 0);
    }
    ?>
    <div class="maleta-detalle-wrap">
        <div class="maleta-detalle-resumen d-flex flex-wrap align-items-center mb-2">
            <span class="badge badge-light text-dark border mr-2 mb-2">Artículos: <strong><?= count($articulos) ?></strong></span>
            <span class="badge badge-primary text-white mr-2 mb-2">Total: <strong><?= $totalGeneral ?></strong></span>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-sm table-hover mb-0 maleta-detalle-table">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Referencia</th>
                        <th style="min-width:260px;">Artículo</th>
                        <th class="text-center" style="width:90px;">Existencia</th>
                        <th class="text-center" style="width:90px;">Tránsito</th>
                        <th class="text-center" style="width:80px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hoy = strtotime(date('Y-m-d'));
                    foreach ($articulos as $row):
                        $badgeExistencia = 'badge-secondary text-white';
                        $tituloCaducidad = 'Sin fecha de caducidad';

                        if (!empty($row['PROXIMA_CADUCIDAD'])) {
                            $fechaCad = strtotime($row['PROXIMA_CADUCIDAD']);
                            $tituloCaducidad = 'Caduca: ' . $row['PROXIMA_CADUCIDAD'];

                            if ($fechaCad !== false) {
                                $diffDays = ($fechaCad - $hoy) / (60 * 60 * 24);
                                if ($diffDays <= 30) {
                                    $badgeExistencia = 'badge-danger text-white';
                                } elseif ($diffDays <= 90) {
                                    $badgeExistencia = 'badge-warning text-dark';
                                } else {
                                    $badgeExistencia = 'badge-success text-white';
                                }
                            }
                        }
                    ?>
                        <tr class="fila-articulo-maleta" data-articulo-id="<?= (int)($row['STOCK_ARTICULOID'] ?? 0) ?>" title="Clic para ver lotes">
                            <td>
                                <div class="font-weight-bold text-dark"><?= htmlspecialchars($row['SKU'] ?? 'SIN CLAVE') ?></div>
                            </td>
                            <td>
                                <div class="maleta-articulo-nombre"><?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?></div>
                            </td>
                            <td class="text-center"><span class="badge <?= $badgeExistencia ?>" title="<?= htmlspecialchars($tituloCaducidad) ?>"><?= (int)($row['CANTIDAD_FISICA'] ?? 0) ?></span></td>
                            <td class="text-center"><span class="badge <?= ((int)($row['CANTIDAD_TRANSITO'] ?? 0) > 0) ? 'badge-warning text-dark' : 'badge-secondary text-white' ?>"><?= (int)($row['CANTIDAD_TRANSITO'] ?? 0) ?></span></td>
                            <td class="text-center"><strong><?= (int)($row['CANTIDAD_TOTAL'] ?? 0) ?></strong></td>
                        </tr>
                        <tr class="detalle-lotes-maleta-row" style="display:none;">
                            <td colspan="5" class="p-0 border-0 bg-light"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View (Cards) -->
        <div class="d-block d-md-none mt-2 px-2 pb-2">
            <?php foreach ($articulos as $row):
                $colorClass = 'status-gray';
                if (!empty($row['PROXIMA_CADUCIDAD'])) {
                    $fechaCad = strtotime($row['PROXIMA_CADUCIDAD']);
                    $diffDays = ($fechaCad - $hoy) / (60 * 60 * 24);
                    if ($diffDays <= 30) {
                        $colorClass = 'status-red';
                    } elseif ($diffDays <= 90) {
                        $colorClass = 'status-yellow';
                    } else {
                        $colorClass = 'status-green';
                    }
                }
            ?>
                <div class="mobile-articulo-card fila-articulo-maleta-mobile" data-articulo-id="<?= (int)($row['STOCK_ARTICULOID'] ?? 0) ?>">
                    <div class="mobile-articulo-header">
                        <div class="mobile-articulo-sku">
                            <div class="status-circle <?= $colorClass ?> mr-2"></div>
                            <?= htmlspecialchars($row['SKU'] ?? 'SIN CLAVE') ?>
                        </div>
                    </div>
                    <div class="mobile-articulo-desc">
                        <?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?>
                    </div>
                    <div class="mobile-articulo-body">
                        <div class="mobile-articulo-stat">
                            <span>Existencia</span>
                            <strong><span class="badge badge-success text-white" style="font-size: 0.9rem;"><?= (int)($row['CANTIDAD_FISICA'] ?? 0) ?></span></strong>
                        </div>
                        <div class="mobile-articulo-stat">
                            <span>Tránsito</span>
                            <strong><span class="badge <?= ((int)($row['CANTIDAD_TRANSITO'] ?? 0) > 0) ? 'badge-warning text-dark' : 'badge-secondary text-white' ?>" style="font-size: 0.9rem;"><?= (int)($row['CANTIDAD_TRANSITO'] ?? 0) ?></span></strong>
                        </div>
                        <div class="mobile-articulo-stat">
                            <span>Total</span>
                            <strong style="font-size: 1.2rem;"><?= (int)($row['CANTIDAD_TOTAL'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-4 text-muted maleta-detalle-vacio">
        <i class="mdi mdi-package-variant" style="font-size: 22px;"></i>
        <div class="mt-2">Esta maleta no tiene artículos registrados.</div>
    </div>
<?php endif; ?>