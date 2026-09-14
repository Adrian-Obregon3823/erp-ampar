<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$proyectoid = isset($_GET['proyectoid']) ? intval(base64_decode($_GET['proyectoid'])) : 0;
$proyectos = new proyectos();
$res = $proyectos->getproyectobyid($proyectoid);
if (!$res || count($res) === 0) {
    echo "<div class='alert alert-danger'>Proyecto no encontrado</div>";
    exit;
}
$p = $res[0];

$db = new FirebirdConnection(true);
// Obtener lista de responsables (usuarios del sistema)
$usuarios = $db->query("SELECT USUARIO_ID AS ID, USUARIO_NOMBRE AS NOMBRE FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ACTIVO = 1 ORDER BY USUARIO_NOMBRE");
// Monedas para sub-modal de cliente
$monedas = $db->query("SELECT MONEDA_ID AS ID, NOMBRE FROM MONEDAS ORDER BY NOMBRE");
$db->close();
?>

<div class="col-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Editar Proyecto (<?= $p['PROYECTO_FOLIO'] ?>)</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background:none; border:none; font-size:1.5rem;">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-proyecto">
                <input type="hidden" id="proyectoid" name="proyectoid" value="<?= $p['PROYECTO_ID'] ?>">
                <input type="hidden" id="esucalmacen" name="esucalmacen" value="<?= $p['PROYECTO_SUCURSALID'] ?>">

                <div class="form-group">
                    <label>Almacén del Proyecto</label>
                    <input type="text" class="form-control" value="<?= htmlentities($p['SUCURSAL_NOMBRE'] ?? '') ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="edescripcion">Descripción / Concepto <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="edescripcion" name="edescripcion" placeholder="Descripción del proyecto" maxlength="1000" required><?= htmlentities($p['PROYECTO_CONCEPTO'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="eclienteid" class="mb-0">Cliente <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-outline-primary btn-xs text-nowrap" data-toggle="modal" data-target="#mdlCrearCliente">
                            + Nuevo Cliente
                        </button>
                    </div>
                    <select class="form-control select2-cliente" id="eclienteid" name="eclienteid" style="width: 100%;" required>
                        <?php if ($p['PROYECTO_CLIENTEID']): ?>
                            <option value="<?= $p['PROYECTO_CLIENTEID'] ?>" selected><?= htmlentities($p['CLIENTE_NOMBRE'] ?? '') ?></option>
                        <?php else: ?>
                            <option value="">Busca y selecciona un cliente...</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="proyecto_tipo">Tipo de Proyecto <span class="text-danger">*</span></label>
                            <select class="form-control" id="proyecto_tipo" name="proyecto_tipo" required>
                                <option value="1" <?= $p['PROYECTO_TIPO'] == 1 ? 'selected' : '' ?>>Consignación (Artículos)</option>
                                <option value="2" <?= $p['PROYECTO_TIPO'] == 2 ? 'selected' : '' ?>>Renta (Solo Equipo Capital)</option>
                                <option value="3" <?= $p['PROYECTO_TIPO'] == 3 ? 'selected' : '' ?>>Comodato (Artículos y Equipo Capital)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="eresponsableid">Responsable <span class="text-danger">*</span></label>
                            <select class="form-control" id="eresponsableid" name="eresponsableid" required>
                                <option value=""></option>
                                <?php foreach (($usuarios ?: []) as $u): ?>
                                    <option value="<?= $u['ID'] ?>" <?= $p['PROYECTO_RESPONSABLEID'] == $u['ID'] ? 'selected' : '' ?>><?= htmlentities($u['NOMBRE']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="eplazo">Plazo de Cumplimiento <span class="text-danger">*</span></label>
                            <select class="form-control" id="eplazo" name="eplazo" required>
                                <option value="TODO" <?= (!isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) || $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == '' || $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'TODO') ? 'selected' : '' ?>>Histórico (Total acumulado)</option>
                                <option value="SEMANA" <?= (isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) && $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'SEMANA') ? 'selected' : '' ?>>Semanal</option>
                                <option value="MES" <?= (isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) && $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'MES') ? 'selected' : '' ?>>Mensual</option>
                                <option value="BIMENSUAL" <?= (isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) && $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'BIMENSUAL') ? 'selected' : '' ?>>Bimensual</option>
                                <option value="TRIMESTRAL" <?= (isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) && $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'TRIMESTRAL') ? 'selected' : '' ?>>Trimestral</option>
                                <option value="SEMESTRAL" <?= (isset($p['PROYECTO_PLAZO_CUMPLIMIENTO']) && $p['PROYECTO_PLAZO_CUMPLIMIENTO'] == 'SEMESTRAL') ? 'selected' : '' ?>>Semestral</option>
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="cumplimiento" name="cumplimiento" value="<?= (int)($p['PROYECTO_CUMPLIMIENTO'] ?? 0) ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="efechai">Fecha Inicio <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="efechai" name="efechai" value="<?= date('Y-m-d\TH:i', strtotime($p['PROYECTO_FECHAI'])) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="efechaf">Fecha Fin <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="efechaf" name="efechaf" value="<?= date('Y-m-d\TH:i', strtotime($p['PROYECTO_FECHAF'])) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row align-items-end mb-3">
                    <div class="col-12">
                        <label for="earticulo_auto">Buscar y Agregar Artículo / Equipo Capital</label>
                        <input type="text" class="form-control" id="earticulo_auto" placeholder="Escribe el nombre, clave o folio del artículo/equipo...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Artículo / Equipo</th>
                                <th>Tipo</th>
                                <th width="140px">Meta Cumplimiento</th>
                                <th width="100px">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="emaletasBody">
                            <?php foreach (($p['_MALETAS'] ?: []) as $maleta): 
                                $badgeClass = trim($maleta['TIPO']) === 'EQUIPO' ? 'badge-info' : 'badge-primary';
                                $badgeText = trim($maleta['TIPO']) === 'EQUIPO' ? 'Equipo Capital' : 'Artículo';
                            ?>
                                <tr>
                                    <td><?= htmlentities($maleta['ALMACEN_FOLIO'] ?: 'S/F') ?></td>
                                    <td>
                                        <input type="hidden" name="emaletaid[]" value="<?= $maleta['MALETA_ID'] ?>">
                                        <strong><?= htmlentities($maleta['ALMACEN_NOMBRE']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm val-cumplimiento" name="cumplimiento_art[<?= $maleta['MALETA_ID'] ?>]" min="1" value="<?= (int)($maleta['CUMPLIMIENTO'] ?? 1) ?>" onchange="recalcularMetaGlobal();" required style="width:100px;">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); recalcularMetaGlobal();">Quitar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-right">
                    <button type="button" class="btn btn-success" onclick="guardarEdicionProyecto();">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Crear Nuevo Cliente Inline -->
<div class="modal fade" id="mdlCrearCliente" tabindex="-1" role="dialog" aria-labelledby="mdlCrearClienteLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nuevo Cliente</h5>
                <button type="button" class="close" onclick="$('#mdlCrearCliente').modal('hide');" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-nuevo-cliente">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cli_nombre">Razón Social <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cli_nombre" name="NOMBRE_COMERCIAL" required>
                    </div>
                    <div class="form-group">
                        <label for="cli_contacto">Nombre de Contacto</label>
                        <input type="text" class="form-control" id="cli_contacto" name="NOMBRE_CONTACTO">
                    </div>
                    <div class="form-group">
                        <label for="cli_telefono">Teléfono</label>
                        <input type="text" class="form-control" id="cli_telefono" name="TELEFONO">
                    </div>
                    <div class="form-group">
                        <label for="cli_correo">Correo</label>
                        <input type="email" class="form-control" id="cli_correo" name="CORREO">
                    </div>
                    <div class="form-group">
                        <label for="cli_ubicacion">Ubicación / Dirección</label>
                        <input type="text" class="form-control" id="cli_ubicacion" name="UBICACION">
                    </div>
                    <div class="form-group">
                        <label for="cli_moneda">Moneda <span class="text-danger">*</span></label>
                        <select class="form-control" id="cli_moneda" name="MONEDA_ID" required>
                            <?php foreach (($monedas ?: []) as $m): ?>
                                <option value="<?= $m['ID'] ?>" <?= $m['ID'] == 1 ? 'selected' : '' ?>><?= htmlentities($m['NOMBRE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="guardarClienteInline();">Guardar Cliente</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#mdlCrearCliente').modal('hide');">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Mover modal al body y limpiar duplicados previos
        $('body > #mdlCrearCliente').remove();
        $('#mdlCrearCliente').appendTo('body');

        // Inicializar select2 para clientes
        initClienteSelect2();

        // Al cambiar tipo de proyecto, limpiar artículos agregados
        $("#proyecto_tipo").change(function() {
            $("#emaletasBody").empty();
            $("#earticulo_auto").val("");
            recalcularMetaGlobal();
        });

        // Autocomplete de Artículos / Equipo Capital
        $("#earticulo_auto").autocomplete({
            source: function(request, response) {
                const tipoProj = $("#proyecto_tipo").val();
                const clienteId = $("#eclienteid").val() || '';
                
                $.getJSON("../ajax/proyectos.buscar_items.php", {
                    proyecto_tipo: tipoProj,
                    clienteid: clienteId,
                    q: request.term
                }, function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: (item.FOLIO ? '[' + item.FOLIO + '] ' : '') + item.ARTICULO_NOMBRE.toUpperCase() + (item.CLAVE_ARTICULO ? ' (' + item.CLAVE_ARTICULO + ')' : ''),
                            value: '',
                            raw: item
                        };
                    }));
                });
            },
            minLength: 2,
            select: function(event, ui) {
                agregarArticuloATabla(ui.item.raw);
            }
        });

        recalcularMetaGlobal();
    });

    function agregarArticuloATabla(item) {
        var yaExiste = false;
        $("input[name='emaletaid[]']").each(function() {
            if ($(this).val() == item.ID) {
                yaExiste = true;
                return false;
            }
        });

        if (yaExiste) {
            Swal.fire("Atención", "Ya se agregó este artículo.", "warning");
            return;
        }

        var badgeClass = item.TIPO === 'EQUIPO' ? 'badge-info' : 'badge-primary';
        var badgeText = item.TIPO === 'EQUIPO' ? 'Equipo Capital' : 'Artículo';
        
        var fila = `
            <tr>
                <td>${item.FOLIO || 'S/F'}</td>
                <td>
                    <input type="hidden" name="emaletaid[]" value="${item.ID}">
                    <strong>${item.ARTICULO_NOMBRE}</strong>
                    ${item.CLAVE_ARTICULO ? '<br><small class="text-muted">Clave: ' + item.CLAVE_ARTICULO + '</small>' : ''}
                </td>
                <td>
                    <span class="badge ${badgeClass}">${badgeText}</span>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm val-cumplimiento" name="cumplimiento_art[${item.ID}]" min="1" value="1" onchange="recalcularMetaGlobal();" required style="width:100px;">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); recalcularMetaGlobal();">Quitar</button>
                </td>
            </tr>
        `;
        $("#emaletasBody").append(fila);
        recalcularMetaGlobal();
    }

    function recalcularMetaGlobal() {
        var total = 0;
        $(".val-cumplimiento").each(function() {
            total += parseInt($(this).val()) || 0;
        });
        $("#cumplimiento").val(total);
    }

    function initClienteSelect2() {
        $('#eclienteid').select2({
            placeholder: 'Busca y selecciona un cliente...',
            dropdownParent: $('#modalglobal'),
            width: '100%',
            ajax: {
                url: '../ajax/precios.buscar_clientes.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '*'
                    };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data.items || [], function (item) {
                            return {
                                id: item.CLIENTE_ID,
                                text: item.NOMBRE
                            }
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });
    }

    function guardarClienteInline() {
        var form = document.getElementById("form-nuevo-cliente");
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var formData = new FormData(form);
        formData.append("action", "save");
        formData.append("cat", "clientes");
        formData.append("id", "0");

        $.ajax({
            url: '../api/catalogos.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(resp) {
                if (resp && resp.ok) {
                    Swal.fire("Cliente creado", resp.msg || "Cliente creado con éxito", "success");
                    $('#mdlCrearCliente').modal('hide');
                    
                    // Agregar y pre-seleccionar el cliente en Select2
                    var newOption = new Option(resp.msg || $('#cli_nombre').val().toUpperCase(), resp.data.CLIENTE_ID, true, true);
                    $('#eclienteid').append(newOption).trigger('change');
                    
                    // Resetear formulario
                    form.reset();
                } else {
                    Swal.fire("Error", resp.msg || "Error al crear cliente", "error");
                }
            },
            error: function(xhr, status, error) {
                Swal.fire("Error", "Error de comunicación con el servidor", "error");
            }
        });
    }

    function guardarEdicionProyecto() {
        var form = document.getElementById("form-editar-proyecto");
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if ($("input[name='emaletaid[]']").length === 0) {
            Swal.fire("Error", "Debes agregar al menos un artículo o equipo capital", "warning");
            return;
        }

        var formData = new FormData(form);

        $.ajax({
            url: '../ajax/proyectos.editar.php',
            type: 'POST',
            data: formData,
            dataType: 'html',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(response) {
                if (response.trim() === "") {
                    Swal.fire({
                        text: "Proyecto actualizado con éxito",
                        icon: "success",
                        customClass: { confirmButton: 'btn btn-success' }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        text: response,
                        icon: "warning",
                        customClass: { confirmButton: 'btn btn-success' }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            },
            complete: function() {
                $("#loading").hide();
            }
        });
    }
</script>
