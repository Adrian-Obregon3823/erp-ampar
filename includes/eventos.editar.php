<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$eventoid = base64_decode($_GET['eventoid']);
$eventos = new eventos();
$res = $eventos->geteventobyid($eventoid);
$result2 = $eventos->getproveedoresbyeventoid($eventoid);
$nombrePacienteVal = !empty($res[0]['NOMBRE']) ? $res[0]['NOMBRE'] : (!empty($res[0]['EVENTO_NOMBREPARTICULAR']) ? $res[0]['EVENTO_NOMBREPARTICULAR'] : ($res[0]['EVENTO_CLIENTEID'] ?? ''));

// Equipos capital ya asignados al evento (IDs negativos en AMPAR_HIS_EVENTOSMALETAS)
$dbEC = new FirebirdConnection();
$equiposAsignados = $dbEC->query("
    SELECT EM.EVENTOMALETA_ID, EC.EQUIPOCAPITAL_ID, EC.FOLIO,
           AR.NOMBRE AS ARTICULO_NOMBRE, EC.REFERENCIA, COALESCE(EC.UBICACION, 'SIN UBICACION') AS UBICACION
    FROM AMPAR_HIS_EVENTOSMALETAS EM
    INNER JOIN AMPAR_EQUIPOCAPITAL EC ON EC.EQUIPOCAPITAL_ID = ABS(EM.EVENTOMALETA_MALETAID)
    INNER JOIN ARTICULOS AR ON AR.ARTICULO_ID = EC.ARTICULO_ID
    WHERE EM.EVENTOMALETA_EVENTOID = " . (int)$eventoid . "
      AND EM.EVENTOMALETA_MALETAID < 0
      AND EC.ESTATUS = 'A'
    ORDER BY AR.NOMBRE
");
$dbEC->close();
$equiposAsignados = is_array($equiposAsignados) ? $equiposAsignados : [];

// Nombre e ID del almacén del evento para cargar EC disponibles en el select
$almacenIdEvento   = $res[0]['EVENTO_ALMACENID'] ?? '';
$almacenNomEvento  = $res[0]['EVENTO_ALMACEN_NOMBRE'] ?? '';
$clienteIdEvento   = $res[0]['EVENTO_CLIENTEID'] ?? '';
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
    <div class="card">
        <div class="card-header">
            <h4>Editar Evento</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nuevo-evento">
                <ul class="nav nav-tabs" id="eventoTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#tab-general" role="tab">General</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="proveedor-tab" data-toggle="tab" href="#tab-proveedor" role="tab">Proveedor</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row mt-3">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="ealmacen">Almacén <span class="text-danger">*</span></label>
                                        <div class="row">
                                            <input type="hidden" value="<?= $eventoid ?>" id="eventoid" name="eventoid">
                                            <input type="hidden" value="<?= !empty($res[0]['EVENTO_SUCURSALID']) ? $res[0]['EVENTO_SUCURSALID'] : $res[0]['SUCURSAL_ID'] ?>" id="esucalmacen" name="esucalmacen">
                                            <div class="col-12 col-md-8 mb-2 mb-md-0">
                                                <select id="ealmacen" name="ealmacen" class="form-control" disabled>
                                                    <option value="<?= $res[0]['EVENTO_ALMACENID'] ?>"><?= $res[0]['EVENTO_ALMACEN_NOMBRE'] ?? 'Almacén de ' . $res[0]['SUCURSAL_NOMBRE'] ?></option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <button type="button" id="cambiarAlmacen" class="btn btn-warning w-100">Cambiar Almacén</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="etipoeventogrupo">Grupo <span class="text-danger">*</span></label>
                                        <select class="form-control" id="etipoeventogrupo" name="etipoeventogrupo"></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="etipoeventosubgrupo">Subgrupo <span class="text-danger">*</span></label>
                                        <select class="form-control" id="etipoeventosubgrupo" name="etipoeventosubgrupo"></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="etipoevento">Tipo de Evento <span class="text-danger">*</span></label>
                                        <select class="form-control" id="etipoevento" name="etipoevento"></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="edescripcion">Descripción <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edescripcion" name="edescripcion" placeholder="Descripción del evento" maxlength="1000"><?= $res[0]["EVENTO_CONCEPTO"] ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="tipoCliente">Tipo de cliente <span class="text-danger">*</span></label>
                                        <select class="form-control" id="tipoCliente" name="tipoCliente"></select>
                                    </div>

                                    <div id="grupoCliente">
                                        <div class="form-group">
                                            <label for="ecliente">Nombre del paciente <span class="text-danger">*</span></label>
                                            <input type="hidden" id="eclienteid" name="eclienteid">
                                            <input type="text" class="form-control" id="ecliente" name="ecliente" required>
                                        </div>
                                    </div>

                                    <div id="grupoPresupuesto" style="display:none;">
                                        <div class="form-group">
                                            <label for="presupuesto">Presupuesto Aproximado <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="presupuesto" name="presupuesto" step="0.01" min="0" value="<?= (float)($res[0]['EVENTO_PRESUPUESTOPARTICULAR'] ?? 0) ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="efechai">Fecha Inicio <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="efechai" name="efechai" value="<?= $res[0]['EVENTO_FECHAI'] ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="efechaf">Fecha Fin <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="efechaf" name="efechaf" value="<?= $res[0]['EVENTO_FECHAF'] ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="elugar">Lugar <span class="text-danger">*</span></label>
                                        <select class="form-control" id="elugar" name="elugar"></select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="emedicor">Médico Referidor <span class="text-danger">*</span></label>
                                                <select class="form-control" id="emedicor" name="emedicor"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="emedicoi">Médico Intervencionista <span class="text-danger">*</span></label>
                                                <select class="form-control" id="emedicoi" name="emedicoi"></select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="eespecialista">Especialista </label>
                                                <select class="form-control" id="eespecialista" name="eespecialista"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="echofer">Chofer </label>
                                                <select class="form-control" id="echofer" name="echofer"></select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-12 col-md-9 mb-2 mb-md-0">
                                            <div class="form-group mb-md-0">
                                                <select class="form-control" id="emaleta" name="emaleta">
                                                    <option value="">Seleccione una maleta</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="form-group mb-md-0 mt-2 mt-md-0">
                                                <button type="button" class="btn btn-warning w-100" id="eagregarmaleta">Agregar Maleta</button>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Maleta</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="emaletasBody">
                                                <?php
                                                $seen = [];
                                                foreach ($res as $r) {
                                                    $eventomaletaId = trim((string)($r['EVENTOMALETA_ID'] ?? ''));
                                                    $almacenId      = trim((string)($r['ALMACEN_ID'] ?? ''));
                                                    if ($eventomaletaId === '' || $eventomaletaId === '0') continue;
                                                    if ((int)$almacenId < 0) continue;
                                                    if (isset($seen[$eventomaletaId])) continue;
                                                    $seen[$eventomaletaId] = true;
                                                    $label = trim(($r['ALMACEN_FOLIO'] ?? '') . ' - ' . ($r['ALMACEN_NOMBRE'] ?? ''));
                                                ?>
                                                    <tr data-tipo="original"
                                                        data-eventomaleta-id="<?= $eventomaletaId ?>"
                                                        data-almacen-id="<?= $almacenId ?>">
                                                        <td>
                                                            <input type="hidden" name="eventomaleta_id[]" value="<?= $eventomaletaId ?>">
                                                            <input type="hidden" name="almacen_id[]" value="<?= $almacenId ?>">
                                                            <input type="hidden" name="maleta_estado[]" value="original">
                                                            <?= htmlspecialchars($label) ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                                                                <i class="menu-icon mdi mdi-delete-forever"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- EQUIPO CAPITAL -->
                                    <div class="row align-items-end mt-3">
                                        <div class="col-12 col-md-9 mb-2 mb-md-0">
                                            <div class="form-group mb-md-0">
                                                <label for="eequipocapital">Equipo Capital <small class="text-muted">(Opcional)</small></label>
                                                <select class="form-control" id="eequipocapital" name="eequipocapital">
                                                    <option value="">Cargando equipos capital...</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="form-group mb-md-0 mt-2 mt-md-0">
                                                <button type="button" class="btn btn-info w-100" id="eagregarequipo">Agregar Equipo Capital</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="tablaEquipoCapitalWrap" style="<?= !empty($equiposAsignados) ? '' : 'display:none;' ?>">
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Equipo Capital</th>
                                                        <th>Folio</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="eequiposBody">
                                                    <?php foreach ($equiposAsignados as $eq): ?>
                                                    <tr data-tipo="original-ec"
                                                        data-eventomaleta-id="<?= $eq['EVENTOMALETA_ID'] ?>"
                                                        data-equipocapital-id="<?= $eq['EQUIPOCAPITAL_ID'] ?>">
                                                        <td>
                                                            <input type="hidden" name="eventomaleta_id_ec[]" value="<?= $eq['EVENTOMALETA_ID'] ?>">
                                                            <input type="hidden" name="equipocapital_id[]" value="<?= $eq['EQUIPOCAPITAL_ID'] ?>">
                                                            <input type="hidden" name="equipocapital_estado[]" value="original">
                                                            <?= htmlspecialchars($eq['ARTICULO_NOMBRE'] . ($eq['REFERENCIA'] ? ' (' . $eq['REFERENCIA'] . ')' : '') . ' (' . $eq['UBICACION'] . ')') ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($eq['FOLIO']) ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm eeliminarEquipo">
                                                                <i class="menu-icon mdi mdi-delete-forever"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-proveedor" role="tabpanel">
                        <div class="row mt-3 align-items-end">
                            <div class="col-12 col-md-9 mb-2 mb-md-0">
                                <label for="eproveedor">Proveedor</label>
                                <select class="form-control" id="eproveedor">
                                    <option value="">Selecciona un proveedor</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3 d-flex align-items-end mt-2 mt-md-0">
                                <button type="button" class="btn btn-warning w-100" id="agregarProveedor">Agregar Proveedor</button>
                            </div>

                            <div class="col-12 mt-3">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Proveedor</th>
                                                <th>Observaciones</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedoresBody">
                                            <?php if (!empty($result2) && is_array($result2)) : ?>
                                                <?php foreach ($result2 as $p):
                                                    $eventoproveedorId = (string)($p['EVENTOPROVEEDOR_ID'] ?? '');
                                                    $proveedorId       = (string)$p['PROVEEDOR_ID'];
                                                    $pnom              = (string)($p['NOMBREPROVEEDOR'] ?? '');
                                                    $obs               = (string)($p['EVENTOPROVEEDOR_OBSERVACIONES'] ?? '');
                                                ?>
                                                    <tr data-tipo="original"
                                                        data-eventoproveedor-id="<?= $eventoproveedorId ?>"
                                                        data-proveedor-id="<?= $proveedorId ?>">
                                                        <td>
                                                            <input type="hidden" name="eventoproveedor_id[]" value="<?= $eventoproveedorId ?>">
                                                            <input type="hidden" name="eproveedorid[]" value="<?= $proveedorId ?>">
                                                            <input type="hidden" name="eproveedor_estado[]" value="original">
                                                            <?= htmlspecialchars($pnom) ?>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control" name="eobservacionproveedor[]"
                                                                placeholder="Observaciones para este proveedor"><?= $obs ?></textarea>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm eliminarProveedor">Quitar</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-success" onclick="modificare();">Modificar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // === FUNCIONES AUXILIARES PARA CARGAR CATÁLOGOS SEGÚN SUCURSAL ===
    function cargarLugaresPorAlmacen(almacenId, selectVal = null) {
        $("#elugar").html("<option value=''></option>");
        if (!almacenId) return;

        $.getJSON("../ajax/get.lugares.php", {
            almacenid: almacenId
        }, function(data) {
            let html = "<option value=''></option>";
            data.forEach(x => {
                html += `<option value="${x.ID}">${x.NOMBRE}</option>`;
            });
            $("#elugar").html(html);

            if (selectVal) $("#elugar").val(selectVal);
        });
    }

    function cargarMedicosPorAlmacenYSubgrupo(almacenId, subgrupoId, grupoId = null, refVal = null, intVal = null) {
        $("#emedicor, #emedicoi").html("<option value=''></option>");
        if (!almacenId || !subgrupoId) return;

        $.getJSON("../ajax/get.medicos.php", {
            almacenid: almacenId,
            subgrupoid: subgrupoId,
            grupoid: grupoId || ""
        }, function(data) {
            let html = "<option value=''></option>";
            data.forEach(x => {
                html += `<option value="${x.ID}">${x.NOMBRE}</option>`;
            });

            $("#emedicor").html(html);
            $("#emedicoi").html(html);

            if (refVal) $("#emedicor").val(refVal);
            if (intVal) $("#emedicoi").val(intVal);
        });
    }

    function cargarChoferesPorSucursal(sucursalId, selectVal = null) {
        $("#echofer").html("<option value=''></option>");
        if (!sucursalId) return;
        $.getJSON(`../ajax/get.choferes.php?sucursalid=${btoa(sucursalId)}`, function(data) {
            let html = "<option value=''></option>";
            data.forEach(item => html += `<option value="${item.ID}">${item.NOMBRE}</option>`);
            $("#echofer").html(html);
            if (selectVal) $("#echofer").val(selectVal);
        });
    }

    function cargarEspecialistasPorSucursal(sucursalId, selectVal = null) {
        $("#eespecialista").html("<option value=''></option>");
        if (!sucursalId) return;
        $.getJSON(`../ajax/get.especialistas.php?sucursalid=${btoa(sucursalId)}`, function(data) {
            let html = "<option value=''></option>";
            data.forEach(item => html += `<option value="${item.ID}">${item.NOMBRE}</option>`);
            $("#eespecialista").html(html);
            if (selectVal) $("#eespecialista").val(selectVal);
        });
    }

    async function tmobtenerMaletas(sucalmacenid) {
        return await $.getJSON("../ajax/get.maletas.catalogo.php?sucalmacenid=" + sucalmacenid);
    }

    function cargarEquipoCapitalPorAlmacen(almacenId) {
        $("#eequipocapital").html("<option value=''>Seleccione un equipo capital</option>").prop("disabled", true);
        if (!almacenId) return;
        
        let currentEventoId = <?= isset($eventoid) ? (int)$eventoid : 0 ?>;
        let tipoEvId = $("#etipoevento").val() || 0;
        let fechai = $("#efechai").val() || '';
        let fechaf = $("#efechaf").val() || '';
        
        $.getJSON(`../ajax/get.equipocapital.byalmacen.php?almacenid=${almacenId}&eventoid=${currentEventoId}&current_eventoid=${currentEventoId}&tipoeventoid=${tipoEvId}&fechai=${fechai}&fechaf=${fechaf}`, function(data) {
            if (data && data.length > 0) {
                let html = "<option value=''>Seleccione un equipo capital</option>";
                data.forEach(item => {
                    html += `<option value="${item.ID}" data-folio="${item.FOLIO}" data-nombre="${item.ARTICULO_NOMBRE}">${item.NOMBRE_DISPLAY}</option>`;
                });
                $("#eequipocapital").html(html).prop("disabled", false);
            } else {
                $("#eequipocapital").html("<option value=''>No hay equipo capital en este almacén</option>").prop("disabled", true);
            }
        });
    }

    $(document).ready(function() {

        const sucursalIdInicial = "<?= (int)(!empty($res[0]['EVENTO_SUCURSALID']) ? $res[0]['EVENTO_SUCURSALID'] : $res[0]['SUCURSAL_ID']) ?>";
        const almacenIdInicial = "<?= (int)$res[0]['EVENTO_ALMACENID'] ?>";
        const grupoIdInicial = "<?= (int)$res[0]['TIPOEVENTOGRUPO_ID'] ?>";
        const subgrupoIdInicial = "<?= (int)$res[0]['TIPOEVENTOSUBGRUPO_ID'] ?>";
        const hospitalIdInicial = "<?= (int)$res[0]['EVENTO_HOSPITALID'] ?>";
        const medicoRefInicial = "<?= (int)$res[0]['EVENTO_DOCTORREFERIDORID'] ?>";
        const medicoIntInicial = "<?= (int)$res[0]['EVENTO_DOCTORINTERVENCIONISTAID'] ?>";

        // Cargar combos iniciales
        cargarLugaresPorAlmacen(almacenIdInicial, hospitalIdInicial);
        cargarMedicosPorAlmacenYSubgrupo(almacenIdInicial, subgrupoIdInicial, grupoIdInicial, medicoRefInicial, medicoIntInicial);
        cargarChoferesPorSucursal(sucursalIdInicial, '<?= $res[0]['EVENTO_CHOFERID'] ?>');
        cargarEspecialistasPorSucursal(sucursalIdInicial, '<?= $res[0]['EVENTO_ESPECIALISTAID'] ?>');

        tmobtenerMaletas(sucursalIdInicial).then(function(result) {
            let opcionesMaletas = "<option value=''>Seleccione una maleta</option>";
            if (result && result.length > 0) {
                $.each(result, function(index, obj) {
                    opcionesMaletas += `<option value="${obj.ID}">${obj.FOLIO.toUpperCase()} - ${obj.NOMBRE.toUpperCase()}</option>`;
                });
                $("#emaleta").html(opcionesMaletas).prop("disabled", false);
            } else {
                $("#emaleta").html("<option value=''>No hay maletas disponibles</option>").prop("disabled", true);
            }
        });

        // Catálogo Almacenes (Se cargan en memoria y se asignan cuando da clic en "Cambiar Almacén")
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
        });

        // Botón Cambiar Almacén
        $("#cambiarAlmacen").click(function() {
            Swal.fire({
                title: '¿Cambiar Almacén/Sucursal?',
                text: "Cambiar esto limpiará el subgrupo, tipo de evento, especialista, chofer, médicos, lugar y maletas previamente seleccionados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Poner las opciones de almacenes y habilitar
                    $("#ealmacen").html("<option value=''>Selecciona un almacén</option>" + itemsal);
                    $("#ealmacen").prop("disabled", false).val("");

                    $("#esucalmacen").val("");
                    $("#sucursalTexto").text("");

                    // Limpiar todo lo que dependa de la sucursal
                    $("#etipoeventosubgrupo").val("");
                    $("#etipoevento").html("<option value=''></option>");
                    $("#eespecialista").html("<option value=''></option>");
                    $("#echofer").html("<option value=''></option>");
                    $("#elugar").html("<option value=''></option>");
                    $("#emedicor, #emedicoi").html("<option value=''></option>");

                    // Marcar maletas originales como eliminadas
                    $("#emaletasBody tr").each(function() {
                        const $tr = $(this);
                        if ($tr.attr('data-tipo') === 'original') {
                            $tr.find('input[name="maleta_estado[]"]').val('eliminado');
                            $tr.attr('data-tipo', 'eliminado').hide();
                        } else {
                            $tr.remove();
                        }
                    });

                    $("#eequiposBody tr").each(function() {
                        const $tr = $(this);
                        if ($tr.attr('data-tipo') === 'original-ec') {
                            $tr.find('input[name="equipocapital_estado[]"]').val('eliminado');
                            $tr.attr('data-tipo', 'eliminado-ec').hide();
                        } else {
                            $tr.remove();
                        }
                    });
                    if ($("#eequiposBody tr:visible").length === 0) {
                        $("#tablaEquipoCapitalWrap").hide();
                    }
                    $("#eequipocapital").html("<option value=''>Seleccione un equipo capital</option>").prop("disabled", true);

                    $("#emaleta").html("<option value=''>Seleccione una maleta</option>").prop("disabled", true);
                }
            });
        });


        // Buscar el almacén original usando el AJAX de sucursal (categoría) que ya tienes
        $.getJSON("../ajax/get.almacenesporcategoria.php?categoriaid=<?= $res[0]['SUCURSAL_ID'] ?>", function(data) {
            if (data && data.length > 0) {
                // Pone el nombre del primer almacén encontrado para esta sucursal
                $("#opt-almacen-original").text(data[0].NOMBRE).val(data[0].ID);
            } else {
                $("#opt-almacen-original").text("Almacén de <?= $res[0]['SUCURSAL_NOMBRE'] ?>");
            }
        });

        // Evento onChange del Almacén
        $("#ealmacen").change(function() {
            const almacenId = $(this).val();

            $("#esucalmacen").val("");
            $("#sucursalTexto").text("");
            $("#eespecialista").html("<option value=''></option>");
            $("#echofer").html("<option value=''></option>");
            $("#etipoeventosubgrupo").val("");
            $("#etipoevento").html("<option value=''></option>");
            $("#elugar").html("<option value=''></option>");
            $("#emedicor, #emedicoi").html("<option value=''></option>");

            if (!almacenId) return;

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data && data.SUCURSAL_ID) {
                    const sucursalId = data.SUCURSAL_ID;
                    $("#esucalmacen").val(sucursalId);
                    $("#sucursalTexto").text("-> Sucursal vinculada al evento: " + data.SUCURSAL_NOMBRE);

                    cargarChoferesPorSucursal(sucursalId);
                    cargarEspecialistasPorSucursal(sucursalId);
                    cargarLugaresPorAlmacen(almacenId);
                    cargarEquipoCapitalPorAlmacen(almacenId);
                    $("#emedicor, #emedicoi").html("<option value=''></option>");

                    tmobtenerMaletas(sucursalId).then(function(result) {
                        let opcionesMaletas = "<option value=''>Seleccione una maleta</option>";
                        if (result && result.length > 0) {
                            $.each(result, function(index, obj) {
                                opcionesMaletas += `<option value="${obj.ID}">${obj.FOLIO.toUpperCase()} - ${obj.NOMBRE.toUpperCase()}</option>`;
                            });
                            $("#emaleta").html(opcionesMaletas).prop("disabled", false);
                        } else {
                            $("#emaleta").html("<option value=''>No hay maletas disponibles</option>").prop("disabled", true);
                        }
                    });
                } else {
                    Swal.fire("Error", "Este almacén no tiene una sucursal asignada.", "error");
                    $("#ealmacen").prop("disabled", false).val("");
                }
            });
        });

        // Get Eventos Tipo Grupo
        var itemsetipogrupo = "";
        $.getJSON("../ajax/get.eventos.tipogrupo.php", function(data) {
            itemsetipogrupo += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsetipogrupo += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#etipoeventogrupo").html(itemsetipogrupo);
            $("#etipoeventogrupo").val('<?= $res[0]['TIPOEVENTOGRUPO_ID'] ?>');
        });

        // Get Eventos Tipo SubGrupo filtrado por grupo
        var options = "";
        $.getJSON("../ajax/get.eventos.tiposubgrupo.php?grupoid=<?= $res[0]['TIPOEVENTOGRUPO_ID'] ?>", function(data) {
            options += "<option value=''></option>";
            if (data.length > 0) {
                $.each(data, function(index, item) {
                    options += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                });
            }
            $("#etipoeventosubgrupo").html(options);
            $("#etipoeventosubgrupo").val('<?= $res[0]['TIPOEVENTOSUBGRUPO_ID'] ?>');
        });

        // --- Tipo de cliente ---
        const CLIENTETIPO_EVENTO_ID = <?= isset($res[0]['EVENTO_CLIENTETIPOID']) ? (int)$res[0]['EVENTO_CLIENTETIPOID'] : 0 ?>;
        const FALLBACK_ES_PARTICULAR = <?= ($res[0]['EVENTO_CLIENTEID'] === null ? 'true' : 'false') ?>;

        $.getJSON("../ajax/get.clientestipos.php", function(data) {
            let opts = "<option value=''></option>";
            let tipoSeleccionado = CLIENTETIPO_EVENTO_ID || 0;

            if (!tipoSeleccionado && FALLBACK_ES_PARTICULAR) {
                const conPres = data.find(x => Number(x.PRESUPUESTO) === 1);
                if (conPres) tipoSeleccionado = Number(conPres.ID);
            }

            data.forEach(item => {
                const selected = (Number(item.ID) === Number(tipoSeleccionado)) ? "selected" : "";
                opts += `<option value="${item.ID}" data-presupuesto="${item.PRESUPUESTO}" ${selected}>${item.NOMBRE}</option>`;
            });

            $("#tipoCliente").html(opts);
            togglePresupuestoSegunTipo();
        });

        function togglePresupuestoSegunTipo() {
            const $sel = $("#tipoCliente");
            const requierePresupuesto = Number($sel.find("option:selected").data("presupuesto")) === 1;

            $("#grupoCliente").show();
            $("#ecliente").prop("required", true);

            if (requierePresupuesto) {
                $("#grupoPresupuesto").show();
                $("#presupuesto").prop("required", true);
            } else {
                $("#grupoPresupuesto").hide();
                $("#presupuesto").prop("required", false).val("");
            }
        }

        $("#tipoCliente").off('change').on('change', togglePresupuestoSegunTipo);


        // Obtener tipo de evento inicial
        var itemstipo = ""
        $.getJSON("../ajax/get.eventos.tipo.php?subgrupoid=" + btoa('<?= $res[0]['TIPOEVENTOSUBGRUPO_ID'] ?>'), function(data) {
            itemstipo += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemstipo += `<option value="${item.ID}">${item.NOMBRE}</option>`;
            });
            $("#etipoevento").html(itemstipo);
            $("#etipoevento").val('<?= $res[0]['EVENTO_TIPOEVENTO'] ?>');
            
            cargarEquipoCapitalPorAlmacen('<?= (int)$res[0]['EVENTO_ALMACENID'] ?>');
        });

        // Al cambiar grupo
        $("#etipoeventogrupo").change(function() {
            $("#etipoeventosubgrupo").html("<option value=''></option>");
            $("#etipoevento").html("<option value=''></option>");

            let grupoId = $(this).val();
            if (grupoId === "") return;

            $.getJSON("../ajax/get.eventos.tiposubgrupo.php?grupoid=" + grupoId, function(data) {
                let options = "<option value=''></option>";
                if (data.length > 0) {
                    $.each(data, function(index, item) {
                        options += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                    });
                }
                $("#etipoeventosubgrupo").html(options);
            });
        });

        // Al cambiar subgrupo
        $("#etipoeventosubgrupo").change(function() {
            const subgrupoId = $(this).val();
            const sucursalId = $("#esucalmacen").val();
            const almacenId = $("#ealmacen").val();
            const grupoId = $("#etipoeventogrupo").val();

            $("#etipoevento").html("<option value=''></option>");
            $("#emedicor, #emedicoi").html("<option value=''></option>");

            if (sucursalId === "") {
                Swal.fire({
                    text: "Primero selecciona un almacén.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            if (subgrupoId === "") {
                Swal.fire({
                    text: "Primero selecciona un subgrupo.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }


            $.getJSON(`../ajax/get.eventos.tipo.php?subgrupoid=${btoa(subgrupoId)}`, function(data) {
                let itemstipo = "<option value=''></option>";
                $.each(data, function(index, item) {
                    itemstipo += `<option value="${item.ID}">${item.NOMBRE}</option>`;
                });
                $("#etipoevento").html(itemstipo);
            });

            if (almacenId) {
                cargarMedicosPorAlmacenYSubgrupo(almacenId, subgrupoId, grupoId);
            }
        });


        $("#etipoevento").change(function() {
            const almacenId = $("#ealmacen").val();
            if (almacenId && almacenId !== "") {
                cargarEquipoCapitalPorAlmacen(almacenId);
            }
        });

        $("#efechai, #efechaf").change(function() {
            var almacenId = $("#ealmacen").val();
            if (almacenId) {
                cargarEquipoCapitalPorAlmacen(almacenId);
            }
        });

        //Get Proveedor
        var itemspro = "";
        $.getJSON("../ajax/get.proveedores.php", function(data) {
            itemspro += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemspro += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#eproveedor").html(itemspro);
        });

    });

    $("#presupuesto").on("input", function() {
        let val = $(this).val();
        if (val.includes('.')) {
            let partes = val.split('.');
            if (partes[1].length > 2) {
                $(this).val(parseFloat(val).toFixed(2));
            }
        }
    });

    $("#echofer").focus(function() {
        if ($("#esucalmacen").val() === "") {
            Swal.fire({
                text: "Primero selecciona la sucursal/almacén.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
            $(this).blur();
        }
    });

    $("#eespecialista").focus(function() {
        if ($("#esucalmacen").val() === "") {
            Swal.fire({
                text: "Primero selecciona el almacén.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
            $(this).blur();
        }
    });

    var listaProveedores = [];

    $("#proveedoresBody tr").each(function() {
        const id = $(this).data('id');
        if (id) listaProveedores.push(String(id));
    });

    $("#agregarProveedor").off('click').on('click', function() {
        const proveedorId = $("#eproveedor").val();
        const proveedorNombre = $("#eproveedor option:selected").text();
        if (!proveedorId) {
            alert("Selecciona un proveedor válido.");
            return;
        }
        agregarProveedorAlDOM(proveedorId, proveedorNombre, "", "nuevo", "");
    });


    $(document).off("click", ".eliminarProveedor").on("click", ".eliminarProveedor", function() {
        const $tr = $(this).closest("tr");
        const tipo = $tr.attr('data-tipo');
        if (tipo === "original") {
            $tr.find('input[name="eproveedor_estado[]"]').val('eliminado');
            $tr.attr('data-tipo', 'eliminado').hide();
        } else {
            $tr.remove();
        }
    });

    $(document).on('input', 'textarea[name="eobservacionproveedor[]"]', function() {
        const $tr = $(this).closest('tr');
        const $estado = $tr.find('input[name="eproveedor_estado[]"]');
        if ($estado.val() === 'original') $estado.val('editado');
    });

    async function eobtenerClientes() {
        return await $.getJSON("../ajax/get.clientes.catalogo.php");
    }

    // Campo libre: inicializar y sincronizar con eclienteid
    $("#eclienteid").val("<?= htmlspecialchars((string)($res[0]['EVENTO_CLIENTEID'] ?? ''), ENT_QUOTES) ?>");
    $("#ecliente").val("<?= htmlspecialchars((string)$nombrePacienteVal, ENT_QUOTES) ?>");

    $("#ecliente").on("input change blur", function() {
        $("#eclienteid").val($(this).val().trim());
    });

    // Agregar Maleta click
    $("#eagregarmaleta").off('click').on('click', function() {
        const almacenId = $("#emaleta").val();
        const etiqueta = $("#emaleta option:selected").text();
        if (!almacenId) {
            alert("Seleccione una maleta");
            return;
        }
        agregarMaletaAlDOM(almacenId, etiqueta, "nuevo", "");
        $("#emaleta").val("");
    });

    // Eliminar maleta
    $(document).off("click", ".eeliminarFila").on("click", ".eeliminarFila", function() {
        const $tr = $(this).closest("tr");
        const tipo = $tr.attr('data-tipo');
        if (tipo === "original") {
            $tr.find('input[name="maleta_estado[]"]').val('eliminado');
            $tr.attr('data-tipo', 'eliminado').hide();
        } else {
            $tr.remove();
        }
    });

    // ─── EQUIPO CAPITAL ───────────────────────────────────────────────────────
    $("#eagregarequipo").off('click').on('click', function() {
        var idEquipo = $("#eequipocapital").val();
        var folio = $("#eequipocapital option:selected").data("folio");
        var nombre = $("#eequipocapital option:selected").data("nombre");

        if (!idEquipo) {
            alert("Seleccione un equipo capital");
            return;
        }

        // Verificar que no esté ya en la tabla visible
        var yaExiste = false;
        $("#eequiposBody tr").each(function() {
            var $tr = $(this);
            if (String($tr.data("equipocapital-id")) === String(idEquipo)) {
                var est = $tr.find('input[name="equipocapital_estado[]"]').val();
                if (est === 'eliminado') {
                    // Si estaba como eliminado (era original), lo reactivamos
                    $tr.find('input[name="equipocapital_estado[]"]').val('original');
                    $tr.attr('data-tipo', 'original-ec').show();
                    yaExiste = true;
                    return false;
                } else {
                    alert("Este equipo capital ya fue agregado.");
                    yaExiste = true;
                    return false;
                }
            }
        });

        if (yaExiste) {
            $("#tablaEquipoCapitalWrap").show();
            $("#eequipocapital").val("");
            return;
        }

        const fila = `
            <tr data-tipo="nuevo-ec"
                data-eventomaleta-id=""
                data-equipocapital-id="${idEquipo}">
                <td>
                    <input type="hidden" name="eventomaleta_id_ec[]" value="">
                    <input type="hidden" name="equipocapital_id[]" value="${idEquipo}">
                    <input type="hidden" name="equipocapital_estado[]" value="nuevo">
                    <input type="text" class="form-control" value="${nombre}" readonly>
                </td>
                <td>${folio || ''}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eeliminarEquipo">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;

        $("#eequiposBody").append(fila);
        $("#tablaEquipoCapitalWrap").show();
        $("#eequipocapital").val("");
    });

    $(document).off("click", ".eeliminarEquipo").on("click", ".eeliminarEquipo", function() {
        const $tr = $(this).closest("tr");
        const tipo = $tr.attr('data-tipo');
        if (tipo === "original-ec") {
            $tr.find('input[name="equipocapital_estado[]"]').val('eliminado');
            $tr.attr('data-tipo', 'eliminado-ec').hide();
        } else {
            $tr.remove();
        }
        if ($("#eequiposBody tr:visible").length === 0) {
            $("#tablaEquipoCapitalWrap").hide();
        }
    });

    function modificare() {
        Swal.fire({
            text: 'Seguro que deseas guardar el Evento?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#esucalmacen").val() == "") {
                    Swal.fire({
                        html: "Sucursal/Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#esucalmacen').focus();
                        }
                    });
                } else if ($("#etipoeventogrupo").val() == "") {
                    Swal.fire({
                        html: "Grupo es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#etipoeventogrupo').focus();
                        }
                    });
                } else if ($("#etipoeventosubgrupo").val() == "") {
                    Swal.fire({
                        html: "Subgrupo es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#etipoeventosubgrupo').focus();
                        }
                    });
                } else if ($("#etipoevento").val() == "") {
                    Swal.fire({
                        html: "Tipo de evento es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#etipoevento').focus();
                        }
                    });
                } else if ($("#edescripcion").val() == "") {
                    Swal.fire({
                        html: "Descripción es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#edescripcion').focus();
                        }
                    });
                } else if ($("#tipoCliente").val() === "") {
                    Swal.fire({
                        html: "Tipo de cliente es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#tipoCliente').focus();
                        }
                    });
                } else if ($("#ecliente").val().trim() === "") {
                    Swal.fire({
                        html: "Nombre del paciente es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#ecliente').focus();
                        }
                    });
                } else if (Number($("#tipoCliente option:selected").data("presupuesto")) === 1 &&
                    ($("#presupuesto").val().trim() === "")) {
                    Swal.fire({
                        html: "Presupuesto es obligatorio para el tipo seleccionado",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#presupuesto').focus();
                        }
                    });
                } else if ($("#efechai").val() == "") {
                    Swal.fire({
                        html: "Fecha de Inicio es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#efechai').focus();
                        }
                    });
                } else if ($("#efechaf").val() == "") {
                    Swal.fire({
                        html: "Fecha de Fin es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#efechaf').focus();
                        }
                    });
                } else if ($("#efechaf").val() <= $("#efechai").val()) {
                    Swal.fire({
                        html: "La Fecha Fin debe ser mayor que la Fecha de Inicio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#efechaf').focus();
                        }
                    });
                } else if ($("#elugar").val() == "") {
                    Swal.fire({
                        html: "Lugar es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#elugar').focus();
                        }
                    });
                } else if ($("#emedicor").val() == "") {
                    Swal.fire({
                        html: "Médico Referidor es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#emedicor').focus();
                        }
                    });
                } else if ($("#emedicoi").val() == "") {
                    Swal.fire({
                        html: "Médico Intervencionista es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#emedicoi').focus();
                        }
                    });
                } else {
                    var form = document.getElementById("form-nuevo-evento");
                    var formData = new FormData(form);

                    const estadoActual = obtenerEstadoActual();
                    const cambios = obtenerCambios(originales, estadoActual);
                    formData.append('cambios_json', JSON.stringify(cambios));

                    $.ajax({
                        url: '../ajax/eventos.editar.php',
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
                                    html: "Evento modificado con éxito.<br><br><b>Siguiente paso:</b> Se ha notificado a los involucrados sobre las actualizaciones.",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud:', error);
                        },
                        complete: function(data) {
                            $("#loading").hide();
                        }
                    });
                }
            } else {
                return false;
            }
        });
    }

    function getMaletaRowByAlmacenId(almacenId) {
        let $row = null;
        $("#emaletasBody tr").each(function() {
            if (String($(this).data('almacen-id')) === String(almacenId)) {
                $row = $(this);
                return false;
            }
        });
        return $row;
    }

    function agregarMaletaAlDOM(almacenId, etiquetaTexto, tipo = "nuevo", eventomaletaId = "") {
        const $existente = getMaletaRowByAlmacenId(almacenId);
        if ($existente) {
            const $estado = $existente.find('input[name="maleta_estado[]"]');
            if ($estado.val() === 'eliminado') {
                $estado.val('original');
                $existente.attr('data-tipo', 'original').show();
                $existente.find('input[type="text"]').val(etiquetaTexto);
                return;
            } else {
                alert("Esta maleta ya está agregada.");
                return;
            }
        }

        const esOriginal = (tipo === "original");
        const estado = esOriginal ? 'original' : 'nuevo';
        const fila = `
            <tr data-tipo="${estado}"
                data-eventomaleta-id="${eventomaletaId || ''}"
                data-almacen-id="${almacenId}">
            <td>
                <input type="hidden" name="eventomaleta_id[]" value="${eventomaletaId || ''}">
                <input type="hidden" name="almacen_id[]" value="${almacenId}">
                <input type="hidden" name="maleta_estado[]" value="${estado}">
                <input type="text" class="form-control" value="${etiquetaTexto}" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                <i class="menu-icon mdi mdi-delete-forever"></i>
                </button>
            </td>
            </tr>`;
        $("#emaletasBody").append(fila);
    }

    function getProveedorRowByProveedorId(proveedorId) {
        let $row = null;
        $("#proveedoresBody tr").each(function() {
            if (String($(this).data('proveedor-id')) === String(proveedorId)) {
                $row = $(this);
                return false;
            }
        });
        return $row;
    }

    function agregarProveedorAlDOM(proveedorId, nombre, obs = "", tipo = "nuevo", eventoproveedorId = "") {
        const $existente = getProveedorRowByProveedorId(proveedorId);
        if ($existente) {
            const $estado = $existente.find('input[name="eproveedor_estado[]"]');
            if ($estado.val() === 'eliminado') {
                $estado.val('original');
                $existente.attr('data-tipo', 'original').show();
                if (obs) $existente.find('textarea[name="eobservacionproveedor[]"]').val(obs).trigger('input');
                return;
            } else {
                alert("Este proveedor ya está agregado.");
                return;
            }
        }

        const estado = (tipo === "original") ? "original" : "nuevo";
        const fila = `
            <tr data-tipo="${estado}"
                data-eventoproveedor-id="${eventoproveedorId || ''}"
                data-proveedor-id="${proveedorId}">
            <td>
                <input type="hidden" name="eventoproveedor_id[]" value="${eventoproveedorId || ''}">
                <input type="hidden" name="eproveedorid[]" value="${proveedorId}">
                <input type="hidden" name="eproveedor_estado[]" value="${estado}">
                ${nombre}
            </td>
            <td>
                <textarea class="form-control" name="eobservacionproveedor[]"
                placeholder="Observaciones para este proveedor">${obs || ""}</textarea>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm eliminarProveedor">Quitar</button>
            </td>
            </tr>`;
        $("#proveedoresBody").append(fila);
    }

    function parseLocalDateToMs(v) {
        if (!v) return null;
        let s = String(v).trim();
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(s)) {
            s = s.replace(' ', 'T');
        }
        const d = new Date(s);
        if (isNaN(d.getTime())) return null;
        d.setSeconds(0, 0);
        return d.getTime();
    }

    function equalDateTimes(a, b) {
        const ma = parseLocalDateToMs(a);
        const mb = parseLocalDateToMs(b);
        return (ma === mb);
    }
</script>
<script>
    // ==== 1) Snapshot originales ====
    var originales = {
        cabecera: {
            almacen_id: "<?= $res[0]['EVENTO_ALMACENID'] ?? '' ?>", // <-- NUEVO
            sucursal_id: "<?= $res[0]['SUCURSAL_ID'] ?>",
            sucursal: "<?= $res[0]['SUCURSAL_NOMBRE'] ?>",
            grupo_id: "<?= $res[0]['TIPOEVENTOGRUPO_ID'] ?>",
            subgrupo_id: "<?= $res[0]['TIPOEVENTOSUBGRUPO_ID'] ?>",
            tipoevento_id: "<?= $res[0]['EVENTO_TIPOEVENTO'] ?>",
            descripcion: `<?= htmlspecialchars($res[0]['EVENTO_CONCEPTO'] ?? '', ENT_QUOTES) ?>`,
            tipocliente: "<?= ($res[0]['EVENTO_CLIENTEID'] === null ? 'particular' : 'cliente') ?>",
            cliente_id: "<?= (string)($res[0]['EVENTO_CLIENTEID'] ?? '') ?>",
            tipocliente_id: "<?= (string)($res[0]['EVENTO_CLIENTETIPOID'] ?? '') ?>",
            presupuesto: "<?= (string)($res[0]['EVENTO_PRESUPUESTOPARTICULAR'] ?? '') ?>",
            fechai: "<?= (string)$res[0]['EVENTO_FECHAI'] ?>",
            fechaf: "<?= (string)$res[0]['EVENTO_FECHAF'] ?>",
            lugar_id: "<?= (string)$res[0]['EVENTO_HOSPITALID'] ?>",
            medicor_id: "<?= (string)$res[0]['EVENTO_DOCTORREFERIDORID'] ?>",
            medicoi_id: "<?= (string)$res[0]['EVENTO_DOCTORINTERVENCIONISTAID'] ?>",
            especialista_id: "<?= (string)($res[0]['EVENTO_ESPECIALISTAID'] ?? '') ?>",
            chofer_id: "<?= (string)($res[0]['EVENTO_CHOFERID'] ?? '') ?>",
            grupo_text: "<?= (string)($res[0]['TIPOEVENTOGRUPO_NOMBRE'] ?? '') ?>",
            subgrupo_text: "<?= (string)($res[0]['TIPOEVENTOSUBGRUPO_NOMBRE'] ?? '') ?>",
            tipoevento_text: "<?= (string)($res[0]['TIPOEVENTO_NOMBRE'] ?? '') ?>",
            cliente_nombre: "<?= htmlspecialchars((string)$nombrePacienteVal, ENT_QUOTES) ?>",
            lugar_text: "<?= (string)($res[0]['HOSPITAL_NOMBRE'] ?? '') ?>",
            medicor_text: "<?= (string)($res[0]['DOCTORREFERIDOR_NOMBRE'] ?? '') ?>",
            medicoi_text: "<?= (string)($res[0]['DOCTORINTERVENCIONISTA_NOMBRE'] ?? '') ?>",
            especialista_text: "<?= (string)($res[0]['ESPECIALISTA_NOMBRE'] ?? '') ?>",
            chofer_text: "<?= (string)($res[0]['CHOFER_NOMBRE'] ?? '') ?>",
        },
        maletas: <?php
                    $maletas = [];
                    $seen = [];
                    foreach ($res as $r) {
                        $mid = trim((string)($r['EVENTOMALETA_ID'] ?? ''));
                        $almid = trim((string)($r['ALMACEN_ID'] ?? ''));
                        if ($mid === '' || $mid === '0' || isset($seen[$mid])) continue;
                        if ((int)$almid < 0) continue;
                        $seen[$mid] = true;
                        $maletas[] = [
                            'id' => $mid,
                            'label' => (($r['ALMACEN_FOLIO'] ?? '') . ' - ' . ($r['ALMACEN_NOMBRE'] ?? ''))
                        ];
                    }
                    foreach ($equiposAsignados as $eq) {
                        $maletas[] = [
                            'id' => trim((string)$eq['EVENTOMALETA_ID']),
                            'label' => trim($eq['ARTICULO_NOMBRE'] . ($eq['REFERENCIA'] ? ' (' . $eq['REFERENCIA'] . ')' : ''))
                        ];
                    }
                    echo json_encode($maletas, JSON_UNESCAPED_UNICODE);
                    ?>,
        proveedores: <?php
                        $provs = [];
                        if (!empty($result2) && is_array($result2)) {
                            foreach ($result2 as $p) {
                                $provs[] = [
                                    'id' => (string)$p['PROVEEDOR_ID'],
                                    'nombre' => (string)($p['NOMBREPROVEEDOR'] ?? ''),
                                    'obs' => (string)($p['EVENTOPROVEEDOR_OBSERVACIONES'] ?? '')
                                ];
                            }
                        }
                        echo json_encode($provs, JSON_UNESCAPED_UNICODE);
                        ?>
    };

    // ==== 2) Estado actual (lee del DOM) ====
    function obtenerEstadoActual() {
        const estado = {
            cabecera: {
                almacen_id: $("#ealmacen").val() || "",
                sucursal_id: $("#esucalmacen").val() || "",
                grupo_id: $("#etipoeventogrupo").val() || "",
                subgrupo_id: $("#etipoeventosubgrupo").val() || "",
                tipoevento_id: $("#etipoevento").val() || "",
                descripcion: $("#edescripcion").val() || "",
                tipocliente: $("#tipoCliente").val() || "",
                cliente_id: $("#eclienteid").val() || "",
                tipocliente_id: $("#tipoCliente").val() || "",
                presupuesto: $("#presupuesto").val() || "",
                fechai: $("#efechai").val() || "",
                fechaf: $("#efechaf").val() || "",
                lugar_id: $("#elugar").val() || "",
                medicor_id: $("#emedicor").val() || "",
                medicoi_id: $("#emedicoi").val() || "",
                especialista_id: $("#eespecialista").val() || "",
                chofer_id: $("#echofer").val() || "",
                grupo_text: $("#etipoeventogrupo option:selected").text() || "",
                subgrupo_text: $("#etipoeventosubgrupo option:selected").text() || "",
                tipoevento_text: $("#etipoevento option:selected").text() || "",
                cliente_nombre: $("#ecliente").val() || "",
                lugar_text: $("#elugar option:selected").text() || "",
                medicor_text: $("#emedicor option:selected").text() || "",
                medicoi_text: $("#emedicoi option:selected").text() || "",
                especialista_text: $("#eespecialista option:selected").text() || "",
                chofer_text: $("#echofer option:selected").text() || ""
            },
            maletas: [],
            proveedores: []
        };

        // maletas
        $("#emaletasBody tr").each(function() {
            const $tr = $(this);
            estado.maletas.push({
                eventomaleta_id: String($tr.data('eventomaleta-id') || ''),
                almacen_id: String($tr.data('almacen-id') || ''),
                label: $tr.find('input[type="text"]').val() || '',
                estado: $tr.find('input[name="maleta_estado[]"]').val() || 'original'
            });
        });

        // equipos capital -> convertidos a almacen_id negativo
        $("#eequiposBody tr").each(function() {
            const $tr = $(this);
            const eqId = String($tr.data('equipocapital-id') || '');
            if (eqId) {
                const numEq = parseInt(eqId, 10);
                if (!isNaN(numEq) && numEq > 0) {
                    estado.maletas.push({
                        eventomaleta_id: String($tr.data('eventomaleta-id') || ''),
                        almacen_id: String(-1 * numEq),
                        label: $tr.find('input[type="text"]').val() || '',
                        estado: $tr.find('input[name="equipocapital_estado[]"]').val() || 'original'
                    });
                }
            }
        });

        // proveedores
        $("#proveedoresBody tr").each(function() {
            const $tr = $(this);
            estado.proveedores.push({
                eventoproveedor_id: String($tr.data('eventoproveedor-id') || ''),
                proveedor_id: String($tr.data('proveedor-id') || ''),
                nombre: $tr.find('td').eq(0).text().trim(),
                obs: $tr.find('textarea[name="eobservacionproveedor[]"]').val() || '',
                estado: $tr.find('input[name="eproveedor_estado[]"]').val() || 'original'
            });
        });

        return estado;
    }

    // ==== 3) Detección de cambios ====
    function obtenerCambios(original, modificado) {
        const cambios = {
            cabecera: {},
            maletas: [],
            proveedores: []
        };

        if (!equalDateTimes(original.cabecera.fechai, modificado.cabecera.fechai)) {
            cambios.cabecera.fechai = {
                original: original.cabecera.fechai,
                nuevo: modificado.cabecera.fechai
            };
        }
        if (!equalDateTimes(original.cabecera.fechaf, modificado.cabecera.fechaf)) {
            cambios.cabecera.fechaf = {
                original: original.cabecera.fechaf,
                nuevo: modificado.cabecera.fechaf
            };
        }

        const idTextFields = [{
                id: 'grupo_id',
                text: 'grupo_text',
                key: 'grupo'
            },
            {
                id: 'subgrupo_id',
                text: 'subgrupo_text',
                key: 'subgrupo'
            },
            {
                id: 'tipoevento_id',
                text: 'tipoevento_text',
                key: 'tipoevento'
            },
            {
                id: 'cliente_id',
                text: 'cliente_nombre',
                key: 'cliente'
            },
            {
                id: 'lugar_id',
                text: 'lugar_text',
                key: 'lugar'
            },
            {
                id: 'medicor_id',
                text: 'medicor_text',
                key: 'medico_referidor'
            },
            {
                id: 'medicoi_id',
                text: 'medicoi_text',
                key: 'medico_intervencionista'
            },
            {
                id: 'especialista_id',
                text: 'especialista_text',
                key: 'especialista'
            },
            {
                id: 'chofer_id',
                text: 'chofer_text',
                key: 'chofer'
            }
        ];

        const omitCabeceraKeys = new Set(['fechai', 'fechaf']);
        idTextFields.forEach(f => {
            omitCabeceraKeys.add(f.id);
            omitCabeceraKeys.add(f.text);

            const oid = String(original.cabecera[f.id] ?? "");
            const nid = String(modificado.cabecera[f.id] ?? "");
            const otx = String(original.cabecera[f.text] ?? "");
            const ntx = String(modificado.cabecera[f.text] ?? "");

            if (oid !== nid || otx !== ntx) {
                cambios.cabecera[f.key] = {
                    original: {
                        id: oid,
                        texto: otx
                    },
                    nuevo: {
                        id: nid,
                        texto: ntx
                    }
                };
            }
        });

        for (let k in modificado.cabecera) {
            if (omitCabeceraKeys.has(k)) continue;
            const o = (original.cabecera[k] ?? "");
            const m = (modificado.cabecera[k] ?? "");
            if (String(o) !== String(m)) {
                cambios.cabecera[k] = {
                    original: o,
                    nuevo: m
                };
            }
        }

        (modificado.maletas || []).forEach(m => {
            if (m.estado === 'nuevo') {
                cambios.maletas.push({
                    tipo: 'agregado',
                    almacen_id: m.almacen_id,
                    label: m.label
                });
            } else if (m.estado === 'eliminado') {
                cambios.maletas.push({
                    tipo: 'eliminado',
                    eventomaleta_id: m.eventomaleta_id,
                    almacen_id: m.almacen_id,
                    label: m.label
                });
            }
        });

        const op = {};
        (original.proveedores || []).forEach(p => {
            const key = String(p.proveedor_id ?? p.id ?? "");
            if (key) op[key] = p;
        });

        (modificado.proveedores || []).forEach(p => {
            if (p.estado === 'nuevo') {
                cambios.proveedores.push({
                    tipo: 'agregado',
                    proveedor_id: p.proveedor_id,
                    nombre: p.nombre,
                    obs: p.obs
                });
            } else if (p.estado === 'eliminado') {
                cambios.proveedores.push({
                    tipo: 'eliminado',
                    eventoproveedor_id: p.eventoproveedor_id,
                    proveedor_id: p.proveedor_id,
                    nombre: p.nombre
                });
            } else if (
                p.estado === 'editado' ||
                (op[p.proveedor_id] && String(op[p.proveedor_id].obs ?? "") !== String(p.obs ?? ""))
            ) {
                cambios.proveedores.push({
                    tipo: 'modificado',
                    eventoproveedor_id: p.eventoproveedor_id,
                    proveedor_id: p.proveedor_id,
                    nombre: p.nombre,
                    cambios: {
                        obs: {
                            original: (op[p.proveedor_id]?.obs ?? ""),
                            nuevo: p.obs
                        }
                    }
                });
            }
        });

        return cambios;
    }
</script>