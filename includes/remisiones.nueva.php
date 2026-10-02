<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        /* Asegura que esté sobre el modal (Bootstrap usa 1050) */
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }

    #esubtotalArticulosInput,
    #eivaArticulosInput,
    #etotalArticulosInput {
        font-weight: bold;
        font-size: 1.1em;
    }

    #etotalArticulosInput {
        background-color: #f8f9fa;
        border: 2px solid #007bff;
    }
</style>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nueva Remisión</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nueva-remision">
                <ul class="nav nav-tabs" id="eventoTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#tab-general" role="tab">General</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="proveedor-tab" data-toggle="tab" href="#tab-proveedor" role="tab">Proveedor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="otros-tab" data-toggle="tab" href="#tab-otros" role="tab">Otros</a>
                    </li>
                </ul>

                <div class="tab-content">

                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row mt-3">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="remalmacen">Almacén <span class="text-danger">*</span></label>
                                        <div class="form-group row">
                                            <div class="col-md-9">
                                                <input type="hidden" id="remsucalmacen" name="remsucalmacen">
                                                <select class="form-control" id="remalmacen" name="remalmacen"></select>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-warning w-100 text-nowrap" id="btnLimpiarTodo">
                                                    Cambiar Almacén
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Vincular Remisión a:</label>
                                        <div class="d-flex gap-4 mb-2">
                                            <div class="form-check m-0 mr-3 d-inline-block">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input" name="vincular_a" id="vincular_evento" value="evento" checked>
                                                    Evento
                                                </label>
                                            </div>
                                            <div class="form-check m-0 d-inline-block">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input" name="vincular_a" id="vincular_proyecto" value="proyecto">
                                                    Proyecto
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="remevento" id="lbl_remevento">Evento</label>
                                        <select class="form-control select2" id="remevento" name="remevento" style="width:100%;">
                                            <option value="">Seleccione...</option>
                                        </select>
                                        <input type="hidden" class="form-control" id="remeventoid" name="remeventoid">
                                        <input type="hidden" class="form-control" id="remproyectoid" name="remproyectoid">
                                        <input type="hidden" class="form-control" id="remisionid" name="remisionid">
                                        <input type="hidden" id="remeventoclienteid">
                                    </div>
                                    <div class="row">
                                        <div class="col-12 mb-2" id="contenedor-buscador-maletas" style="display:none;">
                                            <input type="text" id="buscadorMaletas" class="form-control" placeholder="Buscar por folio, referencia o descripción en las maletas...">
                                        </div>
                                        <div class="col-12">
                                            <div id="contenedor-articulos-maletas" class="mb-3">
                                                <!-- Los artículos se generarán aquí dinámicamente agrupados por maleta -->
                                            </div>
                                        </div>
                                        <div class="col-12 text-end mb-3">
                                            <button type="button" class="btn btn-warning" id="btnAgregarSeleccionados" style="display:none;">Agregar Seleccionados</button>
                                        </div>
                                    </div>
                                    <!-- Buscador de Equipo Capital Extra -->
                                    <div class="row mb-3" id="contenedor-extra-eq" style="display: none;">
                                        <div class="col-12">
                                            <div class="card shadow-sm border-0" style="border-radius: 8px; background-color: #f8f9fa;">
                                                <div class="card-body p-3">
                                                    <label for="selectExtraEq"><b>Agregar Equipo Capital Extra:</b></label>
                                                    <div class="row align-items-center">
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="selectExtraEq">
                                                                <option value="">Cargando Equipo Capital...</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mt-2 mt-md-0">
                                                            <button type="button" class="btn btn-primary w-100" id="btnAgregarExtraEq">
                                                                Agregar Extra
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3" id="contenedor-paquete-uap">
                                        <div class="col-12">
                                            <div class="card shadow-sm border-0" style="border-radius: 8px; background-color: #e9ecef;">
                                                <div class="card-body p-3">
                                                    <label for="selectPaqueteUap"><b>Agregar Paquete UAP:</b></label>
                                                    <div class="row align-items-center">
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="selectPaqueteUap">
                                                                <option value="">Cargando Paquetes...</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mt-2 mt-md-0">
                                                            <button type="button" class="btn btn-info w-100" id="btnAgregarPaqueteUap">
                                                                Agregar Paquete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-3">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Cve</th>
                                                    <th>Artículo</th>
                                                    <th>Subtotal</th>
                                                    <th>IVA</th>
                                                    <th>Total</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="earticulosBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-proveedor" role="tabpanel">
                        <div class="row mt-3">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="eproveedor">Proveedor del evento</label>
                                        <select class="form-control" id="eproveedor" name="eproveedor"></select>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <div class="form-group mb-md-0">
                                        <input type="hidden" id="idarticulofiltro" name="idarticulofiltro">
                                        <input type="hidden" id="cvearticulofiltro" name="cvearticulofiltro">
                                        <input type="text" class="form-control" id="articulofiltro" name="articulofiltro" placeholder="Artículo">
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0">
                                    <div class="form-group mb-md-0">
                                        <input type="number" class="form-control" id="cantidad" name="cantidad" placeholder="Cantidad" min="1">
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-group mb-md-0 mt-2 mt-md-0">
                                        <button type="button" class="btn btn-warning w-100" id="agregarProducto">Agregar Producto</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-otros" role="tabpanel">
                        <div class="row mt-3">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="eotrosproveedor">Otros Proveedores</label>
                                        <select class="form-control" id="eotrosproveedor" name="eotrosproveedor"></select>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <div class="form-group mb-md-0">
                                        <input type="hidden" id="idarticulofiltro_otros">
                                        <input type="hidden" id="cvearticulofiltro_otros">
                                        <input type="text" class="form-control" id="articulofiltro_otros" placeholder="Artículo">
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0">
                                    <div class="form-group mb-md-0">
                                        <input type="number" class="form-control" id="cantidad_otros" placeholder="Cantidad" min="1">
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-group mb-md-0 mt-2 mt-md-0">
                                        <button type="button" class="btn btn-warning w-100" id="agregarProducto_otros">Agregar Producto</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla compartida para ambos -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Proveedor</th>
                                            <th>Cve</th>
                                            <th>Artículo</th>
                                            <th>Subtotal</th>
                                            <th>Iva</th>
                                            <th>Total</th>
                                            <th>Cantidad</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="productosBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-2 text-end">
                    <div class="row justify-content-end mb-2">
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="actualizarTotal();" title="Recalcular totales desde los artículos">
                                <i class="mdi mdi-calculator"></i> Recalcular Totales
                            </button>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="esubtotalArticulosInput"><strong>Subtotal:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end" id="esubtotalArticulosInput" name="remisionsubtotal" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="eivaArticulosInput"><strong>IVA:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end" id="eivaArticulosInput" name="remisioniva" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="etotalArticulosInput"><strong>Total:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end font-weight-bold" id="etotalArticulosInput" name="remisiontotal" value="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-primary" onclick="guardare();">Guardar manual</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        // 1. Cargar catálogo de almacenes
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.usuario.php", function(data) {
            itemsal += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#remalmacen").html(itemsal);
            if (window.autoSelectAlmacenId) {
                $("#remalmacen").val(window.autoSelectAlmacenId).trigger("change");
                window.autoSelectAlmacenId = null;
            }
        });

        // Cargar catálogo de proveedores globalmente para pestaña Otros
        $.getJSON("../ajax/get.proveedores.php", function(data) {
            var itemspro = "<option value=''></option>";
            $.each(data || [], function(index, item) {
                itemspro += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#eotrosproveedor").html(itemspro);
        });

        async function tmobtenerProveedores(eventoid) {
            return await $.getJSON("../ajax/get.proveedoresbyevento.php?eventoid=" + eventoid);
        }

        async function tmobtenerEventos(almacenid) {
            let url = "../ajax/get.eventos.remision.php?almacenid=" + almacenid;
            let initialId = window.autoSelectEventoId || $('#initial_eventoid').val();
            if (initialId) {
                url += "&eventoid=" + initialId;
            }
            return await $.getJSON(url);
        }

        async function tmobtenerArticulosEventos(eventoid) {
            const ts = new Date().getTime();
            return await $.getJSON("../ajax/get.articulosbyevento.php?eventoid=" + eventoid + "&_=" + ts);
        }

        async function tmobtenerProyectos(almacenid) {
            return await $.getJSON("../ajax/get.proyectos.remision.php?almacenid=" + almacenid);
        }

        async function tmobtenerArticulosProyectos(proyectoid) {
            const almacenId = $("#remalmacen").val();
            const ts = new Date().getTime();
            return await $.getJSON("../ajax/get.articulosbyproyecto.php?proyectoid=" + proyectoid + "&almacenid=" + almacenId + "&_=" + ts);
        }

        // 2. Evento al cambiar el Almacén
        $("#remalmacen").change(function() {
            const almacenId = $(this).val();

            // Bloquear el select temporalmente
            $(this).prop("disabled", true);

            // Obtener sucursal del almacén elegido
            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(info) {
                if (info && info.SUCURSAL_ID) {
                    const sucursalId = info.SUCURSAL_ID;
                    $("#remsucalmacen").val(sucursalId); // Guardamos sucursal internamente
                    $("#sucursalTexto").text("-> Sucursal vinculada: " + info.SUCURSAL_NOMBRE);

                    inicializarBuscadorVincular(almacenId);
                } else {
                    Swal.fire({
                        text: "Error al cargar almacén o no tiene una sucursal asignada.",
                        icon: "error",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    $("#remalmacen").prop("disabled", false);
                }
            });
        });

        // Evento al cambiar la vinculación (Evento vs Proyecto)
        $("input[name='vincular_a']").change(function() {
            const almacenId = $("#remalmacen").val();
            if (almacenId) {
                inicializarBuscadorVincular(almacenId);
            }
        });

        function inicializarBuscadorVincular(almacenId) {
            // Limpiar campos y destruir autocomplete previo
            $("#remevento").val("");
            $("#remeventoid").val("");
            $("#remproyectoid").val("");
            $("#remisionid").val("");
            $("#remeventoclienteid").val("");
            $("#contenedor-extra-eq").hide();
            $("#contenedor-articulos-maletas").empty();
            $("#selectExtraEq").html("<option value=''>Cargando Equipo Capital...</option>");
            window.equipoCapitalDisponibleLocal = [];
            window.rfidRemisionPendientes = [];
            window.rfidMismatchNotificado = false;

            if ($("#remevento").data("select2")) {
                $("#remevento").select2("destroy");
            }
            $("#remevento").html("<option value=''>Seleccione...</option>");

            $("#earticulosBody").empty();
            $("#productosBody").empty();
            $("#contenedor-articulos-maletas").empty();
            $("#btnAgregarSeleccionados").hide();

            if (typeof actualizarTotal === "function") actualizarTotal();

            if (!almacenId) return;

            const esProyecto = $("#vincular_proyecto").is(":checked");

            if (esProyecto) {
                $("#lbl_remevento").text("Proyecto");
                $("#remevento").attr("placeholder", "Escribe el folio o concepto del proyecto...");

                tmobtenerProyectos(almacenId).then(function(resultproyectos) {
                    console.log('Proyectos recibidos:', resultproyectos);
                    if (!Array.isArray(resultproyectos)) {
                        $("#remalmacen").prop("disabled", false);
                        return;
                    }
                    let proyectosValidos = resultproyectos.filter(m => m.ID && m.NOMBRE);
                    if (proyectosValidos.length > 0) {
                        $("#remalmacen").prop("disabled", false);
                        $("#remevento").prop("readonly", false);

                        let optHtml = "<option value=''>Seleccione Proyecto...</option>";
                        $.each(proyectosValidos, function(i, obj) {
                            var folio = obj.FOLIO || '';
                            var concepto = obj.NOMBRE ? obj.NOMBRE.toUpperCase() : '';
                            var status = obj.STATUS_NOMBRE ? obj.STATUS_NOMBRE.toUpperCase() : '';
                            var label = folio + ' - ' + concepto + (status ? ' [' + status + ']' : '');
                            optHtml += `<option value="${obj.ID}" data-cliente="${obj.CLIENTE_ID || ''}">${label}</option>`;
                        });
                        
                        if ($("#remevento").data("select2")) {
                            $("#remevento").select2("destroy");
                        }
                        $("#remevento").html(optHtml).select2({width: '100%'});
                        
                        $("#remevento").off("change").on("change", function() {
                            var selectedId = $(this).val();
                            if (!selectedId) {
                                $("#contenedor-extra-eq").hide();
                                $("#remarticulos").val('');
                                return;
                            }
                            var clienteId = $(this).find(':selected').data('cliente') || '';
                            
                            $("#remeventoid").val("");
                            $("#remproyectoid").val(selectedId);
                            $("#remeventoclienteid").val(clienteId);
                            
                            $("#contenedor-extra-eq").show();
                            const almacenId = $("#remalmacen").val();

                            $("#selectExtraEq").html("<option value=''>Cargando Equipo Capital...</option>");
                            $.getJSON("../ajax/get.equipocapital.disponible.php?almacenid=" + almacenId + "&clienteid=" + clienteId, function(data) {
                                window.equipoCapitalDisponibleLocal = data || [];
                                let ecHtml = "<option value=''>Seleccione Equipo Capital...</option>";
                                $.each(data || [], function(i, o) {
                                    const label = (o.FOLIO ? o.FOLIO + ' - ' : '') + (o.ARTICULO_NOMBRE || '').toUpperCase() + (o.SERIE ? ' (S/N: ' + o.SERIE + ')' : '') + (o.CLAVE_ARTICULO ? ' (' + o.CLAVE_ARTICULO + ')' : '');
                                    ecHtml += `<option value="${o.ID}">${label}</option>`;
                                });
                                $("#selectExtraEq").html(ecHtml);
                            });

                            $("#remisionid").val("");
                            window.rfidRemisionPendientes = [];
                            window.rfidMismatchNotificado = false;

                            tmobtenerArticulosProyectos(selectedId).then(function(resultarticulos) {
                                if (!Array.isArray(resultarticulos)) return;
                                if ($("#remarticulos").data("ui-autocomplete")) {
                                    $("#remarticulos").autocomplete("destroy");
                                }
                                procesarYMostrarArticulos(resultarticulos);
                            });
                        });
                        
                        if (window.autoSelectEventoId) {
                            $("#remevento").val(window.autoSelectEventoId).trigger("change");
                            $("#remevento").prop("disabled", true);
                            window.autoSelectEventoId = null;
                        }
                    } else {
                        $("#remalmacen").prop("disabled", false);
                        $("#remevento").val("No se encontraron proyectos para este almacén").prop("readonly", true);
                    }
                });
            } else {
                $("#lbl_remevento").text("Evento");
                $("#remevento").attr("placeholder", "");

                tmobtenerEventos(almacenId).then(function(resulteventos) {
                    console.log('Eventos recibidos:', resulteventos);
                    if (!Array.isArray(resulteventos)) {
                        $("#remalmacen").prop("disabled", false);
                        return;
                    }

                    let eventosValidos = resulteventos.filter(m => m.ID && m.NOMBRE);

                    if (eventosValidos.length > 0) {
                        $("#remalmacen").prop("disabled", false);
                        $("#remevento").prop("readonly", false);

                        let optHtml = "<option value=''>Seleccione Evento...</option>";
                        $.each(eventosValidos, function(i, obj) {
                            var folio = obj.FOLIO || '';
                            var concepto = obj.NOMBRE ? obj.NOMBRE.toUpperCase() : '';
                            var status = obj.STATUS_NOMBRE ? obj.STATUS_NOMBRE.toUpperCase() : '';
                            var label = folio + ' - ' + concepto + (status ? ' [' + status + ']' : '');
                            var isSelected = (window.autoSelectEventoId && String(obj.ID) === String(window.autoSelectEventoId)) ? "selected" : "";
                            optHtml += `<option value="${obj.ID}" data-cliente="${obj.CLIENTE_ID || ''}" ${isSelected}>${label}</option>`;
                        });
                        
                        if ($("#remevento").data("select2")) {
                            $("#remevento").select2("destroy");
                        }
                        $("#remevento").html(optHtml).select2({width: '100%'});
                        
                        $("#remevento").off("change").on("change", function() {
                            var selectedId = $(this).val();
                            if (!selectedId) {
                                $("#contenedor-extra-eq").hide();
                                $("#remarticulos").val('');
                                return;
                            }
                            var clienteId = $(this).find(':selected').data('cliente') || '';
                            
                            $("#remeventoid").val(selectedId);
                            $("#remproyectoid").val(""); // clear project
                            $("#remeventoclienteid").val(clienteId);
                            
                            $("#contenedor-extra-eq").show();
                            const almacenId = $("#remalmacen").val();

                            $("#selectExtraEq").html("<option value=''>Cargando Equipo Capital...</option>");
                            $.getJSON("../ajax/get.equipocapital.disponible.php?almacenid=" + almacenId + "&clienteid=" + clienteId, function(data) {
                                window.equipoCapitalDisponibleLocal = data || [];
                                let ecHtml = "<option value=''>Seleccione Equipo Capital...</option>";
                                $.each(data || [], function(i, o) {
                                    const label = (o.FOLIO ? o.FOLIO + ' - ' : '') + (o.ARTICULO_NOMBRE || '').toUpperCase() + (o.SERIE ? ' (S/N: ' + o.SERIE + ')' : '') + (o.CLAVE_ARTICULO ? ' (' + o.CLAVE_ARTICULO + ')' : '');
                                    ecHtml += `<option value="${o.ID}">${label}</option>`;
                                });
                                $("#selectExtraEq").html(ecHtml);
                            });

                            $("#remisionid").val("");
                            window.rfidRemisionPendientes = [];
                            window.rfidMismatchNotificado = false;

                            tmobtenerArticulosEventos(selectedId).then(function(resultarticulos) {
                                if (!Array.isArray(resultarticulos)) return;
                                if ($("#remarticulos").data("ui-autocomplete")) {
                                    $("#remarticulos").autocomplete("destroy");
                                }
                                procesarYMostrarArticulos(resultarticulos, selectedId);
                            }).catch(function(error) {
                                Swal.fire({ text: "Error al cargar artículos del evento.", icon: "error" });
                            });

                            tmobtenerProveedores(selectedId).then(function(resultproveedores) {
                                var itemspro = "<option value=''></option>";
                                $.each(resultproveedores, function(index, item) {
                                    itemspro += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                                });
                                $("#eproveedor").html(itemspro);
                            }).catch(function(error) {
                                Swal.fire({ text: "Error al cargar proveedores del evento.", icon: "error" });
                            });
                        });
                        
                        if (window.autoSelectEventoId) {
                            // Event is already selected in HTML, just trigger change to load maletas
                            $("#remevento").trigger("change");
                            setTimeout(function() {
                                $("#remevento").prop("disabled", true);
                            }, 500);
                            window.autoSelectEventoId = null;
                        }
                    } else {
                        Swal.fire({
                            text: "No se encontraron eventos para este almacén.",
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                        $("#remalmacen").prop("disabled", false);
                        $("#remevento").prop("readonly", true);
                    }
                });
            }
        }

        function procesarYMostrarArticulos(resultarticulos, eventoid = null) {
            let articulosValidos = resultarticulos.filter(m => m.ID && (m.ARTICULO_NOMBRE || m.ARTICULO_NAME));
            articulosValidos.forEach(function(item) {
                if (!item.ARTICULO_NOMBRE) item.ARTICULO_NOMBRE = item.ARTICULO_NAME;
            });
            window.articulosValidosGlobal = articulosValidos;
            console.log('Artículos válidos filtrados:', articulosValidos.length);

            if (typeof procesarPendientesRFID === 'function') {
                procesarPendientesRFID();
            }

            if (articulosValidos.length > 0) {
                let htmlMaletas = '';
                let articulosPorMaleta = {};

                // Separar equipos capital (MALETA_ID < 0) de maletas normales
                let articulosMaletas = articulosValidos.filter(i => !i.MALETA_ID || i.MALETA_ID >= 0);

                // Agrupar maletas normales
                articulosMaletas.forEach(function(item) {
                    let maletaNombre = item.MALETA_NOMBRE ?
                        (item.MALETA_FOLIO || '') + ' - ' + item.MALETA_NOMBRE :
                        'Sin Maleta';
                    if (!articulosPorMaleta[maletaNombre]) {
                        articulosPorMaleta[maletaNombre] = [];
                    }
                    articulosPorMaleta[maletaNombre].push(item);
                });

                // Generar HTML maletas
                let maletaIndex = 0;
                for (let maleta in articulosPorMaleta) {
                    maletaIndex++;
                    htmlMaletas += `<div class="col-12 mb-3">
                                    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
                                        <div class="card-header font-weight-bold bg-light d-flex justify-content-between align-items-center toggle-maleta" data-target="#maleta-body-${maletaIndex}" style="border-bottom: 1px solid #eee; cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check m-0 mr-2" onclick="event.stopPropagation();">
                                                    <label class="form-check-label" style="margin-bottom: 0;">
                                                        <input type="checkbox" class="form-check-input chk-maleta-todos" data-maleta="${maleta}"> 
                                                        <i class="input-helper"></i>
                                                    </label>
                                                </div>
                                                <span class="text-dark font-weight-bold" style="font-size: 0.85em;">Maleta: ${maleta}</span>
                                            </div>
                                            <span class="badge badge-primary badge-pill text-white" style="padding: 6px 12px; font-size: 0.8em;">
                                                ${articulosPorMaleta[maleta].length} artículos
                                            </span>
                                        </div>
                                        <div class="card-body p-4 maleta-body collapse" id="maleta-body-${maletaIndex}" style="background-color: #fcfcfc;">
                                            <div class="row">`;
                    articulosPorMaleta[maleta].forEach(function(item) {
                        let cve = item.CLAVE_ARTICULO || '';
                        let itemText = (item.FOLIO || '') + ' - ' + (item.ARTICULO_NOMBRE || '') + (cve ? ' (' + cve + ')' : '');
                        let dataAttrs = `data-id="${item.ID}" data-folio="${item.FOLIO || ''}" data-cve="${cve}" data-nombre="${item.ARTICULO_NOMBRE || ''}" data-subtotal="${item.SUBTOTAL || 0}" data-iva="${item.IVA || 0}" data-total="${item.TOTAL || 0}"`;
                        htmlMaletas += `            <div class="col-12 mb-3 pb-2" style="border-bottom: 1px dashed #e0e0e0;">
                                                    <div class="form-check m-0">
                                                        <label class="form-check-label text-dark" style="font-size: 0.8em;">
                                                            <input type="checkbox" class="form-check-input chk-articulo-evento" data-maleta="${maleta}" ${dataAttrs}> 
                                                            <i class="input-helper"></i> ${itemText}
                                                        </label>
                                                    </div>
                                                </div>`;
                    });
                    htmlMaletas += `            </div>
                                        </div>
                                    </div>
                                </div>`;
                }

                $("#contenedor-articulos-maletas").html(htmlMaletas);
                $("#btnAgregarSeleccionados").show();
                $("#contenedor-buscador-maletas").show();

            } else {
                // Sin artículos en maletas, pero puede haber equipos capital
                $("#contenedor-articulos-maletas").html('');
                if (eventoid) {
                    $("#btnAgregarSeleccionados").show();
                } else {
                    Swal.fire({
                        text: "No se encontraron artículos.",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                }
                $("#contenedor-buscador-maletas").hide();
            }

            if (eventoid) {
                // Cargar equipos capital del evento
                $.getJSON('../ajax/get.equipocapital.byevento.php?eventoid=' + eventoid, function(eqData) {
                    if (eqData && eqData.length > 0) {
                        let htmlEq = '<div class="col-12 mb-3"><div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">';
                        htmlEq += '<div class="card-header font-weight-bold d-flex justify-content-between align-items-center toggle-maleta" data-target="#maleta-body-eq-evento" style="border-bottom: 1px solid #eee; cursor: pointer; background-color: #e8f4fd;">';
                        htmlEq += '<div class="d-flex align-items-center">';
                        htmlEq += '<div class="form-check m-0 mr-2" onclick="event.stopPropagation();"><label class="form-check-label" style="margin-bottom: 0;"><input type="checkbox" class="form-check-input chk-maleta-todos" data-maleta="__eq_evento__"> <i class="input-helper"></i></label></div>';
                        htmlEq += '<span class="text-primary font-weight-bold" style="font-size: 0.85em;"><i class="mdi mdi-briefcase-outline mr-1"></i>Equipo Capital del Evento</span>';
                        htmlEq += '</div>';
                        htmlEq += `<span class="badge badge-info badge-pill text-white" style="padding: 6px 12px; font-size: 0.8em;">${eqData.length} equipo(s)</span>`;
                        htmlEq += '</div>';
                        htmlEq += '<div class="card-body p-4 maleta-body collapse" id="maleta-body-eq-evento" style="background-color: #f0f7ff;"><div class="row">';
                        eqData.forEach(function(eq) {
                            const eqId = 'ec_' + eq.ID; // prefijo para distinguir de stock
                            const label = (eq.FOLIO || '') + ' - ' + (eq.ARTICULO_NOMBRE || '') + (eq.REFERENCIA ? ' (' + eq.REFERENCIA + ')' : '');
                            const dataAttrs = `data-id="${eqId}" data-folio="${eq.FOLIO || ''}" data-cve="${eq.CLAVE_ARTICULO || ''}" data-nombre="${eq.ARTICULO_NOMBRE || ''}" data-subtotal="${eq.SUBTOTAL || 0}" data-iva="${eq.IVA || 0}" data-total="${eq.TOTAL || 0}" data-eq-id="${eq.ID}"`;
                            htmlEq += `<div class="col-12 mb-3 pb-2" style="border-bottom: 1px dashed #c8e0f0;">
                            <div class="form-check m-0">
                                <label class="form-check-label text-dark" style="font-size: 0.8em;">
                                    <input type="checkbox" class="form-check-input chk-articulo-evento" data-maleta="__eq_evento__" ${dataAttrs}>
                                    <i class="input-helper"></i> ${label}
                                </label>
                            </div>
                        </div>`;
                        });
                        htmlEq += '</div></div></div></div>';
                        $("#contenedor-articulos-maletas").append(htmlEq);
                    }

                    // Bind eventos toggle/check al final
                    $(".chk-maleta-todos").off('change').change(function() {
                        let isChecked = $(this).is(":checked");
                        let maletaTarget = $(this).data("maleta");
                        $(`.chk-articulo-evento[data-maleta="${maletaTarget}"]:not(:disabled)`).prop("checked", isChecked);
                    });
                    $(".toggle-maleta").off('click').click(function() {
                        let target = $(this).data("target");
                        $(target).collapse('toggle');
                    });
                });
            } else {
                $(".chk-maleta-todos").off('change').change(function() {
                    let isChecked = $(this).is(":checked");
                    let maletaTarget = $(this).data("maleta");
                    $(`.chk-articulo-evento[data-maleta="${maletaTarget}"]:not(:disabled)`).prop("checked", isChecked);
                });
                $(".toggle-maleta").off('click').click(function() {
                    let target = $(this).data("target");
                    $(target).collapse('toggle');
                });
            }
        }


        var subtotalArticulos = 0;
        var ivaArticulos = 0;
        var totalArticulos = 0;

        $("#btnAgregarSeleccionados").click(function() {
            let itemsSeleccionados = $(".chk-articulo-evento:checked:not(:disabled)");

            if (itemsSeleccionados.length === 0) {
                Swal.fire({
                    text: "Seleccione al menos un artículo (que no haya sido agregado previamente).",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            let agregados = 0;
            let yaExistentes = 0;
            let sinPrecio = 0;

            itemsSeleccionados.each(function() {
                let idinventario = String($(this).data("id"));
                let folioinventario = $(this).data("folio");
                let cveinventario = $(this).data("cve");
                let articulo = $(this).data("nombre");
                let subtotal = parseFloat($(this).data("subtotal"));
                let iva = parseFloat($(this).data("iva"));
                let total = parseFloat($(this).data("total"));

                const esProyecto = $("#vincular_proyecto").is(":checked");
                if (isNaN(total) || (total <= 0 && !esProyecto)) {
                    sinPrecio++;
                    // Desmarcar el checkbox si no tiene precio para que no se quede bloqueado visualmente como seleccionado
                    $(this).prop("checked", false);
                    return; // Ignorar artículos sin precio
                }

                // Verifica si ya existe
                let yaExiste = false;
                $("input[name='remarticulosid[]']").each(function() {
                    if (String($(this).val()) === idinventario) {
                        yaExiste = true;
                        return false;
                    }
                });

                if (yaExiste) {
                    yaExistentes++;
                    // Desmarcar y deshabilitar si ya existe por alguna otra razón (ej. agregado por RFID)
                    $(this).prop("checked", true).prop("disabled", true);
                    return; // Continuar con el siguiente
                }

                var nuevaFila = `
                <tr data-total="${total}">
                    <td>
                        <input type="hidden" class="form-control" name="remarticulosid[]" value="${idinventario}" readonly>
                        <input type="text" class="form-control" value="${folioinventario}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${cveinventario}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${articulo}" readonly>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-subtotal-general" name="remarticulossubtotal[]" value="${subtotal.toFixed(2)}">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-iva-general" name="remarticulosiva[]" value="${iva.toFixed(2)}">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-total-general" name="remarticulostotal[]" value="${total.toFixed(2)}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                            <i class="menu-icon mdi mdi-delete-forever"></i>
                        </button>
                    </td>
                </tr>
            `;

                $("#earticulosBody").append(nuevaFila);
                subtotalArticulos += subtotal;
                ivaArticulos += iva;
                totalArticulos += total;
                agregados++;

                // Mantener checkbox marcado y deshabilitarlo
                $(this).prop("checked", true).prop("disabled", true);
            });

            actualizarTotal();

            // Desmarcar las maletas padre si se agregaron todos
            $(".chk-maleta-todos").prop("checked", false);

            if (agregados > 0) {
                // Silently added, no popup needed

            } else if (yaExistentes > 0 || sinPrecio > 0) {
                let msg = "";
                if (yaExistentes > 0 && sinPrecio > 0) {
                    msg = `Se ignoraron ${yaExistentes} por ya estar agregados y ${sinPrecio} por no tener precio ($0.00).`;
                } else if (yaExistentes > 0) {
                    msg = "Los artículos seleccionados ya habían sido agregados.";
                } else {
                    msg = `Se ignoraron ${sinPrecio} artículos seleccionados porque no tienen un precio configurado ($0.00).`;
                }

                Swal.fire({
                    text: msg,
                    icon: "info",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
            }
        });


        function actualizarTotal() {
            // Recalcular desde las tablas en lugar de usar variables acumuladas
            subtotalArticulos = 0;
            ivaArticulos = 0;
            totalArticulos = 0;

            // Sumar desde tabla General
            $(".campo-subtotal-general").each(function() {
                subtotalArticulos += parseFloat($(this).val()) || 0;
            });
            $(".campo-iva-general").each(function() {
                ivaArticulos += parseFloat($(this).val()) || 0;
            });
            $(".campo-total-general").each(function() {
                totalArticulos += parseFloat($(this).val()) || 0;
            });

            // Sumar desde tabla Proveedor
            $(".campo-subtotal-proveedor").each(function() {
                subtotalArticulos += parseFloat($(this).val()) || 0;
            });
            $(".campo-iva-proveedor").each(function() {
                ivaArticulos += parseFloat($(this).val()) || 0;
            });
            $(".campo-total-proveedor").each(function() {
                totalArticulos += parseFloat($(this).val()) || 0;
            });

            // Actualizar los campos editables con los valores calculados
            $("#esubtotalArticulosInput").val(subtotalArticulos.toFixed(2));
            $("#eivaArticulosInput").val(ivaArticulos.toFixed(2));
            $("#etotalArticulosInput").val(totalArticulos.toFixed(2));
        }

        //Articulos
        async function obtenerArticulos(maletaidvar) {
            return await $.getJSON("../ajax/get.articulos.catalogo.php");
        }

        obtenerArticulos().then(function(resultarticulos) {
            // Autocomplete para pestaña Proveedor
            $("#articulofiltro").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        var label = obj.NOMBRE.toUpperCase();
                        var referencia = (obj.CLAVE_ARTICULO || "").toUpperCase();
                        var todas_claves = (obj.TODAS_CLAVES || "").toUpperCase();
                        var termino = request.term.toUpperCase();
                        if (label.includes(termino) || referencia.includes(termino) || todas_claves.includes(termino)) {
                            return {
                                label: label + (obj.CLAVE_ARTICULO ? " (" + obj.CLAVE_ARTICULO + ")" : ""),
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                cve: obj.CLAVE_ARTICULO,
                                subtotal: parseFloat(obj.SUBTOTAL) || 0,
                                iva: parseFloat(obj.IVA) || 0,
                                total: parseFloat(obj.TOTAL) || 0
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#articulofiltro").val(ui.item.label);
                    $("#idarticulofiltro").val(ui.item.id);
                    $("#cvearticulofiltro").val(ui.item.cve);

                    $("#articulofiltro").attr("data-subtotal", ui.item.subtotal);
                    $("#articulofiltro").attr("data-iva", ui.item.iva);
                    $("#articulofiltro").attr("data-total", ui.item.total);
                    return false;
                }
            });

            // Llenar select de Paquetes UAP
            var opcionesPaquete = '<option value="">Selecciona un paquete...</option>';
            $.each(resultarticulos, function(i, obj) {
                var ref = (obj.CLAVE_ARTICULO || "").toUpperCase();
                if (ref.endsWith("-UAP")) {
                    var sub = parseFloat(obj.SUBTOTAL) || 0;
                    var iva = parseFloat(obj.IVA) || 0;
                    var tot = parseFloat(obj.TOTAL) || 0;
                    opcionesPaquete += `<option value="${obj.ID}" data-cve="${obj.CLAVE_ARTICULO}" data-nombre="${obj.NOMBRE}" data-sub="${sub}" data-iva="${iva}" data-tot="${tot}">${obj.NOMBRE} (${obj.CLAVE_ARTICULO})</option>`;
                }
            });
            $("#selectPaqueteUap").html(opcionesPaquete);

            $("#btnAgregarPaqueteUap").click(function() {
                var sel = $("#selectPaqueteUap option:selected");
                var id = sel.val();
                if (!id) {
                    Swal.fire({
                        text: "Seleccione un paquete UAP primero.",
                        icon: "warning"
                    });
                    return;
                }
                var cve = sel.data("cve");
                var nom = sel.data("nombre");
                var sub = parseFloat(sel.data("sub")) || 0;
                var iva = parseFloat(sel.data("iva")) || 0;
                var tot = parseFloat(sel.data("tot")) || 0;

                var nuevaFila = `
                <tr>
                    <td>
                        <input type="hidden" name="proveedorid[]" value="0">
                        <input type="text" class="form-control" value="PAQUETE UAP" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${cve}" readonly>
                    </td>
                    <td>
                        <input type="hidden" name="idarticulofiltro[]" value="${id}">
                        <input type="hidden" name="preciounitario_sub[]" value="${sub}">
                        <input type="hidden" name="preciounitario_iva[]" value="${iva}">
                        <input type="hidden" name="preciounitario_total[]" value="${tot}">
                        <input type="text" class="form-control" value="${nom}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="costounitario[]" value="${sub.toFixed(2)}" readonly>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-subtotal-proveedor" name="subproveedor[]" value="${sub.toFixed(2)}">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-iva-proveedor" name="ivaproveedor[]" value="${iva.toFixed(2)}">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control campo-total-proveedor" name="totalproveedor[]" value="${tot.toFixed(2)}">
                    </td>
                    <td>
                        <input type="number" class="form-control" name="cantidad[]" value="1" min="1" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm eliminarFila">
                            <i class="menu-icon mdi mdi-delete-forever"></i>
                        </button>
                    </td>
                </tr>
                `;
                $("#productosBody").append(nuevaFila);
                actualizarTotal();
                $("#selectPaqueteUap").val("");
                Swal.fire({
                    text: "Paquete UAP agregado correctamente.",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                });
            });


            // Autocomplete para pestaña Otros
            $("#articulofiltro_otros").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        var label = obj.NOMBRE.toUpperCase();
                        var referencia = (obj.CLAVE_ARTICULO || "").toUpperCase();
                        var todas_claves = (obj.TODAS_CLAVES || "").toUpperCase();
                        var termino = request.term.toUpperCase();
                        if (label.includes(termino) || referencia.includes(termino) || todas_claves.includes(termino)) {
                            return {
                                label: label + (obj.CLAVE_ARTICULO ? " (" + obj.CLAVE_ARTICULO + ")" : ""),
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                cve: obj.CLAVE_ARTICULO,
                                subtotal: parseFloat(obj.SUBTOTAL) || 0,
                                iva: parseFloat(obj.IVA) || 0,
                                total: parseFloat(obj.TOTAL) || 0
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#articulofiltro_otros").val(ui.item.label);
                    $("#idarticulofiltro_otros").val(ui.item.id);
                    $("#cvearticulofiltro_otros").val(ui.item.cve);

                    $("#articulofiltro_otros").attr("data-subtotal", ui.item.subtotal);
                    $("#articulofiltro_otros").attr("data-iva", ui.item.iva);
                    $("#articulofiltro_otros").attr("data-total", ui.item.total);
                    return false;
                }
            });
        });


        function procesarAgregarProducto(proveedorId, proveedorTexto, idArticulo, cveArticulo, articulo, cantidad, precioSubtotal, precioIva, precioTotal) {
            if (!idArticulo || !articulo || isNaN(cantidad) || cantidad <= 0) {
                Swal.fire({
                    text: "Seleccione un artículo válido y una cantidad mayor a 0.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            if (!proveedorId) {
                alert("Selecciona un proveedor.");
                return;
            }

            var subtotal = precioSubtotal * cantidad;
            var iva = precioIva * cantidad;
            var total = precioTotal * cantidad;

            var nuevaFila = `
            <tr>
                <td>
                    <input type="hidden" name="proveedorid[]" value="${proveedorId}">
                    <input type="text" class="form-control" value="${proveedorTexto}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${cveArticulo}" readonly>
                </td>
                <td>
                    <input type="hidden" name="idarticulofiltro[]" value="${idArticulo}">
                    <input type="hidden" name="preciounitario_sub[]" value="${precioSubtotal}">
                    <input type="hidden" name="preciounitario_iva[]" value="${precioIva}">
                    <input type="hidden" name="preciounitario_total[]" value="${precioTotal}">
                    <input type="text" class="form-control" value="${articulo}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" name="costounitario[]" value="${precioSubtotal.toFixed(2)}" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-subtotal-proveedor" name="subproveedor[]" value="${subtotal.toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-iva-proveedor" name="ivaproveedor[]" value="${iva.toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-total-proveedor" name="totalproveedor[]" value="${total.toFixed(2)}">
                </td>
                <td>
                    <input type="number" class="form-control" name="cantidad[]" value="${cantidad}" min="1" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarFila">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;

            $("#productosBody").append(nuevaFila);

            subtotalArticulos += subtotal;
            ivaArticulos += iva;
            totalArticulos += total;
            actualizarTotal();
        }

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulofiltro").val();
            var cveArticulo = $("#cvearticulofiltro").val();
            var articulo = $("#articulofiltro").val();
            var cantidad = parseFloat($("#cantidad").val());

            var precioSubtotal = parseFloat($("#articulofiltro").attr("data-subtotal")) || 0;
            var precioIva = parseFloat($("#articulofiltro").attr("data-iva")) || 0;
            var precioTotal = parseFloat($("#articulofiltro").attr("data-total")) || 0;

            var proveedorId = $("#eproveedor").val();
            var proveedorTexto = $("#eproveedor option:selected").text();

            procesarAgregarProducto(proveedorId, proveedorTexto, idArticulo, cveArticulo, articulo, cantidad, precioSubtotal, precioIva, precioTotal);

            $("#idarticulofiltro").val("");
            $("#articulofiltro").val("");
            $("#cantidad").val("");
            $("#articulofiltro").removeAttr("data-subtotal data-iva data-total");
        });

        $("#agregarProducto_otros").click(function() {
            var idArticulo = $("#idarticulofiltro_otros").val();
            var cveArticulo = $("#cvearticulofiltro_otros").val();
            var articulo = $("#articulofiltro_otros").val();
            var cantidad = parseFloat($("#cantidad_otros").val());

            var precioSubtotal = parseFloat($("#articulofiltro_otros").attr("data-subtotal")) || 0;
            var precioIva = parseFloat($("#articulofiltro_otros").attr("data-iva")) || 0;
            var precioTotal = parseFloat($("#articulofiltro_otros").attr("data-total")) || 0;

            var proveedorId = $("#eotrosproveedor").val();
            var proveedorTexto = $("#eotrosproveedor option:selected").text();

            procesarAgregarProducto(proveedorId, proveedorTexto, idArticulo, cveArticulo, articulo, cantidad, precioSubtotal, precioIva, precioTotal);

            $("#idarticulofiltro_otros").val("");
            $("#articulofiltro_otros").val("");
            $("#cantidad_otros").val("");
            $("#articulofiltro_otros").removeAttr("data-subtotal data-iva data-total");
        });

        // Para ambas pestañas - eliminar fila
        $(document).on("click", ".eliminarFila, .eeliminarFila", function() {
            let fila = $(this).closest("tr");
            let idArticulo = fila.find("input[name='remarticulosid[]']").val();
            if (idArticulo) {
                $(`.chk-articulo-evento[data-id="${idArticulo}"]`).prop("checked", false).prop("disabled", false);
            }
            fila.remove();
            actualizarTotal();
        });

        // Recalcular cuando se modifiquen los campos de subtotal, iva o total
        $(document).on("input", ".campo-subtotal-general, .campo-subtotal-proveedor", function() {
            let fila = $(this).closest("tr");
            let subtotal = parseFloat($(this).val()) || 0;
            let iva = subtotal * 0.16;
            let total = subtotal + iva;

            if ($(this).hasClass("campo-subtotal-general")) {
                fila.find(".campo-iva-general").val(iva.toFixed(2));
                fila.find(".campo-total-general").val(total.toFixed(2));
            } else {
                fila.find(".campo-iva-proveedor").val(iva.toFixed(2));
                fila.find(".campo-total-proveedor").val(total.toFixed(2));
            }
            actualizarTotal();
        });

        $(document).on("input", ".campo-iva-general, .campo-iva-proveedor", function() {
            let fila = $(this).closest("tr");
            let subtotalInput = $(this).hasClass("campo-iva-general") ? fila.find(".campo-subtotal-general") : fila.find(".campo-subtotal-proveedor");
            let totalInput = $(this).hasClass("campo-iva-general") ? fila.find(".campo-total-general") : fila.find(".campo-total-proveedor");

            let subtotal = parseFloat(subtotalInput.val()) || 0;
            let iva = parseFloat($(this).val()) || 0;
            let total = subtotal + iva;

            totalInput.val(total.toFixed(2));
            actualizarTotal();
        });

        $(document).on("input", ".campo-total-general, .campo-total-proveedor", function() {
            actualizarTotal();
        });

        // Función para limpiar todo
        $("#btnLimpiarTodo").click(function() {
            if (confirm("¿Seguro que deseas limpiar todo el formulario?")) {
                // Reset form
                $("#form-nueva-remision")[0].reset();

                // Reset tablas
                $("#selectExtraEq").html('<option value="">Seleccione Equipo Capital...</option>');
                $("#contenedor-extra-eq").hide();
                $("#contenedor-articulos-maletas").empty();
                $("#contenedor-buscador-maletas").hide();
                $("#btnAgregarSeleccionados").hide();
                $("#contenedor-extra-eq").hide();
                $("#selectExtraEq").html("<option value=''>Cargando Equipo Capital...</option>");
                $("#remeventoclienteid").val("");
                window.equipoCapitalDisponibleLocal = [];

                // Reset acumulados
                subtotalArticulos = 0;
                ivaArticulos = 0;
                totalArticulos = 0;
                actualizarTotal();

                // Reactivar campos
                $("#remalmacen").prop("disabled", false);
                $("#sucursalTexto").text("");
                $("#remsucalmacen").val("");
                $("#remevento").prop("readonly", false);
                $("#remisionid").val("");
                window.rfidRemisionPendientes = [];
                window.rfidMismatchNotificado = false;
            }
        });

        $("#btnAgregarExtraEq").click(function() {
            const equipoCapitalId = $("#selectExtraEq").val();
            if (!equipoCapitalId) {
                Swal.fire({
                    text: "Por favor, seleccione un artículo de Equipo Capital.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            // Buscar datos en window.equipoCapitalDisponibleLocal
            const item = (window.equipoCapitalDisponibleLocal || []).find(x => String(x.ID) === String(equipoCapitalId));
            if (!item) {
                Swal.fire({
                    text: "No se encontraron los datos del artículo seleccionado.",
                    icon: "error",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            const total = parseFloat(item.TOTAL);
            const esProyecto = $("#vincular_proyecto").is(":checked");
            if (isNaN(total) || (total <= 0 && !esProyecto)) {
                Swal.fire({
                    text: "El artículo seleccionado no tiene precio configurado.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            // Usar prefijo 'ec_' para el ID en la tabla (para no confundir con stock IDs)
            const rowId = 'ec_' + item.ID;

            // Validar duplicado
            let yaExiste = false;
            $("input[name='remarticulosid[]']").each(function() {
                if (String($(this).val()) === String(rowId)) {
                    yaExiste = true;
                    return false;
                }
            });
            if (yaExiste) {
                Swal.fire({
                    text: "Este Equipo Capital ya fue agregado a la remisión.",
                    icon: "info",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            var nuevaFila = `
            <tr data-total="${total}">
                <td>
                    <input type="hidden" class="form-control" name="remarticulosid[]" value="${rowId}" readonly>
                    <input type="text" class="form-control" value="${item.FOLIO || ''}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${item.CLAVE_ARTICULO || ''}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${item.ARTICULO_NOMBRE || ''}" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-subtotal-general" name="remarticulossubtotal[]" value="${parseFloat(item.SUBTOTAL || 0).toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-iva-general" name="remarticulosiva[]" value="${parseFloat(item.IVA || 0).toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-total-general" name="remarticulostotal[]" value="${parseFloat(item.TOTAL || 0).toFixed(2)}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;
            $("#earticulosBody").append(nuevaFila);
            actualizarTotal();

            // Marcar el checkbox en la sección de equipo capital del evento si existe
            $(`.chk-articulo-evento[data-eq-id="${item.ID}"]`).prop("checked", true).prop("disabled", true);

            // Limpiar select
            $("#selectExtraEq").val("");

            Swal.fire({
                text: "Equipo Capital agregado exitosamente.",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        });

        window.guardare = function() {
            if (String($("#remisionid").val() || "")) {
                Swal.fire({
                    text: "La remisión ya fue creada automáticamente por RFID.",
                    icon: "info",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            if (confirm('¿Seguro que deseas guardar la nota de Remisión?')) {
                const almacen = $("#remalmacen").val();
                const sucursal = $("#remsucalmacen").val();
                const esProyecto = $("#vincular_proyecto").is(":checked");
                const eventoid = $("#remeventoid").val();
                const proyectoid = $("#remproyectoid").val();
                const totalArticulosGeneral = $("#earticulosBody tr").length;
                const totalArticulosProveedor = $("#productosBody tr").length;

                if (!almacen || !sucursal) {
                    alert("Almacén es un campo obligatorio y debe tener una sucursal vinculada.");
                    $("#remalmacen").focus();
                    return;
                }

                if (!esProyecto && !eventoid) {
                    alert("Selecciona un evento válido");
                    $("#remevento").focus();
                    return;
                }

                if (esProyecto && !proyectoid) {
                    alert("Selecciona un proyecto válido");
                    $("#remevento").focus();
                    return;
                }

                if (totalArticulosGeneral === 0 && totalArticulosProveedor === 0) {
                    alert("Debes agregar al menos un artículo a la remisión (en General, Proveedor u Otros)");
                    return;
                }

                var formData = new FormData(document.getElementById("form-nueva-remision"));
                var remsucalmacen = document.getElementById("remsucalmacen").value;
                var remalmacen = document.getElementById("remalmacen").value;

                formData.append("remsucalmacen", remsucalmacen);
                formData.append("remalmacen", remalmacen);

                $.ajax({
                    url: '../ajax/remisiones.guardar.php',
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
                        var newRemisionId = response.trim();
                        var ids = newRemisionId.split(',');
                        var isValid = true;
                        var firstId = "";
                        
                        ids.forEach(function(id) {
                            if (isNaN(id) || id === "") {
                                isValid = false;
                            } else if (firstId === "") {
                                firstId = id;
                            }
                        });

                        if (isValid && firstId !== "") {
                            var eventId = document.getElementById('remeventoid').value;
                            if (eventId) {
                                if (ids.length > 1) {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: "¡Atención!",
                                            text: "Se crearon automáticamente " + ids.length + " remisiones separadas (para stock y equipo capital). Serás redirigido para llenar los datos de la primera.",
                                            icon: "info",
                                            confirmButtonText: "Entendido",
                                            allowOutsideClick: false,
                                            allowEscapeKey: false
                                        }).then(() => {
                                            window.location.href = 'eventos.php?status=16&open_datos_extra=' + btoa(firstId) + '&event_id=' + btoa(eventId);
                                        });
                                    } else {
                                        alert("Se crearon automáticamente " + ids.length + " remisiones separadas. Serás redirigido a la primera.");
                                        window.location.href = 'eventos.php?status=16&open_datos_extra=' + btoa(firstId) + '&event_id=' + btoa(eventId);
                                    }
                                } else {
                                    window.location.href = 'eventos.php?status=16&open_datos_extra=' + btoa(firstId) + '&event_id=' + btoa(eventId);
                                }
                            } else {
                                window.location.reload();
                            }
                        } else {
                            alert("Ocurrió un error al guardar la remisión: " + response);
                            $("#loading").hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la solicitud:', error);
                    },
                    complete: function() {
                        $("#loading").hide();
                    }
                });
            }
        }

        // =================================================================
        // INTEGRACION RFID - POLLING DE LA API DE REMISIONES
        // =================================================================

        // Cola local para no perder lecturas cuando el evento/articulos aun no estan listos.
        window.rfidRemisionPendientes = window.rfidRemisionPendientes || [];
        window.rfidMismatchNotificado = window.rfidMismatchNotificado || false;
        window.rfidRemisionCreando = window.rfidRemisionCreando || false;

        function existeArticuloEnTabla(stockId) {
            let existe = false;
            $("input[name='remarticulosid[]']").each(function() {
                if (String($(this).val()) === String(stockId)) {
                    existe = true;
                    return false;
                }
            });
            return existe;
        }

        function encolarPendientesRFID(items) {
            if (!Array.isArray(items) || items.length === 0) return;

            const ya = new Set(window.rfidRemisionPendientes.map(i => String(i.stock_id)));
            items.forEach(item => {
                if (!item || typeof item.stock_id === 'undefined' || item.stock_id === null) return;
                const k = String(item.stock_id);
                if (!ya.has(k) && !existeArticuloEnTabla(k)) {
                    window.rfidRemisionPendientes.push(item);
                    ya.add(k);
                }
            });
        }

        function appendFilaAutoRFID(art, subtotal, iva, total) {
            if (existeArticuloEnTabla(String(art.ID))) {
                return;
            }

            var nuevaFila = `
            <tr data-total="${total}">
                <td>
                    <input type="hidden" class="form-control" name="remarticulosid[]" value="${art.ID}" readonly>
                    <input type="text" class="form-control" value="${art.FOLIO || ''}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${art.CLAVE_ARTICULO || ''}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" value="${art.ARTICULO_NOMBRE || ''}" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-subtotal-general" name="remarticulossubtotal[]" value="${subtotal.toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-iva-general" name="remarticulosiva[]" value="${iva.toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control campo-total-general" name="remarticulostotal[]" value="${total.toFixed(2)}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eeliminarFila">
                        <i class="menu-icon mdi mdi-delete-forever"></i>
                    </button>
                </td>
            </tr>
        `;

            $("#earticulosBody").append(nuevaFila);
            actualizarTotal();
        }

        async function procesarPendientesRFID() {
            if (!Array.isArray(window.articulosValidosGlobal) || window.articulosValidosGlobal.length === 0) {
                return;
            }

            if (window.rfidRemisionCreando) {
                return;
            }

            const eventoSeleccionado = String($("#remeventoid").val() || "");
            const restantes = [];
            const items = window.rfidRemisionPendientes.slice();
            window.rfidRemisionPendientes = [];

            for (const itemRFID of items) {
                const stockId = String(itemRFID.stock_id);
                const eventoItem = (typeof itemRFID.evento_id !== 'undefined' && itemRFID.evento_id !== null) ?
                    String(itemRFID.evento_id) :
                    "";

                if (existeArticuloEnTabla(stockId)) {
                    continue;
                }

                if (eventoSeleccionado && eventoItem && eventoItem !== eventoSeleccionado) {
                    restantes.push(itemRFID);

                    if (!window.rfidMismatchNotificado && typeof Swal !== 'undefined') {
                        window.rfidMismatchNotificado = true;
                        const folioEv = itemRFID.evento_folio ? (' (' + itemRFID.evento_folio + ')') : '';
                        Swal.fire({
                            text: 'Se detectaron artículos RFID de otro evento' + folioEv + '. Cambia al evento correcto para agregarlos.',
                            icon: 'info',
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                    }
                    continue;
                }

                const art = window.articulosValidosGlobal.find(a => String(a.ID) === stockId);
                if (!art) {
                    restantes.push(itemRFID);
                    continue;
                }

                try {
                    window.rfidRemisionCreando = true;

                    let remisionActual = String($("#remisionid").val() || "");

                    if (!remisionActual) {
                        const respInicio = await $.ajax({
                            url: '../ajax/remisiones.iniciar.evento.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                eventoid: $("#remeventoid").val(),
                                stockid: stockId
                            }
                        });

                        if (!respInicio || respInicio.status !== 'success' || !respInicio.remisionid) {
                            restantes.push(itemRFID);
                            continue;
                        }

                        remisionActual = String(respInicio.remisionid);
                        $("#remisionid").val(remisionActual);

                        if (respInicio.creada) {
                            const subtotalInicio = parseFloat(art.SUBTOTAL) || 0;
                            const ivaInicio = parseFloat(art.IVA) || 0;
                            const totalInicio = parseFloat(art.TOTAL) || 0;
                            appendFilaAutoRFID(art, subtotalInicio, ivaInicio, totalInicio);
                            continue;
                        }

                        const respDetalleInicio = await $.ajax({
                            url: '../ajax/remisiones.detalle.agregar.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                remisionid: remisionActual,
                                stockid: stockId
                            }
                        });

                        if (String(respDetalleInicio).trim() === '') {
                            const subtotalInicio = parseFloat(art.SUBTOTAL) || 0;
                            const ivaInicio = parseFloat(art.IVA) || 0;
                            const totalInicio = parseFloat(art.TOTAL) || 0;
                            appendFilaAutoRFID(art, subtotalInicio, ivaInicio, totalInicio);
                        } else if (String(respDetalleInicio).trim() !== 'YA') {
                            restantes.push(itemRFID);
                        }
                        continue;
                    }

                    const respDetalle = await $.ajax({
                        url: '../ajax/remisiones.detalle.agregar.php',
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            remisionid: remisionActual,
                            stockid: stockId
                        }
                    });

                    if (String(respDetalle).trim() === '') {
                        const subtotal = parseFloat(art.SUBTOTAL) || 0;
                        const iva = parseFloat(art.IVA) || 0;
                        const total = parseFloat(art.TOTAL) || 0;
                        appendFilaAutoRFID(art, subtotal, iva, total);
                    } else if (String(respDetalle).trim() !== 'YA') {
                        restantes.push(itemRFID);
                    }
                } catch (error) {
                    restantes.push(itemRFID);
                } finally {
                    window.rfidRemisionCreando = false;
                }
            }

            window.rfidRemisionPendientes = restantes;
        }

        $(document).on('keyup', '#buscadorMaletas', function() {
            let term = $(this).val().toLowerCase();

            $('.chk-articulo-evento').each(function() {
                let container = $(this).closest('.col-12.mb-3.pb-2');
                let folio = ($(this).data('folio') || '').toString().toLowerCase();
                let cve = ($(this).data('cve') || '').toString().toLowerCase();
                let nombre = ($(this).data('nombre') || '').toString().toLowerCase();

                if (folio.includes(term) || cve.includes(term) || nombre.includes(term)) {
                    container.show().addClass('visible-item');
                } else {
                    container.hide().removeClass('visible-item');
                }
            });

            $('#contenedor-articulos-maletas > .col-12.mb-3').each(function() {
                let visibleItems = $(this).find('.visible-item').length;
                if (visibleItems > 0) {
                    $(this).show();
                    if (term.length > 0) {
                        $(this).find('.maleta-body').collapse('show');
                    } else {
                        $(this).find('.maleta-body').collapse('hide');
                    }
                } else {
                    $(this).hide();
                }
            });
        });
    });


    var rfidRemisionInterval = setInterval(async function() {
        // Detener el polling si el modal ya se cerró
        if ($("#form-nueva-remision").length === 0) {
            clearInterval(rfidRemisionInterval);
            return;
        }

        try {
            const respuesta = await fetch('../ajax/remisiones.rfid.poll.php', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!respuesta.ok) return;
            const data = await respuesta.json();

            if (data.status === "success" && data.articulos_nuevos && data.articulos_nuevos.length > 0) {
                encolarPendientesRFID(data.articulos_nuevos);
            }

            // En cada ciclo intentamos vaciar cola local.
            procesarPendientesRFID();
        } catch (err) {
            // Errores de conexión los omitimos pasivamente
        }
    }, 2500); // Poll cada 2.5 seg

    // Agregar automáticamente al clickear checkbox y limpiar buscador
    $(document).on("change", ".chk-articulo-evento:not(:disabled), .chk-maleta-todos", function() {
        if ($(this).is(":checked")) {
            $("#btnAgregarSeleccionados").click();
            $("#buscadorMaletas").val("").trigger("keyup");
        }
    });
</script>
<?php if (isset($_GET['eventoid'])): ?>
    <?php
    $eid = (int)base64_decode($_GET['eventoid']);
    $db = new FirebirdConnection();
    // Use EVENTO_ALMACENID and retrieve EVENTO_CONCEPTO to build the label
    $evtQuery = $db->query("
        SELECT 
            E.EVENTO_FOLIO, 
            E.EVENTO_ALMACENID, 
            E.EVENTO_CLIENTEID, 
            CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS EVENTO_CONCEPTO,
            S.STATUS_NOMBRE
        FROM AMPAR_HIS_EVENTOS E
        LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = E.EVENTO_STATUSGENERAL
        WHERE E.EVENTO_ID = {$eid}
    ");
    $db->close();
    if (!empty($evtQuery)) {
        $evtFolio = $evtQuery[0]['EVENTO_FOLIO'];
        $evtAlmacen = $evtQuery[0]['EVENTO_ALMACENID'];
        $evtClienteId = $evtQuery[0]['EVENTO_CLIENTEID'];
        $evtNombre = $evtQuery[0]['EVENTO_CONCEPTO'];
        $evtStatus = strtoupper($evtQuery[0]['STATUS_NOMBRE']);
        // Match the label format expected by autocomplete exactly
        $evtLabel = $evtFolio . " - " . $evtNombre . " [" . $evtStatus . "]";
    }
    ?>
    <?php if (!empty($evtQuery)): ?>
    <script>
        window.autoSelectEventoId = "<?= $eid ?>";
        window.autoSelectAlmacenId = "<?= $evtAlmacen ?>";
    </script>
    <?php endif; ?>
<?php endif; ?>