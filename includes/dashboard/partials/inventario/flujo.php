<div class="col-lg-7 grid-margin stretch-card">
  <div class="card card-rounded w-100 inv-panel">
    <div class="card-body">
      <h5 class="card-title">Material por Caducar (flujo de proceso)</h5>
      <div class="flow-grid">
        <?php foreach ($flujoEtapas as $etapa) {
          $porcentaje = $flujoTotal > 0 ? round(((int)$etapa['conteo'] / $flujoTotal) * 100, 1) : 0;
          $expandible = !empty($etapa['expandible']);
          $etapaKey = $etapa['key'] ?? '';
          $articulosEtapa = $articulosPorCaducidad[$etapaKey] ?? [];
        ?>
          <div>
            <div class="flow-item <?= $etapa['clase'] ?> <?= $expandible ? 'flow-clickable' : '' ?>" <?= $expandible ? 'data-etapa="' . htmlspecialchars($etapaKey) . '" id="flowBtn-' . htmlspecialchars($etapaKey) . '"' : '' ?> style="cursor: pointer;">
              <div class="flow-top">
                <div class="flow-stage"><?= htmlspecialchars($etapa['etapa']) ?></div>
                <div class="flow-count" id="flowCount-<?= htmlspecialchars($etapaKey) ?>"><?= number_format((int)$etapa['conteo']) ?></div>
              </div>
              <div class="flow-meta">
                <span><?= htmlspecialchars($etapa['accion']) ?></span>
                <span class="flow-priority"><?= htmlspecialchars($etapa['prioridad']) ?></span>
              </div>
              <div class="flow-progress">
                <span id="flowProgress-<?= htmlspecialchars($etapaKey) ?>" style="width: <?= $porcentaje ?>%;"></span>
              </div>
            </div>

            <div class="cad-exp-wrap etapa-<?= htmlspecialchars($etapaKey) ?>" id="panel-<?= htmlspecialchars($etapaKey) ?>" style="display:none; margin-top: 8px; margin-bottom: 12px;">
              <div class="cad-exp-title">Detalle de articulos - <?= htmlspecialchars($etapa['etapa']) ?></div>
              <?php if (!empty($articulosEtapa) && is_array($articulosEtapa)) { ?>
                <div class="inv-data-list" id="panelList-<?= htmlspecialchars($etapaKey) ?>">
                  <?php foreach ($articulosEtapa as $art) { ?>
                    <?php
                      $dias = (int)($art['DIAS_RESTANTES'] ?? 0);
                      $diasClase = 'days-ok';
                      if ($dias <= 0) {
                        $diasClase = 'days-danger';
                      } elseif ($dias <= 7) {
                        $diasClase = 'days-warn';
                      }

                      $articuloNombre = inv_display_title($art['NOMBRE'] ?? '');
                      $referencia = trim((string)($art['CLAVE_ARTICULO'] ?? ''));
                      $folio = trim((string)($art['FOLIO'] ?? ''));
                      $lote = trim((string)($art['LOTE'] ?? ''));
                      $almacenNombre = trim((string)($art['ALMACEN_NOMBRE'] ?? ''));
                      $caducidad = trim((string)($art['CADUCIDAD'] ?? ''));
                    ?>
                    <div class="inv-data-row">
                      <div class="inv-data-left">
                        <div class="inv-data-title" title="<?= htmlspecialchars($art['NOMBRE'] ?? '') ?>">
                          <?= htmlspecialchars($articuloNombre) ?>
                          <small>Ref: <?= htmlspecialchars($referencia) ?></small>
                        </div>
                        <div class="inv-data-meta">
                          <?php if ($lote !== '') { ?><span>Lote: <?= htmlspecialchars($lote) ?></span><?php } ?>
                          <?php if ($folio !== '') { ?><span>Folio: <?= htmlspecialchars($folio) ?></span><?php } ?>
                          <?php if ($almacenNombre !== '') { ?><span>Almacen: <?= htmlspecialchars($almacenNombre) ?></span><?php } ?>
                        </div>
                      </div>
                      <div class="inv-data-right">
                        <div class="inv-data-stack">
                          <div class="inv-data-label">Caducidad</div>
                          <div class="inv-data-value"><?= htmlspecialchars($caducidad) ?></div>
                        </div>
                        <div class="inv-data-stack">
                          <div class="inv-data-label">Dias</div>
                          <div class="inv-data-value"><span class="days-badge <?= $diasClase ?>"><?= $dias ?></span></div>
                        </div>
                      </div>
                    </div>
                  <?php } ?>
                </div>
              <?php } else { ?>
                <div class="inv-data-empty" id="panelList-<?= htmlspecialchars($etapaKey) ?>">No hay articulos en esta etapa.</div>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
