<?php
// includes/dashboard.ventas.php
?>
<div class="inv-header-wrap d-flex flex-wrap align-items-center justify-content-between mt-3">
    <div>
        <h4 class="inv-header-title mb-1">Dashboard de Ventas</h4>
        <div class="inv-header-sub">Tablero operativo de ingresos, servicios y rendimiento.</div>
    </div>
    <form id="ventasFilterForm" class="d-flex align-items-md-center flex-column flex-md-row inv-filter-box mt-3 mt-md-0">
        <div class="d-flex align-items-center mb-2 mb-md-0 mr-md-2 w-100 w-md-auto justify-content-between justify-content-md-start">
            <label for="ventas_almacen_id" class="mb-0 mr-2">Almacén:</label>
            <select id="ventas_almacen_id" name="ventas_almacen_id" class="form-control form-control-sm text-dark" style="min-width: 140px; flex: 1;">
                <?php if ($esAdmin): ?>
                <option value="global" <?= ((string)$almacenId === 'global') ? 'selected' : '' ?>>Global</option>
                <?php endif; ?>
                <?php if (!empty($listaAlmacenes)): ?>
                <?php foreach ($listaAlmacenes as $al): ?>
                    <?php $almacenOptionId = (string)((int)$al['ID']); ?>
                    <option value="<?= (int)$al['ID'] ?>" <?= ((string)$almacenId === $almacenOptionId) ? 'selected' : '' ?>><?= htmlspecialchars($al['NOMBRE']) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="d-flex align-items-center w-100 w-md-auto justify-content-between justify-content-md-start">
            <label for="ventas_periodo" class="mb-0 mr-2">Periodo:</label>
            <select id="ventas_periodo" name="ventas_periodo" class="form-control form-control-sm text-dark" style="min-width: 110px; flex: 1;">
                <option value="30" <?= $periodoDias === 30 ? 'selected' : '' ?>>30 días</option>
                <option value="60" <?= $periodoDias === 60 ? 'selected' : '' ?>>60 días</option>
                <option value="90" <?= $periodoDias === 90 ? 'selected' : '' ?>>90 días</option>
                <option value="180" <?= $periodoDias === 180 ? 'selected' : '' ?>>180 días</option>
                <option value="365" <?= $periodoDias === 365 ? 'selected' : '' ?>>365 días</option>
            </select>
        </div>
    </form>
</div>

<div class="row mt-4">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 12px; background: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">Ingresos Totales</h4>
                    <div class="icon-box" style="background: rgba(40, 167, 69, 0.1); color: #28a745; padding: 8px; border-radius: 8px;">
                        <i class="mdi mdi-currency-usd" style="font-size: 20px;"></i>
                    </div>
                </div>
                <h3 class="mb-0 font-weight-bold text-dark" id="kpi-ventas-total">$0.00</h3>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.8rem;">En el periodo seleccionado</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 12px; background: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">Remisiones</h4>
                    <div class="icon-box" style="background: rgba(0, 123, 255, 0.1); color: #007bff; padding: 8px; border-radius: 8px;">
                        <i class="mdi mdi-file-document-outline" style="font-size: 20px;"></i>
                    </div>
                </div>
                <h3 class="mb-0 font-weight-bold text-dark" id="kpi-ventas-count">0</h3>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.8rem;">Generadas en el periodo</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 12px; background: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">Ticket Promedio</h4>
                    <div class="icon-box" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8; padding: 8px; border-radius: 8px;">
                        <i class="mdi mdi-chart-line" style="font-size: 20px;"></i>
                    </div>
                </div>
                <h3 class="mb-0 font-weight-bold text-dark" id="kpi-ventas-promedio">$0.00</h3>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.8rem;">Ingreso por remisión</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h4 class="card-title text-dark mb-4" style="font-weight: 600;">Tendencia de Ingresos (Últimos 6 meses)</h4>
                <div style="height: 320px; width: 100%; position: relative;">
                    <canvas id="chart-ventas-tendencia"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h4 class="card-title text-dark mb-4" style="font-weight: 600;">Top 5 Artículos Más Vendidos</h4>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover table-borderless table-sm">
                        <thead style="border-bottom: 1px solid #eee;">
                            <tr>
                                <th class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 600;">Artículo</th>
                                <th class="text-right text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 600;">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody id="table-ventas-top-articulos">
                            <tr><td colspan="2" class="text-muted text-center py-4">Cargando datos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/dashboard.ventas.js?v=<?= time() ?>"></script>
