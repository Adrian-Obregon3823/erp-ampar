<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

if (empty($_GET['ocid'])) {
    echo '<div class="alert alert-warning m-2">Falta ID de Orden de Compra</div>';
    exit;
}

$ocid = base64_decode($_GET['ocid']);
$oc = new oc();
$res = $oc->getocbyid($ocid);

if (empty($res)) {
    echo '<div class="alert alert-danger m-2">No se encontró la Orden de Compra.</div>';
    exit;
}

$r = $res[0];

// Calculate fallback values if header is old
$subtotalHeader = $r['OC_SUBTOTAL'];
$descuentoHeader = $r['OC_DESCUENTO'];
$ivaHeader = $r['OC_IMPUESTOS'];
$totalHeader = $r['OC_TOTAL'];

$hasHeaderCalculations = ($subtotalHeader !== null);
?>
<div class="container-fluid p-2">
    <!-- Header Summary Card -->
    <div class="card mb-4 border-0 shadow-xs" style="background-color: #f8f9fa;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <span class="text-muted d-block small">Folio</span>
                    <strong style="font-size: 1.2em;"><?= htmlspecialchars($r['OC_FOLIO'] ?? '') ?></strong>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted d-block small">Estatus</span>
                    <span class="badge" style="color: #fff; background-color: #<?= $r['STATUS_COLOR'] ?>; font-size: 0.9em; font-weight: bold;">
                        <?= htmlspecialchars($r['STATUS_NOMBRE'] ?? '') ?>
                    </span>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted d-block small">Fecha</span>
                    <strong><?= htmlspecialchars($r['OC_FECHA'] ?? '') ?></strong>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted d-block small">Destino</span>
                    <strong><?= htmlspecialchars(($r['ALMACEN_NOMBRE'] ? $r['ALMACEN_NOMBRE'] . ' (' . $r['SUCURSAL_NOMBRE'] . ')' : $r['SUCURSAL_NOMBRE']) ?? '') ?></strong>
                </div>
            </div>
            <div class="row mt-3 border-top pt-2">
                <div class="col-md-6">
                    <span class="text-muted d-block small">Proveedor</span>
                    <strong><?= htmlspecialchars($r['PROVEEDOR_NOMBRE'] ?? 'N/A') ?></strong>
                </div>
                <?php if ($r['OC_EVENTOID']): ?>
                    <div class="col-md-6">
                        <span class="text-muted d-block small">Evento Vinculado</span>
                        <strong>Folio: <?= htmlspecialchars($r['EVENTO_FOLIO'] ?? $r['OC_EVENTOID']) ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (((int)$r['OC_STATUS'] == 6 || (int)$r['OC_STATUS'] == 1) && !empty($r['OC_MOTIVORECHAZO'])): ?>
                    <div class="col-md-6 mt-2">
                        <span class="text-danger d-block small font-weight-bold"><i class="mdi mdi-alert-circle"></i> Motivo de Rechazo</span>
                        <strong class="text-danger"><?= htmlspecialchars($r['OC_MOTIVORECHAZO'] ?? '') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Items Details Table -->
    <h5 class="font-weight-bold mb-3"><i class="mdi mdi-format-list-bulleted mr-1 text-primary"></i> Artículos de la Orden</h5>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>#</th>
                    <th>Clave</th>
                    <th>Artículo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Costo Unit.</th>
                    <th class="text-center">Desc. %</th>
                    <th class="text-center">IVA</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Total</th>
                    <?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
                        <th class="text-center"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                $calcSubtotal = 0;
                $calcDescuento = 0;
                $calcIva = 0;
                $calcTotal = 0;

                foreach ($res as $re) {
                    $cant = (float)$re['OCDET_CANTIDAD'];
                    $precio = (float)$re['OCDET_PRECIO'];

                    $descPct = isset($re['OCDET_DESCUENTO_PCT']) ? (float)$re['OCDET_DESCUENTO_PCT'] : 0.00;
                    $ivaPct = isset($re['OCDET_IVA_PCT']) ? (float)$re['OCDET_IVA_PCT'] : 16.00;

                    if (isset($re['OCDET_SUBTOTAL']) && $re['OCDET_SUBTOTAL'] !== null) {
                        $itemSubtotal = (float)$re['OCDET_SUBTOTAL'];
                        $itemTotal = (float)$re['OCDET_TOTAL'];
                        $itemIva = $itemTotal - $itemSubtotal;
                        $itemDesc = ($cant * $precio) * ($descPct / 100);
                    } else {
                        // Fallback
                        $itemSubtotal = $cant * $precio;
                        $itemDesc = 0;
                        $itemIva = $itemSubtotal * ($ivaPct / 100);
                        $itemTotal = $itemSubtotal + $itemIva;
                    }

                    $calcSubtotal += ($cant * $precio);
                    $calcDescuento += $itemDesc;
                    $calcIva += $itemIva;
                    $calcTotal += $itemTotal;
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($re['CLAVE_ARTICULO'] ?: 'S/K') ?></strong></td>
                        <td><?= htmlspecialchars($re['ARTICULO_NOMBRE'] ?? '') ?></td>
                        <?php $cantFormat = fmod($cant, 1) !== 0.0 ? number_format($cant, 2) : number_format($cant, 0); ?>
                        <td class="text-center">
                            <?= $cantFormat ?>
                            <?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
                                <button type="button" class="btn btn-sm btn-light btn-editar-cantidad-oc ml-1" data-id="<?= $re['OCDET_ID'] ?>" data-cantidad="<?= $cantFormat ?>" style="padding: 0.1rem 0.3rem;" title="Editar Cantidad">
                                    <i class="mdi mdi-pencil text-primary"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            $<?= number_format($precio, 2) ?>
                            <?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
                                <button type="button" class="btn btn-sm btn-light btn-editar-precio-oc ml-1" data-id="<?= $re['OCDET_ID'] ?>" data-artid="<?= $re['OCDET_ARTICULOID'] ?>" data-provid="<?= $r['OC_PROVEEDORID'] ?>" data-precio="<?= $precio ?>" style="padding: 0.1rem 0.3rem;" title="Editar Precio">
                                    <i class="mdi mdi-pencil text-primary"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= number_format($descPct, 2) ?>%</td>
                        <td class="text-center"><?= number_format($ivaPct, 2) ?>%</td>
                        <td class="text-end">$<?= number_format($itemSubtotal, 2) ?></td>
                        <td class="text-end font-weight-bold text-primary">$<?= number_format($itemTotal, 2) ?></td>
                        <?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-eliminar-item-oc" data-id="<?= $re['OCDET_ID'] ?>" style="padding: 0.1rem 0.3rem;" title="Eliminar">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
        <div class="text-right mb-2">
            <button type="button" class="btn btn-sm btn-info" id="btn-abrir-modal-agregar-oc">
                <i class="mdi mdi-plus"></i> Agregar Artículo
            </button>
        </div>
    <?php endif; ?>

    <!-- Summary Totals Section -->
    <div class="row justify-content-end mt-4">
        <div class="col-md-5">
            <div class="table-responsive">
                <table class="table table-borderless">
                    <?php
                    $finalSubtotal = $hasHeaderCalculations ? (float)$subtotalHeader : ($calcSubtotal - $calcDescuento);
                    $finalDescuento = $hasHeaderCalculations ? (float)$descuentoHeader : $calcDescuento;
                    $finalIva = $hasHeaderCalculations ? (float)$ivaHeader : $calcIva;
                    $finalTotal = $hasHeaderCalculations ? (float)$totalHeader : $calcTotal;
                    ?>
                    <tr>
                        <th class="py-1 text-end" style="width: 60%;">Subtotal:</th>
                        <td class="py-1 text-end font-weight-bold">$<?= number_format($finalSubtotal, 2) ?></td>
                    </tr>
                    <?php if ($finalDescuento > 0): ?>
                        <tr>
                            <th class="py-1 text-end text-danger">Desc. Global:</th>
                            <td class="py-1 text-end font-weight-bold text-danger">-$<?= number_format($finalDescuento, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="py-1 text-end">IVA (Impuestos):</th>
                        <td class="py-1 text-end font-weight-bold">$<?= number_format($finalIva, 2) ?></td>
                    </tr>
                    <tr class="border-top">
                        <th class="py-2 text-end text-primary" style="font-size: 1.1em;">Total Global:</th>
                        <td class="py-2 text-end font-weight-bold text-primary" style="font-size: 1.1em;">$<?= number_format($finalTotal, 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($r['OC_STATUS'] == 30 && isset($_GET['revisar']) && $_GET['revisar'] == 1): ?>
<div class="row mt-4 mb-2">
    <div class="col-12" style="text-align: right;">
        <hr>
        <button type="button" class="btn btn-success btn-autorizar-oc mr-2" data-id="<?= $r['OC_ID'] ?>">
            <i class="mdi mdi-check-circle"></i> Confirmar Orden Proveedor
        </button>
        <button type="button" class="btn btn-danger btn-rechazar-oc" data-id="<?= $r['OC_ID'] ?>">
            <i class="mdi mdi-close-circle"></i> Rechazar Orden
        </button>
    </div>
</div>
<?php endif; ?>

<?php if ($r['OC_STATUS'] != 5 && $r['OC_STATUS'] != 3): // Si no está cancelada ni finalizada ?>
<div class="row mt-4 mb-2">
    <div class="col-12" style="text-align: right;">
        <?php if (!($r['OC_STATUS'] == 30 && isset($_GET['revisar']) && $_GET['revisar'] == 1)): // Evitar doble HR ?>
            <hr>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-danger btn-cancelar-oc" data-id="<?= $r['OC_ID'] ?>">
            <i class="mdi mdi-cancel"></i> Cancelar Orden
        </button>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('.btn-cancelar-oc').on('click', function(e) {
        e.preventDefault();
        const ocId = $(this).data('id');
        Swal.fire({
            title: '¿Cancelar Orden de Compra?',
            text: 'Esta acción cambiará el estatus a Cancelada. ¿Estás seguro?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, Cancelar',
            cancelButtonText: 'No, Volver'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../ajax/oc.changestatus.php', {
                    ocid: ocId,
                    status: 5 // 5 = Cancelada
                }, function(r) {
                    if (r.ok) {
                        Swal.fire('¡Cancelada!', 'La Orden de Compra ha sido cancelada.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', r.msg || 'No se pudo cancelar.', 'error');
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                });
            }
        });
    });
});
</script>

