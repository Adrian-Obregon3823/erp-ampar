<?php 
include_once("../includes/sesion.php"); 
include_once("../includes/includes.php"); 

$reqId = isset($_GET['reqid']) ? base64_decode($_GET['reqid']) : 0;
$reqId = (int)$reqId;

if ($reqId <= 0) {
    echo "<div class='alert alert-danger'>Requerimiento inválido.</div>";
    exit;
}

$db = new FirebirdConnection(true);
$res = $db->query("
    SELECT R.REQMATERIAL_ALMACENID, R.REQMATERIAL_OBSERVACIONES, R.REQMATERIAL_STATUS 
    FROM AMPAR_HIS_REQUERIMIENTOMATERIAL R 
    WHERE R.REQMATERIAL_ID = ?
", [$reqId]);

if (empty($res)) {
    echo "<div class='alert alert-danger'>Requerimiento no encontrado.</div>";
    exit;
}

$reqData = $res[0];

// Obtener detalles
$det = $db->query("
    SELECT D.REQMATERIALDET_ARTICULOID, D.REQMATERIALDET_CANTIDAD, 
           X.CLAVE_ARTICULO, A.NOMBRE 
    FROM AMPAR_HIS_REQDET D
    JOIN ARTICULOS A ON A.ARTICULO_ID = D.REQMATERIALDET_ARTICULOID
    LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = A.ARTICULO_ID
    WHERE D.REQMATERIALDET_REQUERIMIENTOID = ?
", [$reqId]);
$db->close();
?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }
</style>
<div class="col-12">
    <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0;">
        <div class="card-body p-4">
            <form class="forms-sample" id="form-req-material">
                <input type="hidden" id="req_statusid" name="statusid" value="<?= $reqData['REQMATERIAL_STATUS'] ?>">
                <input type="hidden" id="req_id" name="reqid" value="<?= $reqId ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="req_almacen" class="font-weight-bold" style="color: #475569;">Almacén <span class="text-danger">*</span></label>
                        <select class="form-control" id="req_almacen" name="almacenid" required style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); background-color: #f8fafc;">
                            <option value="">Selecciona Almacén</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="req_observaciones" class="font-weight-bold" style="color: #475569;">Observaciones</label>
                        <textarea class="form-control" id="req_observaciones" name="observaciones" rows="2" maxlength="500" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);"><?= htmlspecialchars($reqData['REQMATERIAL_OBSERVACIONES'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr class="my-4" style="border-top: 1px dashed #cbd5e1;">

                <div class="row align-items-end mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="req_articulo" class="font-weight-bold" style="color: #475569;">Buscar Artículo <span class="text-danger">*</span></label>
                        <input type="hidden" id="req_idarticulo">
                        <input type="hidden" id="req_cvearticulo">
                        <input type="text" class="form-control" id="req_articulo" placeholder="Escribe clave o nombre del artículo..." style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="req_cantidad" class="font-weight-bold" style="color: #475569;">Cantidad</label>
                        <input type="number" step="1" min="1" class="form-control font-weight-bold text-center" id="req_cantidad" value="1" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); color: #0f172a;">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <button type="button" class="btn btn-warning w-100 shadow-sm font-weight-bold text-dark" id="btnAgregarReqArticulo" style="padding: 10px 0; border-radius: 8px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none;">
                            <i class="mdi mdi-plus"></i> Agregar
                        </button>
                    </div>
                </div>

                <div class="table-responsive shadow-sm mb-2" style="border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead style="background-color: #f8fafc; color: #334155; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 12px; font-weight: 600;">#</th>
                                <th style="padding: 12px; font-weight: 600;">Clave</th>
                                <th style="padding: 12px; font-weight: 600;">Artículo</th>
                                <th style="padding: 12px; font-weight: 600;">Cantidad</th>
                                <th style="padding: 12px; font-weight: 600;" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="reqArticulosBody" style="background-color: #ffffff;">
                            <?php if(empty($det)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4" id="reqRowEmptyPlaceholder" style="background-color: #f8fafc; font-style: italic;">No hay artículos agregados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($det as $idx => $d): ?>
                                <tr class="req-articulo-row">
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <input type="hidden" name="articuloid[]" value="<?= htmlspecialchars($d['REQMATERIALDET_ARTICULOID']) ?>">
                                        <?= htmlspecialchars($d['CLAVE_ARTICULO'] ?: 'S/K') ?>
                                    </td>
                                    <td><?= htmlspecialchars($d['NOMBRE']) ?></td>
                                    <td><input type="number" step="1" min="1" class="form-control form-control-sm" name="cantidad[]" value="<?= (int)$d['REQMATERIALDET_CANTIDAD'] ?>"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger btnQuitarReqArticulo">Quitar</button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-light mr-2 font-weight-bold" data-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                    <button type="button" class="btn btn-primary font-weight-bold shadow-sm" id="btnGuardarReqMaterial" style="border-radius: 8px;">
                        <i class="mdi mdi-content-save mr-1"></i> Actualizar Requerimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    let articulosCatalogo = [];
    let almacenSeleccionado = <?= json_encode($reqData['REQMATERIAL_ALMACENID']) ?>;

    function cargarAlmacenesReq() {
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            let html = "<option value=''>Selecciona Almacén</option>";
            if (Array.isArray(data)) {
                data.forEach(function(item) {
                    let selected = (item.ID == almacenSeleccionado) ? "selected" : "";
                    html += "<option value='" + item.ID + "' " + selected + ">" + item.NOMBRE + "</option>";
                });
            }
            $("#req_almacen").html(html);
        });
    }

    function cargarArticulosReq() {
        $.getJSON("../ajax/get.articulos.catalogo.php", function(data) {
            articulosCatalogo = data || [];
            if ($("#req_articulo").data("ui-autocomplete")) {
                $("#req_articulo").autocomplete("destroy");
            }
            $("#req_articulo").autocomplete({
                appendTo: "#modalglobal .modal-body",
                minLength: 1,
                source: function(request, response) {
                    const term = (request.term || "").toUpperCase();
                    const resultados = $.map(articulosCatalogo, function(item) {
                        const searchable = ((item.CLAVE_ARTICULO || "") + " " + (item.NOMBRE || "")).toUpperCase();
                        if (searchable.indexOf(term) !== -1) {
                            return {
                                label: "(" + (item.CLAVE_ARTICULO || "S/K") + ") " + (item.NOMBRE || ""),
                                value: item.NOMBRE || "",
                                data: item
                            };
                        }
                        return null;
                    }).filter(Boolean);
                    response(resultados);
                },
                select: function(_, ui) {
                    const item = ui.item.data;
                    $("#req_articulo").val(item.NOMBRE || "");
                    $("#req_idarticulo").val(item.ID || "");
                    $("#req_cvearticulo").val(item.CLAVE_ARTICULO || "");
                    return false;
                }
            });
        });
    }

    function agregarFilaReqArticulo() {
        const idArticulo = $("#req_idarticulo").val();
        const clave = $("#req_cvearticulo").val();
        const nombre = $("#req_articulo").val();
        const cantidad = parseInt($("#req_cantidad").val() || 0, 10);

        if (!idArticulo || !nombre) {
            Swal.fire({ icon: 'warning', text: 'Selecciona un artículo válido de la lista.' });
            return;
        }
        if (cantidad <= 0) {
            Swal.fire({ icon: 'warning', text: 'La cantidad debe ser mayor a 0.' });
            return;
        }

        let duplicado = false;
        $("#reqArticulosBody input[name='articuloid[]']").each(function() {
            if ($(this).val() === idArticulo) {
                duplicado = true;
                return false;
            }
        });
        if (duplicado) {
            Swal.fire({ icon: 'warning', text: 'El artículo ya fue agregado.' });
            return;
        }

        $("#reqRowEmptyPlaceholder").closest("tr").remove();
        const rowCount = $("#reqArticulosBody tr.req-articulo-row").length + 1;
        const newRow = `
            <tr class="req-articulo-row">
                <td>${rowCount}</td>
                <td>
                    <input type="hidden" name="articuloid[]" value="${idArticulo}">
                    ${clave || 'S/K'}
                </td>
                <td>${nombre}</td>
                <td><input type="number" step="1" min="1" class="form-control form-control-sm" name="cantidad[]" value="${cantidad}"></td>
                <td><button type="button" class="btn btn-sm btn-danger btnQuitarReqArticulo">Quitar</button></td>
            </tr>
        `;
        $("#reqArticulosBody").append(newRow);
        $("#req_articulo").val("");
        $("#req_idarticulo").val("");
        $("#req_cvearticulo").val("");
        $("#req_cantidad").val("1");
    }

    $(document).ready(function() {
        cargarAlmacenesReq();
        cargarArticulosReq();

        $(document).on("click", ".btnQuitarReqArticulo", function() {
            $(this).closest("tr").remove();
            $("#reqArticulosBody tr.req-articulo-row").each(function(index) {
                $(this).children("td").first().text(index + 1);
            });
            if ($("#reqArticulosBody tr.req-articulo-row").length === 0) {
                $("#reqArticulosBody").html('<tr><td colspan="5" class="text-center text-muted py-3" id="reqRowEmptyPlaceholder">No hay artículos agregados</td></tr>');
            }
        });

        $("#btnAgregarReqArticulo").on("click", function() {
            agregarFilaReqArticulo();
        });

        $("#btnGuardarReqMaterial").on("click", function() {
            if (!$("#req_almacen").val()) {
                Swal.fire({ icon: 'warning', text: 'Debes seleccionar un almacén.' });
                return;
            }
            if ($("#reqArticulosBody tr.req-articulo-row").length === 0) {
                Swal.fire({ icon: 'warning', text: 'Debes agregar al menos un artículo.' });
                return;
            }

            const formData = new FormData(document.getElementById('form-req-material'));
            $.ajax({
                url: '../ajax/requerimientosmaterial.actualizar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(resp) {
                    if (resp && resp.ok) {
                        Swal.fire({
                            icon: 'success',
                            html: (resp.msg || 'Requerimiento actualizado.') + '<br><b>Folio: ' + (resp.folio || '') + '</b>'
                        }).then(function() {
                            $('#modalglobal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'warning', text: (resp && resp.msg) ? resp.msg : 'No se pudo guardar el requerimiento.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', text: 'Error de servidor al guardar el requerimiento.' });
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        });
    });
})();
</script>