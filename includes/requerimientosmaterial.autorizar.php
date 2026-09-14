<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$reqId = base64_decode($_GET['reqid'] ?? '');
$rm = new requerimientosmaterial();
$res = $rm->getrequerimientobyid($reqId);
if (!$res || $res == 0) {
    echo '<div class="alert alert-warning">No se encontró el requerimiento.</div>';
    return;
}

$header = $res[0];

// Obtener sugerencias inteligentes
$sugerencias = $rm->obtenerSugerenciasAlmacenTraspaso($reqId);
$jsonSugerencias = json_encode($sugerencias);

// Obtener inventario de todos los almacenes para cada artículo para poblar los select
$db = new FirebirdConnection(true);
$sqlStock = "
    SELECT S.STOCK_ARTICULOID, A.ALMACEN_ID, A.ALMACEN_NOMBRE, COUNT(S.STOCK_ID) AS STOCK_DISP
    FROM AMPAR_HIS_STOCK S
    JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
    WHERE S.STOCK_STOCKSTATUSID = 1 AND A.ALMACEN_ID <> ?
    GROUP BY S.STOCK_ARTICULOID, A.ALMACEN_ID, A.ALMACEN_NOMBRE
";
$stockData = $db->query($sqlStock, [$header['REQMATERIAL_ALMACENID']]);
$db->close();