<?php if ((int)$r['OC_STATUS'] == 1 || (int)$r['OC_STATUS'] == 6): ?>
<script>
$(document).ready(function() {
    $('.btn-editar-precio-oc').on('click', function(e) {
        e.preventDefault();
        const detId = $(this).data('id');
        const artId = $(this).data('artid');
        const provId = $(this).data('provid');
        const precioActual = $(this).data('precio');
        const ocId = <?= $ocid ?>;

        // Fix para que Bootstrap no bloquee el input de SweetAlert
        if ($.fn.modal && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
        }
        $('#modalglobal').removeAttr('tabindex');

        Swal.fire({
            target: document.getElementById('modalglobal'),
            title: 'Modificar Precio',
            html: `
                <div class="form-group text-left">
                    <label><strong>Nuevo Precio Unitario ($)</strong></label>
                    <input type="number" step="any" min="0" id="swal-precio-nuevo" class="form-control" value="${precioActual}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nuevoPrecio = parseFloat(document.getElementById('swal-precio-nuevo').value);
                if (!nuevoPrecio || nuevoPrecio <= 0) {
                    Swal.showValidationMessage('Ingresa un precio válido mayor a 0');
                    return false;
                }
                return { nuevoPrecio: nuevoPrecio };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const nuevoPrecio = result.value.nuevoPrecio;
                
                $.post('../ajax/oc.editar.precio.php', {
                    ocid: ocId,
                    detid: detId,
                    artid: artId,
                    provid: provId,
                    precio: nuevoPrecio
                }, function(r) {
                    if (r.ok) {
                        Swal.fire('¡Actualizado!', 'El precio ha sido modificado exitosamente.', 'success').then(() => {
                            const modal = $('#modalglobal');
                            modal.find('.modal-body').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>');
                            $.get('../includes/oc.detalle.php?ocid=' + btoa(ocId), function(html) {
                                modal.find('.modal-body').html(html);
                            });
                        });
                    } else {
                        if (r.bloqueado) {
                            Swal.fire({
                                title: 'Modificación Bloqueada',
                                html: r.msg,
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            Swal.fire('Error', r.msg || 'No se pudo modificar el precio.', 'error');
                        }
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                });
            }
        });
    });

    $('.btn-editar-cantidad-oc').on('click', function(e) {
        e.preventDefault();
        const detId = $(this).data('id');
        const cantidadActual = $(this).data('cantidad');
        const ocId = <?= $ocid ?>;

        if ($.fn.modal && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
        }
        $('#modalglobal').removeAttr('tabindex');

        Swal.fire({
            target: document.getElementById('modalglobal'),
            title: 'Modificar Cantidad',
            html: `
                <div class="form-group text-left">
                    <label><strong>Nueva Cantidad</strong></label>
                    <input type="number" step="any" min="0" id="swal-cantidad-nueva" class="form-control" value="${cantidadActual}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nuevaCantidad = parseFloat(document.getElementById('swal-cantidad-nueva').value);
                if (!nuevaCantidad || nuevaCantidad <= 0) {
                    Swal.showValidationMessage('Ingresa una cantidad válida mayor a 0');
                    return false;
                }
                return { nuevaCantidad: nuevaCantidad };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const nuevaCantidad = result.value.nuevaCantidad;
                
                $.post('../ajax/oc.editar.cantidad.php', {
                    ocid: ocId,
                    detid: detId,
                    cantidad: nuevaCantidad
                }, function(r) {
                    if (r.ok) {
                        Swal.fire('¡Actualizado!', 'La cantidad ha sido modificada exitosamente.', 'success').then(() => {
                            const modal = $('#modalglobal');
                            modal.find('.modal-body').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>');
                            $.get('../includes/oc.detalle.php?ocid=' + btoa(ocId), function(html) {
                                modal.find('.modal-body').html(html);
                            });
                        });
                    } else {
                        Swal.fire('Error', r.msg || 'No se pudo modificar la cantidad.', 'error');
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                });
            }
        });
    });

    $('.btn-eliminar-item-oc').on('click', function(e) {
        e.preventDefault();
        const detId = $(this).data('id');
        const ocId = <?= $ocid ?>;

        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará el artículo de la orden de compra y recalculará los totales.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../ajax/oc.eliminar.item.php', {
                    ocid: ocId,
                    detid: detId
                }, function(r) {
                    if (r.ok) {
                        Swal.fire('¡Eliminado!', 'El artículo ha sido removido.', 'success').then(() => {
                            const modal = $('#modalglobal');
                            modal.find('.modal-body').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>');
                            $.get('../includes/oc.detalle.php?ocid=' + btoa(ocId), function(html) {
                                modal.find('.modal-body').html(html);
                            });
                        });
                    } else {
                        Swal.fire('Error', r.msg || 'No se pudo eliminar el artículo.', 'error');
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                });
            }
        });
    });

    $('#btn-abrir-modal-agregar-oc').on('click', function(e) {
        e.preventDefault();
        
        // Disable enforceFocus for autocomplete inside swal
        if ($.fn.modal && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
        }
        $('#modalglobal').removeAttr('tabindex');

        Swal.fire({
            target: document.getElementById('modalglobal'),
            title: 'Agregar Artículo',
            html: `
                <div class="form-group text-left mb-2">
                    <label><strong>Buscar Artículo</strong></label>
                    <input type="text" id="swal-agregar-articulo" class="form-control" placeholder="Escribe nombre o clave...">
                    <input type="hidden" id="swal-agregar-artid">
                </div>
                <div class="row text-left">
                    <div class="col-6 form-group">
                        <label><strong>Cantidad</strong></label>
                        <input type="number" step="any" min="0" id="swal-agregar-cantidad" class="form-control" value="1">
                    </div>
                    <div class="col-6 form-group">
                        <label><strong>Costo Unit.</strong></label>
                        <input type="number" step="0.01" min="0" id="swal-agregar-precio" class="form-control" value="0.00">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const inputArt = $("#swal-agregar-articulo");
                const inputId = $("#swal-agregar-artid");
                const inputCost = $("#swal-agregar-precio");

                inputArt.autocomplete({
                    appendTo: ".swal2-container",
                    minLength: 2,
                    source: function(request, response) {
                        $.getJSON("../ajax/get.articulos.catalogo.php", function(data) {
                            const term = (request.term || "").toUpperCase();
                            const resultados = $.map(data, function(item) {
                                const searchable = ((item.CLAVE_ARTICULO || "") + " " + (item.NOMBRE || "")).toUpperCase();
                                if (searchable.indexOf(term) !== -1) {
                                    return {
                                        label: "(" + (item.CLAVE_ARTICULO || "S/K") + ") " + (item.NOMBRE || ""),
                                        value: item.NOMBRE || "",
                                        data: item
                                    };
                                }
                                return null;
                            });
                            response(resultados.slice(0, 15)); // limit results
                        });
                    },
                    select: function(_, ui) {
                        const item = ui.item.data;
                        inputArt.val(item.NOMBRE || "");
                        inputId.val(item.ID || "");

                        // Consultar precio base
                        $.getJSON("../ajax/get.articulo.cost_info.php", {
                            articulo_id: item.ID
                        }, function(res) {
                            if (res && res.status === 'success') {
                                inputCost.val(parseFloat(res.cost).toFixed(2));
                            }
                        });

                        return false;
                    }
                });
            },
            preConfirm: () => {
                const artId = document.getElementById('swal-agregar-artid').value;
                const cant = parseFloat(document.getElementById('swal-agregar-cantidad').value);
                const prec = parseFloat(document.getElementById('swal-agregar-precio').value);

                if (!artId) {
                    Swal.showValidationMessage('Debes buscar y seleccionar un artículo válido del catálogo.');
                    return false;
                }
                if (!cant || cant <= 0) {
                    Swal.showValidationMessage('Ingresa una cantidad válida mayor a 0');
                    return false;
                }
                return { artid: artId, cantidad: cant, precio: prec };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const ocId = <?= $ocid ?>;
                $.post('../ajax/oc.agregar.item.php', {
                    ocid: ocId,
                    artid: result.value.artid,
                    cantidad: result.value.cantidad,
                    precio: result.value.precio
                }, function(r) {
                    if (r.ok) {
                        Swal.fire('¡Agregado!', 'El artículo ha sido agregado a la Orden de Compra.', 'success').then(() => {
                            const modal = $('#modalglobal');
                            modal.find('.modal-body').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>');
                            $.get('../includes/oc.detalle.php?ocid=' + btoa(ocId), function(html) {
                                modal.find('.modal-body').html(html);
                            });
                        });
                    } else {
                        Swal.fire('Error', r.msg || 'No se pudo agregar el artículo.', 'error');
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Error de conexión al servidor.', 'error');
                });
            }
        });
    });
});
</script>
<?php endif; ?>
