<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }

    /* Botón agregar rápido */
    .btn-quick-add {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        border: 1.5px solid #1f3bb3;
        color: #1f3bb3;
        background: transparent;
        white-space: nowrap;
        transition: all 0.18s ease;
        line-height: 1.4;
    }
    .btn-quick-add:hover {
        background: #1f3bb3;
        color: #fff;
    }
    .btn-quick-add .mdi {
        font-size: 0.9rem;
    }
    @media (max-width: 576px) {
        .btn-quick-add {
            font-size: 0.72rem;
            padding: 2px 8px;
        }
        /* En móvil los modales rápidos ocupan casi toda la pantalla */
        #modalQuickHospital .modal-dialog,
        #modalQuickMedico .modal-dialog {
            margin: 0.5rem;
        }
    }

    /* Backdrop oscuro para modales rápidos */
    #quickModalBackdrop {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.55);
        z-index: 1055;
        transition: opacity 0.2s ease;
    }
    #quickModalBackdrop.show { display: block; }

    /* Asegurar que los modales rápidos estén sobre el backdrop */
    #modalQuickHospital, #modalQuickMedico {
        z-index: 1060 !important;
    }
</style>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nuevo Evento</h4>
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
                                            <input type="hidden" id="esucalmacen" name="esucalmacen">
                                            <div class="col-12 mb-2 mb-md-0">
                                                <select id="ealmacen" name="ealmacen" class="form-control" required></select>
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
                                        <textarea class="form-control" id="edescripcion" name="edescripcion" placeholder="Descripción del evento" maxlength="1000"></textarea>
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
                                            <input type="number" class="form-control" id="presupuesto" name="presupuesto" step="0.01" min="0">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="efechai">Fecha Inicio <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="efechai" name="efechai">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="efechaf">Fecha Fin <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="efechaf" name="efechaf">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                                            <label for="elugar" class="mb-0">Lugar <span class="text-danger">*</span></label>
                                            <button type="button" class="btn btn-quick-add" onclick="abrirModalRapido('hospital')">
                                                <i class="mdi mdi-plus"></i> Nuevo Hospital
                                            </button>
                                        </div>
                                        <select class="form-control" id="elugar" name="elugar"></select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                                                    <label for="emedicor" class="mb-0">Médico Referidor <span class="text-danger">*</span></label>
                                                    <button type="button" class="btn btn-quick-add" onclick="abrirModalRapido('medico')">
                                                        <i class="mdi mdi-plus"></i> Nuevo Médico
                                                    </button>
                                                </div>
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
                                                <label for="eespecialista">Especialista <span class="text-danger">*</span></label>
                                                <select class="form-control" id="eespecialista" name="eespecialista"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="echofer">Chofer <span class="text-danger">*</span></label>
                                                <select class="form-control" id="echofer" name="echofer"></select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-12 col-md-9 mb-2 mb-md-0">
                                            <div class="form-group mb-md-0">
                                                <select class="form-control" id="emaleta" name="emaleta" disabled>
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
                                                    <th>Progreso</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="emaletasBody"></tbody>
                                        </table>
                                    </div>

                                    <!-- EQUIPO CAPITAL (Opcional) -->
                                    <div class="row align-items-end mt-3">
                                        <div class="col-12 col-md-9 mb-2 mb-md-0">
                                            <div class="form-group mb-md-0">
                                                <label for="eequipocapital">Equipo Capital <small class="text-muted">(Opcional)</small></label>
                                                <select class="form-control" id="eequipocapital" name="eequipocapital" disabled>
                                                    <option value="">Seleccione un equipo capital</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="form-group mb-md-0 mt-2 mt-md-0">
                                                <button type="button" class="btn btn-info w-100" id="eagregarequipo">Agregar Equipo Capital</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="tablaEquipoCapitalWrap" style="display:none;">
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Equipo Capital</th>
                                                        <th>Folio</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="eequiposBody"></tbody>
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
                                        <tbody id="proveedoresBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-success" onclick="guardare();">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Backdrop para modales rápidos -->
<div id="quickModalBackdrop"></div>

<!-- Modal Rápido Hospital -->
<div class="modal fade" id="modalQuickHospital" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Hospital (Rápido)</h5>
                <button type="button" class="close" onclick="$('#modalQuickHospital').modal('hide')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formQuickHospital">
                    <div class="form-group">
                        <label>Nombre del Hospital <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="HOSPITAL_NOMBRE" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#modalQuickHospital').modal('hide')">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarQuickHospital()">Guardar Hospital</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rápido Médico -->
