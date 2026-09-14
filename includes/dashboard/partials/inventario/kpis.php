<div class="row g-3">
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Tasa de Rotacion de Inventario</div>
      <div class="inv-kpi-value" id="kpiTasaRotacion"><?= number_format($tasaRotacion, 2) ?>x</div>
      <div class="inv-kpi-sub">Salidas / inventario promedio del periodo.</div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Dias de Inventario Disponible</div>
      <div class="inv-kpi-value" id="kpiDiasInventario"><?= number_format($diasInventario, 1) ?></div>
      <div class="inv-kpi-sub">Stock fisico actual / consumo diario.</div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Precision de Registro</div>
      <div class="inv-kpi-value" id="kpiPrecisionRegistro"><?= number_format($precisionRegistro, 1) ?>%</div>
      <div class="inv-kpi-sub" id="kpiPrecisionSub">Fisico: <?= number_format($stockFisico) ?> | Registrado: <?= number_format($stockRegistrado) ?></div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Inventario Obsoleto / Lento</div>
      <div class="inv-kpi-value" id="kpiObsoleto"><?= number_format($unidadesObsoletas) ?></div>
      <div class="inv-kpi-sub" id="kpiObsoletoSub">Unidades sin salida en <?= number_format($periodoDias) ?> dias. Articulos: <?= number_format($articulosObsoletos) ?>.</div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Indice de Rotura de Stock</div>
      <div class="inv-kpi-value" id="kpiRoturaStock"><?= number_format($roturaStock, 1) ?>%</div>
      <div class="inv-kpi-sub" id="kpiRoturaSub">Solicitudes sin stock: <?= number_format($solicitudesSinStock) ?> de <?= number_format($solicitudesPeriodo) ?>.</div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="inv-kpi-card">
      <div class="inv-kpi-title">Stock</div>
      <div class="inv-kpi-value" id="kpiStock"><?= number_format($stockRegistrado) ?></div>
      <div class="inv-kpi-sub" id="kpiStockSub">Fisico: <?= number_format($stockFisico) ?> | Transito: <?= number_format($stockTransito) ?>. Articulos: <?= number_format($totalArticulos) ?>.</div>
    </div>
  </div>
</div>