$stockPorArticulo = [];
if ($stockData) {
    foreach ($stockData as $s) {
        $stockPorArticulo[$s['STOCK_ARTICULOID']][] = $s;
    }
}
?>
<div class="col-12">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Autorizar Requerimiento <strong><?= htmlspecialchars($header['REQMATERIAL_FOLIO'] ?? '') ?></strong></h4>
        </div>
        <div class="card-body p-3">

            <!-- Sugerencia de Traspaso Automático -->
            <?php if (!empty($sugerencias)): ?>
                <?php $sug = $sugerencias[0]; // opción óptima única ?>
                <div class="alert border-0 shadow-sm mb-4" style="border-radius: 10px; background-color: #f0fdf4; border-left: 4px solid #16a34a !important; color: #14532d; padding: 16px;">
                    <h6 class="font-weight-bold mb-2" style="font-size: 1rem;"><i class="mdi mdi-lightbulb-on text-success mr-1"></i> Sugerencia Óptima de Traspaso</h6>
                    <p class="small mb-3" style="font-size: 0.85rem; color: #166534;">El sistema analizó rotación, caducidad y mínimos para elegir los mejores lotes. La tabla inferior ya está pre-llenada. Puedes revisar los detalles y aplicarla:</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0 small" style="color: #14532d; width: 100%;">
                            <thead>
                                <tr class="border-bottom" style="border-color: #bbf7d0 !important;">
                                    <th style="padding: 8px 0; font-weight: 700;">Artículo</th>
                                    <th style="padding: 8px 0; font-weight: 700;">Almacén Origen</th>
                                    <th style="padding: 8px 0; font-weight: 700;">Lotes Seleccionados</th>
                                    <th style="padding: 8px 0; font-weight: 700;" class="text-center">Traspaso / OC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sug['articulos'] as $artSug): ?>
                                    <tr class="border-bottom" style="border-color: #bbf7d0 !important;">
                                        <td class="font-weight-bold align-top" style="padding: 8px 0; color: #166534;"><?= htmlspecialchars($artSug['articulo_nombre']) ?></td>
                                        <td class="align-top" style="padding: 8px 0; color: #15803d;"><?= htmlspecialchars($artSug['almacen_origen_nombre'] ?? 'N/A') ?></td>
                                        <td class="align-top" style="padding: 8px 0;">
                                            <?php if (!empty($artSug['lotes_desc'])): ?>
                                                <?= $artSug['lotes_desc'] ?>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.78rem;">Sin stock disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-top" style="padding: 8px 0; white-space: nowrap;">
                                            <?php if ($artSug['cantidad_a_transferir'] > 0): ?>
                                                <span class="badge badge-info text-white" style="padding: 4px 8px; font-size: 0.75rem;"><i class="mdi mdi-swap-horizontal"></i> <?= (int)$artSug['cantidad_a_transferir'] ?> traspaso</span>
                                            <?php endif; ?>
                                            <?php if ($artSug['cantidad_faltante_oc'] > 0): ?>
                                                <span class="badge badge-warning text-white" style="padding: 4px 8px; font-size: 0.75rem; margin-top: 2px; display: inline-block;"><i class="mdi mdi-file-document"></i> <?= (int)$artSug['cantidad_faltante_oc'] ?> OC</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-right">
                        <button type="button" class="btn btn-success btn-sm font-weight-bold shadow-sm btn-aplicar-sug-especifica" data-idx="0"
                            style="border-radius: 8px; padding: 7px 18px; background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); border: none;">
                            <i class="mdi mdi-check"></i> Aplicar Sugerencia Óptima
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert border-0 shadow-sm mb-4" style="border-radius: 10px; background-color: #fffbeb; border-left: 4px solid #f59e0b !important; color: #78350f; padding: 16px;">
                    <h6 class="font-weight-bold mb-2" style="font-size: 1rem;"><i class="mdi mdi-alert-circle-outline text-warning mr-1"></i> Sin opciones de traspaso</h6>
                    <p class="small mb-0" style="font-size: 0.85rem; color: #92400e;">No se detectó stock disponible en otros almacenes para cubrir este requerimiento. Los artículos deberán ser surtidos completamente mediante <strong>Órdenes de Compra (OC)</strong>.</p>
                </div>
            <?php endif; ?>

            <form id="form-autorizar-req">
                <input type="hidden" name="req_id" value="<?= $reqId ?>">
                <div class="table-responsive shadow-sm mb-4" style="border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <table class="table table-sm mb-0 font-weight-normal" style="border-collapse: collapse; width: 100%;">
                        <thead style="background-color: #f8fafc; color: #334155; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 12px; font-weight: 600;">Clave</th>
                                <th style="padding: 12px; font-weight: 600;">Artículo</th>
                                <th class="text-center" width="80px" style="padding: 12px; font-weight: 600;">Req.</th>
                                <th class="text-center" width="100px" style="padding: 12px; font-weight: 600;">Cant. a OC</th>
                                <th class="text-center" width="100px" style="padding: 12px; font-weight: 600;">Cant. a Traspaso</th>
                                <th class="text-center" width="220px" style="padding: 12px; font-weight: 600;">Almacén Origen (Traspaso)</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #ffffff;">
                            <?php foreach ($res as $index => $item):
                                $artId = $item['REQMATERIALDET_ARTICULOID'];
                                $reqQty = (int)$item['REQMATERIALDET_CANTIDAD'];
                                $dispAlmacenes = $stockPorArticulo[$artId] ?? [];
                            ?>
                                <tr class="border-bottom" style="border-color: #f1f5f9;">
                                    <td class="align-middle" style="padding: 12px; color: #475569; font-size: 0.85rem;"><?= htmlspecialchars($item['CLAVE_ARTICULO'] ?? 'S/K') ?></td>
                                    <td class="align-middle font-weight-bold" style="padding: 12px; color: #1e293b; font-size: 0.85rem;">
                                        <?= htmlspecialchars($item['ARTICULO_NOMBRE']) ?>
                                        <input type="hidden" name="articulos[<?= $index ?>][id]" value="<?= $artId ?>">
                                    </td>
                                    <td class="align-middle" style="padding: 12px;">
                                        <input type="number" class="form-control form-control-sm text-center cant-req input-qty font-weight-bold" 
                                               name="articulos[<?= $index ?>][requerido]" 
                                               value="<?= $reqQty ?>" min="0" data-idx="<?= $index ?>"
                                               style="border-radius: 6px; border: 1px solid #93c5fd; background-color: #eff6ff; color: #1e40af;">
                                    </td>
                                    <td class="align-middle" style="padding: 12px;">
                                        <input type="number" class="form-control form-control-sm text-center cant-oc input-qty font-weight-bold"
                                            name="articulos[<?= $index ?>][cant_oc]"
                                            value="<?= $reqQty ?>" min="0" max="<?= $reqQty ?>" data-idx="<?= $index ?>"
                                            style="border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); color: #0f172a;">
                                    </td>
                                    <td class="align-middle" style="padding: 12px;">
                                        <input type="number" class="form-control form-control-sm text-center cant-traspaso input-qty font-weight-bold"
                                            name="articulos[<?= $index ?>][cant_traspaso]"
                                            value="0" min="0" max="<?= $reqQty ?>" data-idx="<?= $index ?>"
                                            style="border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); color: #0f172a;">
                                    </td>
                                    <td class="align-middle" style="padding: 12px;">
                                        <select class="form-control form-control-sm select-almacen" name="articulos[<?= $index ?>][almacen_origen]"
                                            style="border-radius: 6px; border: 1px solid #cbd5e1; background-color: #f8fafc; color: #334155;">
                                            <option value="">Sin traspaso</option>
                                            <?php foreach ($dispAlmacenes as $alm): ?>
                                                <option value="<?= $alm['ALMACEN_ID'] ?>" data-disp="<?= $alm['STOCK_DISP'] ?>">
                                                    <?= htmlspecialchars($alm['ALMACEN_NOMBRE']) ?> (Disp: <?= $alm['STOCK_DISP'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end align-items-center mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-danger font-weight-bold mr-3" id="btn-rechazar-req" style="border-radius: 8px; min-width: 120px;">
                        <i class="mdi mdi-close-circle"></i> Rechazar
                    </button>
                    <button type="button" class="btn btn-primary font-weight-bold shadow-sm" id="btn-procesar-autorizacion" style="border-radius: 8px; min-width: 200px;">
                        <i class="mdi mdi-check-circle"></i> Procesar Autorización
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const reqId = <?= json_encode($reqId) ?>;
        
        // Rechazar Requerimiento
        $('#btn-rechazar-req').on('click', function() {
            Swal.fire({
                target: document.getElementById('modalglobal') || document.body,
                input: 'textarea',
                inputPlaceholder: 'Escribe el motivo del rechazo...',
                inputAttributes: {
                    style: 'min-height:110px; border:1px solid #d1d5db; border-radius:8px; padding:10px; font-size:0.9rem; resize:vertical; width:100%; box-sizing:border-box;'
                },
                html: '<p style="text-align:left; color:#374151; font-size:0.95rem; margin:0 0 4px 0;">Por favor justifique detalladamente el motivo del rechazo:</p>',
                showCancelButton: true,
                confirmButtonText: 'Sí, Rechazar Solicitud',
                cancelButtonText: 'Regresar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Debes ingresar un motivo para rechazar.';
                    }
                },
                didOpen: () => {
                    // Fix z-index para que el textarea sea interactivo dentro del modal Bootstrap
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '999999';

                    // Header rojo edge-to-edge
                    const popup = Swal.getPopup();
                    popup.style.setProperty('padding', '0', 'important');
                    popup.style.overflow = 'hidden';

                    if (!popup.querySelector('.swal-red-header')) {
                        const hdr = document.createElement('div');
                        hdr.className = 'swal-red-header';
                        hdr.style.cssText = [
                            'background: #d32f2f',
                            'color: #fff',
                            'padding: 16px 24px',
                            'display: flex',
                            'align-items: center',
                            'gap: 10px',
                            'width: 100%',
                            'box-sizing: border-box',
                            'margin-bottom: 0'
                        ].join(';');
                        hdr.innerHTML = '<i class="mdi mdi-alert-circle" style="font-size:1.3rem;"></i><b style="font-size:1.05rem;">Rechazar Solicitud</b>';
                        popup.insertBefore(hdr, popup.firstChild);
                    }

                    // Agregar padding al contenido html y al input
                    const htmlEl = Swal.getHtmlContainer();
                    if (htmlEl) htmlEl.style.cssText = 'padding: 18px 24px 0; margin: 0; text-align:left;';

                    const inputEl = Swal.getInput();
                    if (inputEl) {
                        inputEl.style.margin = '0 24px 0';
                        inputEl.style.width = 'calc(100% - 48px)';
                    }

                    const actionsEl = Swal.getActions();
                    if (actionsEl) actionsEl.style.padding = '12px 24px 20px';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const motivo = result.value.trim();
                    $('#btn-rechazar-req').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Procesando...');
                    $.ajax({
                        url: '../ajax/requerimientosmaterial.rechazar.php',
                        type: 'POST',
                        data: { req_id: reqId, motivo: motivo },
                        dataType: 'json',
                        success: function(res) {
                            if (res.ok) {
                                Swal.fire({ title: '¡Rechazado!', text: res.msg, icon: 'success' })
                                    .then(() => { $('#modalglobal').modal('hide'); location.reload(); });
                            } else {
                                Swal.fire('Error', res.msg || 'No se pudo rechazar el requerimiento.', 'error');
                                $('#btn-rechazar-req').prop('disabled', false).html('<i class="mdi mdi-close-circle"></i> Rechazar');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                            $('#btn-rechazar-req').prop('disabled', false).html('<i class="mdi mdi-close-circle"></i> Rechazar');
                        }
                    });
                }
            });
        });

        // ───────────────────────────────────────────
        // Helpers de recálculo por fila
        // ───────────────────────────────────────────
        function recalcularFila($row) {
            const idx      = $row.find('.cant-traspaso').data('idx');
            const reqQty   = Math.max(0, parseInt($row.find('.cant-req').val()) || 0);
            const $trasInput = $row.find('.cant-traspaso');
            const $ocInput   = $row.find('.cant-oc');
            const $selectAlm = $row.find('.select-almacen');

            // Respetar el cap del almacén seleccionado
            const dispAlm  = parseInt($selectAlm.find(':selected').data('disp')) || 0;
            const maxTras  = $selectAlm.val() ? Math.min(reqQty, dispAlm) : 0;

            let tras = Math.min(Math.max(0, parseInt($trasInput.val()) || 0), maxTras, reqQty);
            let oc   = Math.max(0, reqQty - tras);

            $trasInput.val(tras);
            $ocInput.val(oc);

            if (tras > 0 && $selectAlm.val()) {
                $selectAlm.prop('disabled', false);
            } else {
                $selectAlm.prop('disabled', !$selectAlm.val()).val($selectAlm.val() || '');
                if (tras === 0) {
                    $selectAlm.prop('disabled', true).val('');
                }
            }
        }

        // Cuando cambia Req — redistribuir manteniendo la proporción de traspaso actual
        $('.cant-req').on('input', function() {
            const $row    = $(this).closest('tr');
            const reqNew  = Math.max(0, parseInt($(this).val()) || 0);
            const $trasInput = $row.find('.cant-traspaso');
            const $selectAlm = $row.find('.select-almacen');
            const dispAlm    = parseInt($selectAlm.find(':selected').data('disp')) || 0;
            const maxTras    = $selectAlm.val() ? Math.min(reqNew, dispAlm) : 0;

            // Tratar de mantener el traspaso, pero nunca mayor que Req ni que disponible
            let tras = Math.min(parseInt($trasInput.val()) || 0, maxTras, reqNew);
            $trasInput.val(tras);
            $row.find('.cant-oc').val(Math.max(0, reqNew - tras));

            if (tras > 0 && $selectAlm.val()) {
                $selectAlm.prop('disabled', false);
            } else if (tras === 0) {
                $selectAlm.prop('disabled', true).val('');
            }
        });

        // Cuando cambia Cant. a OC
        $('.cant-oc').on('input', function() {
            const $row   = $(this).closest('tr');
            const reqQty = Math.max(0, parseInt($row.find('.cant-req').val()) || 0);
            let oc       = Math.min(Math.max(0, parseInt($(this).val()) || 0), reqQty);
            $(this).val(oc);

            const $trasInput = $row.find('.cant-traspaso');
            const $selectAlm = $row.find('.select-almacen');
            const dispAlm    = parseInt($selectAlm.find(':selected').data('disp')) || 0;
            const maxTras    = $selectAlm.val() ? Math.min(reqQty - oc, dispAlm) : 0;

            let tras = Math.min(reqQty - oc, maxTras);
            $trasInput.val(tras);

            if (tras > 0 && $selectAlm.val()) {
                $selectAlm.prop('disabled', false);
            } else if (tras === 0) {
                $selectAlm.prop('disabled', true).val('');
            }
        });

        // Cuando cambia Cant. a Traspaso
        $('.cant-traspaso').on('input', function() {
            const $row   = $(this).closest('tr');
            const reqQty = Math.max(0, parseInt($row.find('.cant-req').val()) || 0);
            const $selectAlm = $row.find('.select-almacen');
            const dispAlm    = parseInt($selectAlm.find(':selected').data('disp')) || 0;
            const maxTras    = $selectAlm.val() ? Math.min(reqQty, dispAlm) : 0;

            let tras = Math.min(Math.max(0, parseInt($(this).val()) || 0), maxTras, reqQty);
            $(this).val(tras);
            $row.find('.cant-oc').val(Math.max(0, reqQty - tras));

            if (tras > 0 && $selectAlm.val()) {
                $selectAlm.prop('disabled', false);
            } else if (tras === 0) {
                $selectAlm.prop('disabled', true).val('');
            }
        });

        // Cuando cambia el almacén de origen — ajustar traspaso al disponible de ese almacén
        $('.select-almacen').on('change', function() {
            const $row    = $(this).closest('tr');
            const reqQty  = Math.max(0, parseInt($row.find('.cant-req').val()) || 0);
            const dispAlm = parseInt($(this).find(':selected').data('disp')) || 0;

            if (!$(this).val()) {
                // Sin almacén: traspaso = 0, todo a OC
                $row.find('.cant-traspaso').val(0);
                $row.find('.cant-oc').val(reqQty);
                $(this).prop('disabled', true).val('');
                return;
            }

            // Limitar traspaso al disponible en el nuevo almacén
            const currentTras = parseInt($row.find('.cant-traspaso').val()) || 0;
            const newTras = Math.min(currentTras > 0 ? currentTras : reqQty, dispAlm, reqQty);
            $row.find('.cant-traspaso').val(newTras);
            $row.find('.cant-oc').val(Math.max(0, reqQty - newTras));
            $(this).prop('disabled', false);
        });

        const sugerencias = <?= $jsonSugerencias ?: '[]' ?>;

        function aplicarSugerenciaIdx(idxSug, isAuto = false) {
            if (!sugerencias || sugerencias.length <= idxSug) return;

            const laSugerencia = sugerencias[idxSug];

            // Primero limpiar todo: 0 traspaso, todo a OC
            $('.cant-oc').each(function() {
                const idx = $(this).data('idx');
                const reqQty = parseInt($('input[name="articulos[' + idx + '][requerido]"]').val());
                $(this).val(reqQty);
                $('.cant-traspaso[data-idx="' + idx + '"]').val(0);
                $('.select-almacen[name="articulos[' + idx + '][almacen_origen]"]').val('').prop('disabled', true);
            });

            // Aplicar cada artículo de la sugerencia (cada uno puede tener su propio almacén)
            laSugerencia.articulos.forEach(function(artSug) {
                const artId         = artSug.articulo_id;
                const cantTraspasar = parseInt(artSug.cantidad_a_transferir);
                const origenId      = artSug.almacen_origen_id;

                $('input[name$="[id]"][value="' + artId + '"]').each(function() {
                    const $row  = $(this).closest('tr');
                    const idx   = $row.find('.cant-traspaso').data('idx');
                    const reqQty = parseInt($('input[name="articulos[' + idx + '][requerido]"]').val());

                    if (cantTraspasar > 0 && origenId > 0) {
                        const finalTras = Math.min(cantTraspasar, reqQty);
                        const finalOC   = reqQty - finalTras;

                        $row.find('.cant-traspaso').val(finalTras);
                        $row.find('.cant-oc').val(finalOC);
                        $row.find('.select-almacen').val(origenId).prop('disabled', false);
                    }
                });
            });

            if (!isAuto) {
                Swal.fire({
                    title: 'Sugerencia Aplicada',
                    text: 'Se asignaron los lotes óptimos seleccionados por el algoritmo.',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4500
                });
            }
        }

        if (sugerencias && sugerencias.length > 0) {
            aplicarSugerenciaIdx(0, true);
        }

        $('.btn-aplicar-sug-especifica').on('click', function() {
            const idx = $(this).data('idx');
            aplicarSugerenciaIdx(idx, false);
        });

        $('#btn-procesar-autorizacion').on('click', function() {
            // Validaciones previas
            let isValid = true;
            let errMsg = '';

            $('#form-autorizar-req tbody tr').each(function() {
                const trasQty = parseInt($(this).find('.cant-traspaso').val()) || 0;
                const selectVal = $(this).find('.select-almacen').val();
                const artName = $(this).find('td:eq(1)').text().trim();

                if (trasQty > 0 && !selectVal) {
                    isValid = false;
                    errMsg = 'Debes seleccionar un Almacén de Origen para el traspaso del artículo: ' + artName;
                    return false; // break loop
                }
            });

            if (!isValid) {
                Swal.fire('Atención', errMsg, 'warning');
                return;
            }

            const formData = $('#form-autorizar-req').serialize();

            Swal.fire({
                title: '¿Procesar Autorización?',
                text: 'Se crearán las Órdenes de Compra y Traspasos con las cantidades especificadas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, procesar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading').show();
                    $.post('../ajax/requerimientosmaterial.autorizar.php', formData, function(r) {
                        $('#loading').hide();
                        if (r.ok) {
                            Swal.fire({
                                title: '¡Autorizado!',
                                html: r.msg,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', r.msg || 'Error al autorizar', 'error');
                        }
                    }, 'json').fail(function() {
                        $('#loading').hide();
                        Swal.fire('Error', 'Error de conexión', 'error');
                    });
                }
            });
        });
    });
</script>