<div class="modal fade" id="modalQuickMedico" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Médico (Rápido)</h5>
                <button type="button" class="close" onclick="$('#modalQuickMedico').modal('hide')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formQuickMedico">
                    <div class="form-group">
                        <label>Nombre del Médico <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="MEDICO_NOMBRE" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#modalQuickMedico').modal('hide')">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarQuickMedico()">Guardar Médico</button>
            </div>
        </div>
    </div>
</div>
<script>
// Fix para que Bootstrap no bloquee el focus en modales anidados
$('#modalglobal').removeAttr('tabindex');

$('#modalQuickHospital, #modalQuickMedico').on('shown.bs.modal', function () {
    var $input = $(this).find('input[type="text"]');
    // Usamos setTimeout(0) para ejecutar DESPUÉS de que Bootstrap
    // termine de configurar su enforceFocus en este tick del event loop
    setTimeout(function() {
        $(document).off('focusin.bs.modal');
        $input.focus();
    }, 0);
});


    function abrirModalRapido(tipo) {
        if (tipo === 'hospital') {
            const almacenId = $("#ealmacen").val();
            if (!almacenId) {
                Swal.fire('Atención', 'Primero selecciona un Almacén.', 'warning');
                return;
            }
            $('#formQuickHospital')[0].reset();
            $('#quickModalBackdrop').addClass('show');
            $('#modalQuickHospital').modal('show');
        } else if (tipo === 'medico') {
            const almacenId = $("#ealmacen").val();
            const subgrupoId = $("#etipoeventosubgrupo").val();
            if (!almacenId || !subgrupoId) {
                Swal.fire('Atención', 'Primero selecciona el Almacén y el Subgrupo.', 'warning');
                return;
            }
            $('#formQuickMedico')[0].reset();
            $('#quickModalBackdrop').addClass('show');
            $('#modalQuickMedico').modal('show');
        }
    }

    function guardarQuickHospital() {
        const form = $('#formQuickHospital')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const data = new FormData(form);
        data.append('action', 'save');
        data.append('cat', 'hospitales');
        data.append('HOSPITAL_ALMACEN', $("#ealmacen").val());
        
        $.ajax({
            url: '../api/catalogos.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(r) {
                if (r.ok) {
                    $('#modalQuickHospital').modal('hide');
                    Swal.fire('Éxito', 'Hospital guardado correctamente.', 'success');
                    cargarLugaresPorAlmacen($("#ealmacen").val(), r.data ? r.data.HOSPITAL_ID : null);
                } else {
                    Swal.fire('Error', r.msg || 'Error al guardar', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    }

    function guardarQuickMedico() {
        const form = $('#formQuickMedico')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const data = new FormData(form);
        data.append('action', 'save');
        data.append('cat', 'medicos');
        data.append('MEDICO_ALMACENID', $("#ealmacen").val());
        data.append('MEDICO_TIPOEVENTOID', $("#etipoeventosubgrupo").val()); // Por ahora guardamos el subgrupo en el campo TIPOEVENTOID que suele usarse para la relación.
        
        $.ajax({
            url: '../api/catalogos.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(r) {
                if (r.ok) {
                    $('#modalQuickMedico').modal('hide');
                    Swal.fire('Éxito', 'Médico guardado correctamente.', 'success');
                    cargarMedicosPorAlmacenYSubgrupo($("#ealmacen").val(), $("#etipoeventosubgrupo").val(), $("#etipoeventogrupo").val(), r.data ? r.data.MEDICO_ID : null);
                } else {
                    Swal.fire('Error', r.msg || 'Error al guardar', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    }

    $(document).ready(function() {
        
        // Mover los modales al body para evitar problemas de modales anidados en Bootstrap
        // Pero primero eliminar los anteriores si la página se cargó por Ajax múltiples veces
        $('body > #modalQuickHospital').remove();
        $('body > #modalQuickMedico').remove();
        $('#modalQuickHospital, #modalQuickMedico').appendTo('body');
        
        // Fix para que los inputs funcionen dentro de modales anidados en Bootstrap
        $(document).on('focusin', function(e) {
            if ($(e.target).closest('.modal').length) {
                e.stopImmediatePropagation();
            }
        });
        
        // Fix para que el scroll regrese al modal principal cuando se cierra el modal anidado
        $(document).on('hidden.bs.modal', '#modalQuickHospital, #modalQuickMedico', function () {
            $('#quickModalBackdrop').removeClass('show');
            if ($('.modal:visible').length) {
                $('body').addClass('modal-open');
            }
        });

        // Get Almacenes (En lugar de sucursales)
        // Get Almacenes del usuario (filtrado estrictamente por sus sucursales asignadas en la BD y omitiendo caducados)
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.usuario.php", function(data) {
            let selectedValue = "";
            if (data.length > 1) {
                itemsal += "<option value=''>Selecciona un almacén...</option>";
            }
            
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                if (data.length === 1 && index === 0) {
                    selectedValue = item.ID;
                }
            });
            $("#ealmacen").html(itemsal);
            
            if (data.length === 1 && selectedValue !== "") {
                // Solo auto-seleccionar y bloquear si el usuario tiene EXACTAMENTE UN almacén
                $("#ealmacen").val(selectedValue).trigger("change");
                $("#ealmacen").prop("disabled", true);
            } else {
                // Si tiene varios, dejar que elija
                $("#ealmacen").prop("disabled", false);
            }
        });

        // Get Eventos Tipo Grupo
        var itemsetipogrupo = "";
        $.getJSON("../ajax/get.eventos.tipogrupo.php", function(data) {
            itemsetipogrupo += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsetipogrupo += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#etipoeventogrupo").html(itemsetipogrupo);
        });

        // Al cambiar grupo, limpiar subgrupo y tipo, luego cargar subgrupo
        $("#etipoeventogrupo").change(function() {
            // Limpiar subgrupo y tipo
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

        // Al cambiar subgrupo, limpiar tipo y cargar tipo
        $("#etipoeventosubgrupo").change(function() {
            const subgrupoId = $(this).val();
            const sucursalId = $("#esucalmacen").val();
            const almacenId = $("#ealmacen").val();
            const grupoId = $("#etipoeventogrupo").val();

            $("#etipoevento").html("<option value=''></option>");
            $("#emedicor, #emedicoi").html("<option value=''></option>");

            if (sucursalId === "") {
                Swal.fire({
                    text: "Primero selecciona un Almacén.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            if (subgrupoId === "") {
                Swal.fire({
                    text: "Primero selecciona un Subgrupo.",
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

        // Cargar Tipos de Cliente (activos) desde la nueva tabla
        $.getJSON("../ajax/get.clientestipos.php", function(data) {
            let opts = "<option value=''></option>";
            $.each(data, function(i, item) {
                // Guarda si requiere presupuesto en un data-atributo
                opts += `<option value="${item.ID}" data-presupuesto="${item.PRESUPUESTO}">${item.NOMBRE}</option>`;
            });
            $("#tipoCliente").html(opts);
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

        // Cambiar entre Cliente y Paciente Particular
        $("#tipoCliente").change(function() {
            const $sel = $(this);
            const requierePresupuesto = Number($sel.find("option:selected").data("presupuesto")) === 1;

            // Cliente SIEMPRE por catálogo:
            $("#grupoCliente").show();
            $("#ecliente").prop("required", true);

            // Presupuesto: solo si el tipo lo requiere
            if (requierePresupuesto) {
                $("#grupoPresupuesto").show();
                $("#presupuesto").prop("required", true);
            } else {
                $("#grupoPresupuesto").hide();
                $("#presupuesto").prop("required", false).val("");
            }
        });

    });

    $("#presupuesto").on("input", function() {
        let val = $(this).val();

        // Validar máximo 2 decimales
        if (val.includes('.')) {
            let partes = val.split('.');
            if (partes[1].length > 2) {
                // Redondea a 2 decimales
                $(this).val(parseFloat(val).toFixed(2));
            }
        }
    });

    $("#cambiarAlmacen").click(function() {
        // $("#ealmacen").prop("disabled", false).val(""); // El almacen ahora se auto-selecciona y queda deshabilitado
        $("#esucalmacen").val("");
        $("#sucursalTexto").text("");
        $("#eespecialista").html("<option value=''></option>");
        $("#echofer").html("<option value=''></option>");
        $("#etipoeventogrupo").val("");
        $("#etipoeventosubgrupo").html("<option value=''></option>");
        $("#etipoevento").html("<option value=''></option>");
        $("#elugar").html("<option value=''></option>");
        $("#emedicor, #emedicoi").html("<option value=''></option>");
        // Limpiar tabla de maletas
        $("#emaletasBody").empty();

        // Limpiar y deshabilitar campos de maleta
        $("#emaletaid").val("").prop("disabled", true);
        $("#emaleta").val("").prop("disabled", true);

        // Limpiar equipo capital
        $("#eequipocapital").html("<option value=''>Seleccione un equipo capital</option>").prop("disabled", true);
        $("#eequiposBody").empty();
        listaEquiposCapital = [];
        $("#tablaEquipoCapitalWrap").hide();
    });

    $("#echofer").focus(function() {
        if ($("#ealmacen").val() === "") {
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

    $("#eespecialista").focus(function() {
        if ($("#ealmacen").val() === "") {
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

    $("#agregarProveedor").click(function() {
        const proveedorId = $("#eproveedor").val();
        const proveedorNombre = $("#eproveedor option:selected").text();

        if (proveedorId === "") {
            alert("Selecciona un proveedor válido.");
            return;
        }

        if (listaProveedores.includes(proveedorId)) {
            alert("Este proveedor ya fue agregado.");
            return;
        }

        listaProveedores.push(proveedorId);

        const fila = `
            <tr>
                <td>
                    <input type="hidden" name="eproveedorid[]" value="${proveedorId}">
                    ${proveedorNombre}
                </td>
                <td>
                    <textarea class="form-control" name="eobservacionproveedor[]" placeholder="Observaciones para este proveedor"></textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarProveedor">Quitar</button>
                </td>
            </tr>
        `;

        $("#proveedoresBody").append(fila);
    });

    // Eliminar proveedor de la lista
    $(document).on("click", ".eliminarProveedor", function() {
        const fila = $(this).closest("tr");
        const id = fila.find("input[name='eproveedorid[]']").val();
        listaProveedores = listaProveedores.filter(pid => pid !== id);
        fila.remove();
    });

    async function eobtenerClientes() {
        return await $.getJSON("../ajax/get.clientes.catalogo.php");
    }

    // Campo libre: sincronizar el valor del input con eclienteid al escribir
    $("#ecliente").on("input change blur", function() {
        $("#eclienteid").val($(this).val().trim());
    });

    async function tmobtenerMaletas(sucalmacenid) {
        return await $.getJSON("../ajax/get.maletas.catalogo.php?sucalmacenid=" + sucalmacenid);
    }

    function cargarEquipoCapitalPorAlmacen(almacenId) {
        $("#eequipocapital").html("<option value=''>Seleccione un equipo capital</option>").prop("disabled", true);
        if (!almacenId) return;

        let tipoEvId = $("#etipoevento").val() || 0;
        let fechai = $("#efechai").val() || '';
        let fechaf = $("#efechaf").val() || '';

        $.getJSON(`../ajax/get.equipocapital.byalmacen.php?almacenid=${almacenId}&current_eventoid=0&tipoeventoid=${tipoEvId}&fechai=${fechai}&fechaf=${fechaf}`, function(data) {
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

            if (selectVal) {
                $("#elugar").val(selectVal);
            }
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

    function cargarChoferesPorSucursal(sucursalId) {
        $("#echofer").html("<option value=''></option>");
        if (!sucursalId) return;
        $.getJSON(`../ajax/get.choferes.php?sucursalid=${btoa(sucursalId)}`, function(data) {
            let html = "<option value=''></option>";
            data.forEach(item => html += `<option value="${item.ID}">${item.NOMBRE}</option>`);
            $("#echofer").html(html);
        });
    }

    function cargarEspecialistasPorSucursal(sucursalId) {
        $("#eespecialista").html("<option value=''></option>");
        if (!sucursalId) return;
        $.getJSON(`../ajax/get.especialistas.php?sucursalid=${btoa(sucursalId)}`, function(data) {
            let html = "<option value=''></option>";
            data.forEach(item => html += `<option value="${item.ID}">${item.NOMBRE}</option>`);
            $("#eespecialista").html(html);
        });
    }

    // ACCIÓN PRINCIPAL AL ELEGIR EL ALMACÉN
    $("#ealmacen").change(function() {
        const almacenId = $(this).val();

        // Limpiar dependientes
        $("#esucalmacen").val("");
        $("#sucursalTexto").text("");
        $("#eespecialista").html("<option value=''></option>");
        $("#echofer").html("<option value=''></option>");
        $("#etipoeventogrupo").val("");
        $("#etipoeventosubgrupo").html("<option value=''></option>");
        $("#etipoevento").html("<option value=''></option>");
        $("#elugar").html("<option value=''></option>");
        $("#emedicor, #emedicoi").html("<option value=''></option>");
        $("#emaletasBody").empty();

        if (!almacenId) return;

        // OBTENEMOS LA SUCURSAL DEL ALMACÉN SELECCIONADO
        $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
            if (data && data.SUCURSAL_ID) {
                const sucursalId = data.SUCURSAL_ID;

                // Guardamos el ID en el input oculto que enviará el formulario
                $("#esucalmacen").val(sucursalId);
                $("#sucursalTexto").text("-> Sucursal vinculada al evento: " + data.SUCURSAL_NOMBRE);

                // Cargar catálogos filtrados por sucursal
                cargarChoferesPorSucursal(sucursalId);
                cargarEspecialistasPorSucursal(sucursalId);
                cargarLugaresPorAlmacen(almacenId);

                // todavía no cargamos médicos aquí,
                // porque dependen del hospital y del subgrupo
                $("#emedicor, #emedicoi").html("<option value=''></option>");

                // Cargar catálogo de maletas y llenar el select
                tmobtenerMaletas(sucursalId).then(function(result) {
                    let opcionesMaletas = "<option value=''>Seleccione una maleta</option>";
                    if (result && result.length > 0) {
                        $.each(result, function(index, obj) {
                            const porcentaje = (obj.PORCENTAJE !== null && obj.PORCENTAJE !== undefined && obj.PORCENTAJE !== '') ? Number(obj.PORCENTAJE).toFixed(2) : '0.00';
                            opcionesMaletas += `<option value="${obj.ID}" data-porcentaje="${porcentaje}">${obj.FOLIO.toUpperCase()} - ${obj.NOMBRE.toUpperCase()}</option>`;
                        });
                        $("#emaleta").html(opcionesMaletas).prop("disabled", false);
                    } else {
                        $("#emaleta").html("<option value=''>No hay maletas disponibles</option>").prop("disabled", true);
                    }
                });

                // Cargar equipo capital filtrado por almacén
                cargarEquipoCapitalPorAlmacen(almacenId);
            } else {
                Swal.fire("Error", "Este almacén no tiene una sucursal asignada.", "error");
                $("#ealmacen").prop("disabled", false).val("");
            }
        });

        $("#efechai, #efechaf").change(function() {
            var almacenId = $("#ealmacen").val();
            if (almacenId) {
                cargarEquipoCapitalPorAlmacen(almacenId);
            }
        });
    });


    $("#eagregarmaleta").click(function() {
        var idMaleta = $("#emaleta").val(); // Saca el ID directamente del select
        var maleta = $("#emaleta option:selected").text(); // Saca el texto del select
        var porcentajeMaleta = $("#emaleta option:selected").data("porcentaje");

        if (porcentajeMaleta === undefined || porcentajeMaleta === null || porcentajeMaleta === "") {
            porcentajeMaleta = "0.00";
        }

        if (idMaleta === "" || !idMaleta) {
            alert("Seleccione una maleta");
            return;
        }

        // Validar si ya se agregó la maleta
        var yaExiste = false;
        $("input[name='emaletaid[]']").each(function() {
            if ($(this).val() === idMaleta) {
                yaExiste = true;
                return false; // rompe el each
            }
        });

        if (yaExiste) {
            alert("Esta maleta ya fue agregada.");
            return;
        }

        var nuevaFila = `
            <tr>
                <td>
                    <input type="hidden" name="emaletaid[]" value="${idMaleta}">
                    <input type="text" class="form-control" value="${maleta}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${porcentajeMaleta}%" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;

        $("#emaletasBody").append(nuevaFila);

        // Limpiar los campos después de agregar
        $("#emaleta").val("");
    });

    // Eliminar fila maleta
    $(document).on("click", ".eeliminarFila", function() {
        $(this).closest("tr").remove();
    });

    // ─── EQUIPO CAPITAL ───────────────────────────────────────────────────────
    var listaEquiposCapital = [];

    $("#eagregarequipo").click(function() {
        var idEquipo = $("#eequipocapital").val();
        var folio = $("#eequipocapital option:selected").data("folio");
        var nombre = $("#eequipocapital option:selected").data("nombre");
        var textoDisplay = $("#eequipocapital option:selected").text();

        if (!idEquipo) {
            alert("Seleccione un equipo capital");
            return;
        }

        if (listaEquiposCapital.includes(idEquipo)) {
            alert("Este equipo capital ya fue agregado.");
            return;
        }

        listaEquiposCapital.push(idEquipo);

        const fila = `
            <tr>
                <td>
                    <input type="hidden" name="eequipocapitalid[]" value="${idEquipo}">
                    <input type="text" class="form-control" value="${nombre}" readonly>
                </td>
                <td>${folio || ''}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eeliminarEquipoFila" data-id="${idEquipo}">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;

        $("#eequiposBody").append(fila);
        $("#tablaEquipoCapitalWrap").show();
        $("#eequipocapital").val("");
    });

    // Eliminar equipo capital de la lista
    $(document).on("click", ".eeliminarEquipoFila", function() {
        var id = $(this).data("id").toString();
        listaEquiposCapital = listaEquiposCapital.filter(i => i !== id);
        $(this).closest("tr").remove();
        if ($("#eequiposBody tr").length === 0) {
            $("#tablaEquipoCapitalWrap").hide();
        }
    });

    //Articulos
    async function obtenerArticulos(maletaidvar) {
        return await $.getJSON("../ajax/get.articulos.catalogo.php");
    }

    obtenerArticulos().then(function(resultarticulos) {
        $("#articulofiltro").autocomplete({
            source: function(request, response) {
                response($.map(resultarticulos, function(obj) {
                    var label = obj.ID + ' - ' + obj.NOMBRE.toUpperCase();
                    if (label.includes(request.term.toUpperCase())) {
                        return {
                            label: label,
                            id: obj.ID,
                            nombre: obj.NOMBRE
                        };
                    }
                    return null;
                }).filter(Boolean));
            },
            minLength: 1,
            select: function(event, ui) {
                $("#articulofiltro").val(ui.item.label);
                $("#idarticulofiltro").val(ui.item.id);
                return false;
            }
        });
    });


    $("#agregarProducto").click(function() {
        var idArticulo = $("#idarticulofiltro").val();
        var articulo = $("#articulofiltro").val();
        var cantidad = $("#cantidad").val();

        if (idArticulo === "" || articulo === "" || cantidad === "" || cantidad <= 0) {
            alert("Seleccione un artículo válido y una cantidad mayor a 0.");
            return;
        }

        var nuevaFila = `
            <tr>
                <td>
                    <input type="hidden" name="idarticulofiltro[]" value="${idArticulo}">
                    <input type="text" class="form-control" value="${articulo}" readonly>
                </td>
                <td><input type="number" class="form-control" name="cantidad[]" value="${cantidad}" min="1" readonly></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarFila"><i class="menu-icon mdi mdi-delete-forever"></i></button>
                </td>
            </tr>
        `;

        $("#productosBody").append(nuevaFila);

        // Limpiar los campos después de agregar
        $("#idarticulofiltro").val("");
        $("#articulofiltro").val("");
        $("#cantidad").val("");
    });

    // Eliminar fila
    $(document).on("click", ".eliminarFila", function() {
        $(this).closest("tr").remove();
    });

    function guardare() {
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
                if ($("#ealmacen").val() == "") {
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#ealmacen').focus();
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
                } else if (
                    Number($("#tipoCliente option:selected").data("presupuesto")) === 1 &&
                    ($("#presupuesto").val().trim() === "")
                ) {
                    Swal.fire({
                        html: "Presupuesto es un campo obligatorio para el tipo seleccionado",
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
                } else if ($("#eespecialista").val() == "") {
                    Swal.fire({
                        html: "Especialista es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#eespecialista').focus();
                        }
                    });
                } else if ($("#echofer").val() == "") {
                    Swal.fire({
                        html: "Chofer es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#echofer').focus();
                        }
                    });
                } else {
                    var form = document.getElementById("form-nuevo-evento");
                    var formData = new FormData(form);

                    // Si necesitas enviar también el Almacén por algún motivo:
                    if ($("#ealmacen").prop("disabled")) {
                        formData.append("ealmacen", $("#ealmacen").val());
                    }

                    // NOTA: esucalmacen es un input type="hidden", por lo que FormData 
                    // lo atrapará automáticamente con el ID de la Sucursal para el backend.

                    $.ajax({
                        url: '../ajax/eventos.nuevo.php',
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
                                    html: "Evento guardado con éxito.<br><br><b>Siguiente paso:</b> El especialista y el chofer asignados recibirán una notificación para aceptar el evento. Una vez aceptado y llegado el día, el evento podrá ser Iniciado.",
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
</script>