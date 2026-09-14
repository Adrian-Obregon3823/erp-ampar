<div class="col-lg-5 grid-margin stretch-card">
  <div class="card card-rounded w-100 inv-panel">
    <div class="card-body">
      <h5 class="card-title">Productos proximos a caducar</h5>
      <div class="top-exp-summary">
        <span class="top-exp-chip" id="chipVentana">Ventana: proximos <?= number_format($periodoDias) ?> dias</span>
        <span class="top-exp-chip" id="chipArticulos">Articulos: <?= number_format($topCaducidadArticulos) ?></span>
        <span class="top-exp-chip" id="chipUnidades">Unidades que vencen ese dia: <?= number_format($topCaducidadUnidadesDia) ?></span>
      </div>
      <?php if (!empty($topCaducidad)) { ?>
        <div class="inv-data-list" id="topCaducidadList">
          <?php foreach ($topCaducidad as $row) { ?>
            <?php
              $dias = (int)($row['DIAS_RESTANTES'] ?? 0);
              $diasClase = 'days-ok';
              if ($dias <= 7) {
                $diasClase = 'days-danger';
              } elseif ($dias <= 30) {
                $diasClase = 'days-warn';
              }

              $articuloNombre = inv_display_title($row['ARTICULO_NOMBRE'] ?? '');
              $referencia = trim((string)($row['SKU'] ?? ''));
              $cantidad = (int)($row['CANTIDAD'] ?? 0);
              $almacenNombre = trim((string)($row['ALMACEN_NOMBRE'] ?? ''));
              $stockIdsAllRaw = trim((string)($row['STOCK_IDS'] ?? ''));
              $stockIdsAll = $stockIdsAllRaw !== '' ? array_filter(array_map('trim', explode(',', $stockIdsAllRaw))) : [];
              $puedeAccion = !empty($row['PUEDE_ACCION']);
              $stockIdsAccionablesRaw = trim((string)($row['STOCK_IDS_ACCIONABLES'] ?? ''));
              $stockIdsAccionables = [];
              $stockIdsAccionablesMap = [];
              $stockItems = [];

              if ($puedeAccion && $stockIdsAccionablesRaw !== '') {
                foreach (explode(',', $stockIdsAccionablesRaw) as $stockRow) {
                  $stockParts = explode('|', $stockRow);
                  $stockId = (int)($stockParts[0] ?? 0);
                  if ($stockId > 0) {
                    $stockIdsAccionables[] = $stockId;
                    $stockIdsAccionablesMap[$stockId] = true;
                  }
                }
              }

              foreach ($stockIdsAll as $stockRow) {
                $stockParts = explode('|', $stockRow);
                $stockId = (int)($stockParts[0] ?? 0);
                if ($stockId <= 0) {
                  continue;
                }

                $stockItems[] = [
                  'id' => $stockId,
                  'almacen' => $stockParts[1] ?? 'Almacén Principal',
                  'folio' => $stockParts[2] ?? 'S/F',
                  'lote' => $stockParts[3] ?? 'S/L',
                  'puedeAccion' => isset($stockIdsAccionablesMap[$stockId]),
                ];
              }

              $accionBtn = '';
              if ($puedeAccion && !empty($stockIdsAccionables)) {
                if (count($stockIdsAccionables) === 1) {
                  $accionBtn = '<div style="margin-top:8px;"><button class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:11px; font-weight:bold;" onclick="window.iniciarAccionInteligente(' . (int)$stockIdsAccionables[0] . ', this); event.stopPropagation();">&#9889; Iniciar Acción</button></div>';
                } else {
                  $accionBtn = '<div style="margin-top:8px;"><button class="btn btn-sm btn-primary py-1 px-2" style="font-size:11px; font-weight:bold; box-shadow: 0 2px 4px rgba(0,0,0,0.15); color: #ffffff !important;" onclick="window.iniciarAccionInteligenteBulk(\'' . htmlspecialchars(implode(',', $stockIdsAccionables), ENT_QUOTES) . '\', this); event.stopPropagation();">&#9889; Iniciar Acción</button></div>';
                }
              }
              $debeExpandir = count($stockIdsAll) > 1;
            ?>
            <?php if ($debeExpandir) { ?>
              <div class="inv-accordion-group" style="margin-bottom: 15px;">
                <div class="inv-data-row" style="cursor: pointer; border: 1px solid #cbd5e1; box-shadow: 0 3px 6px rgba(0,0,0,0.06); margin-bottom: 0; background: #ffffff;" onclick="this.classList.toggle('active'); var body = this.nextElementSibling; if (body) { body.style.display = body.style.display === 'none' ? 'block' : 'none'; }">
                  <div class="inv-data-left">
                    <div class="inv-data-title" title="<?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?>">
                      <?= htmlspecialchars($articuloNombre) ?>
                      <small>Ref: <?= htmlspecialchars($referencia) ?></small>
                    </div>
                    <div class="inv-data-meta">
                      <span>Caducidad cercana</span>
                      <?php if ($almacenNombre !== '') { ?><span>Almacen: <?= htmlspecialchars($almacenNombre) ?></span><?php } ?>
                    </div>
                    <?= $accionBtn ?>
                  </div>
                  <div class="inv-data-right">
                    <div class="inv-data-stack">
                      <div class="inv-data-label">Cantidad</div>
                      <div class="inv-data-value"><?= number_format($cantidad) ?></div>
                    </div>
                    <div class="inv-data-stack">
                      <div class="inv-data-label">Dias</div>
                      <div class="inv-data-value"><span class="days-badge <?= $diasClase ?>"><?= $dias ?></span></div>
                    </div>
                  </div>
                </div>
                <div class="inv-accordion-body" style="display:none; margin-left: 36px; border-left: 2px solid #cbd5e1; padding-left: 26px; margin-top: 10px; padding-bottom: 2px;">
                  <?php foreach ($stockItems as $stockItem) { ?>
                    <?php
                      $actionBtnIndiv = '';
                      if (!empty($stockItem['puedeAccion'])) {
                        $actionBtnIndiv = '<button class="btn py-0 px-2" style="font-size:11.5px; font-weight:bold; background: #f1f5f9; border: none; text-decoration: none !important; color: #1d4ed8; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0 16px; border-radius: 20px; outline: none; height: 28px; box-sizing: border-box;" onclick="window.iniciarAccionInteligente(' . (int)$stockItem['id'] . ', this); event.stopPropagation();"><span style="font-size: 13px; display: flex; align-items: center; margin-top: -1px; margin-left: 2px;">&#9889;</span> <span>Acción Indiv.</span></button>';
                      }
                    ?>
                    <div style="margin-bottom: 8px; padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; box-shadow: 0 1px 2px rgba(0,0,0,0.02); cursor: default; display: flex; justify-content: space-between; align-items: center;" onclick="event.stopPropagation();">
                      <div style="display: flex; flex-direction: column; justify-content: center;">
                        <span style="font-size: 13.5px; color: #1e293b; font-weight: bold; margin-bottom: 2px;"><?= htmlspecialchars($stockItem['almacen']) ?></span>
                        <span style="font-size: 11px; color: #94a3b8;">Lote: <span style="color: #64748b;"><?= htmlspecialchars($stockItem['lote']) ?></span> &nbsp;&bull;&nbsp; Folio: <span style="color: #64748b;"><?= htmlspecialchars($stockItem['folio']) ?></span></span>
                      </div>
                      <div><?= $actionBtnIndiv ?></div>
                    </div>
                  <?php } ?>
                </div>
              </div>
            <?php } else { ?>
              <div class="inv-data-row">
                <div class="inv-data-left">
                  <div class="inv-data-title" title="<?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?>">
                    <?= htmlspecialchars($articuloNombre) ?>
                    <small>Ref: <?= htmlspecialchars($referencia) ?></small>
                  </div>
                  <div class="inv-data-meta">
                    <span>Caducidad cercana</span>
                    <?php if ($almacenNombre !== '') { ?><span>Almacen: <?= htmlspecialchars($almacenNombre) ?></span><?php } ?>
                  </div>
                  <?= $accionBtn ?>
                </div>
                <div class="inv-data-right">
                  <div class="inv-data-stack">
                    <div class="inv-data-label">Cantidad</div>
                    <div class="inv-data-value"><?= number_format($cantidad) ?></div>
                  </div>
                  <div class="inv-data-stack">
                    <div class="inv-data-label">Dias</div>
                    <div class="inv-data-value"><span class="days-badge <?= $diasClase ?>"><?= $dias ?></span></div>
                  </div>
                </div>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="inv-data-empty" id="topCaducidadList">Sin registros en ventana de 6 meses</div>
      <?php } ?>
    </div>
  </div>
</div>
