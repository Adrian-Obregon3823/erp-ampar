<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/head.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Entrada a Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-salida-inventario">
                <input type="hidden" id="invtipo" name="invtipo" value="E">
                <div class="row">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="invalmacen" name="invalmacen"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group">
                            <label for="invsucursal">Categoría</label>
                            <input type="text" id="invsucursal" name="invsucursal" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="invtipoalmacen">Tipo de Almacén</label>
                            <input type="text" id="invtipoalmacen" name="invtipoalmacen" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="form-group">
                            <label for="invconcepto">Concepto <span class="text-danger">*</span></label>
                            <select class="form-control" id="invconcepto" name="invconcepto"></select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invmotivo">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo"
                                maxlength="250"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-9 mb-2 mb-md-0">
                        <div class="form-group">
                            <label for="proveedor">Proveedor</label>
                            <input type="hidden" class="form-control" id="idproveedor" name="idproveedor">
                            <input type="text" class="form-control" id="proveedor" name="proveedor">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="form-group">
                            <label for="correoproveedor">Correo Proveedor</label>
                            <input type="text" readonly class="form-control" id="correoproveedor"
                                name="correoproveedor">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group d-flex flex-column flex-md-row">
                            <input type="file" id="plantillaArchivo" accept=".xlsx" style="display:none">
                            <a href="../docs/EntradaAlmacen.xlsx" download
                                class="btn btn-light text-dark border border-success mb-2 mb-md-0 mr-md-2 text-center">
                                Descargar Plantilla
                            </a>
                            <a onclick="plantillaalmacen();" class="btn btn-success text-white border border-success text-center">
                                Subir plantilla
                            </a>
                        </div>
                    </div>
                </div>
                <div class="border rounded p-3 mb-3 mt-3" style="background-color: #f8fbff; border-color: #e0e6ed !important;">
                    <div class="row align-items-center mb-2">
                        <div class="col-12 col-md-8 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Artículo <span class="text-danger">*</span></label>
                                <input type="hidden" id="idarticulo" name="idarticulo">
                                <input type="hidden" id="seguimiento" name="seguimiento">
                                <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Buscar artículo...">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" placeholder="Cant." min="1">
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end mt-2">
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Lote <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="text" class="form-control" id="lote_global" placeholder="Se aplicará a todas">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Caducidad <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="date" class="form-control" id="caducidad_global">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Serie <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="text" class="form-control" id="serie_global" placeholder="Se aplicará a todas">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <button type="button" class="btn btn-warning px-5" id="agregarProducto">
                                Agregar Producto
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mb-3" id="seccion-documentos-cc" style="display:none; background-color: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba;">
                    <div class="col-12 mb-2">
                        <h6 class="text-warning font-weight-bold"><i class="mdi mdi-alert"></i> Documentos Requeridos por Caducidad (< 12 Meses)</h6>
                        <small class="text-muted">Se detectó al menos un artículo con caducidad próxima. Por favor proporcione la Carta Canje y el Número de Delivery. Estos se aplicarán a todos los artículos aplicables de esta entrada.</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label><strong>Carta Canje <span class="text-danger">*</span>:</strong></label>
                        <input type="file" class="form-control form-control-sm" name="archivo_carta_canje_general" id="archivo_carta_canje_general" accept=".pdf,application/pdf,image/*,.jpg,.jpeg,.png,.gif,.webp" title="Subir Carta Canje">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label><strong>Núm. de Delivery <span class="text-danger">*</span>:</strong></label>
                        <input type="text" class="form-control form-control-sm" name="num_delivery_general" id="num_delivery_general" placeholder="Ingrese el Número de Delivery">
                    </div>
                </div>

                <br>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Lote</th>
                                <th>Caducidad</th>
                                <th># Serie</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productosBody"></tbody>
                    </table>
                </div>
                <br>
                <button type="button" class="btn btn-success" onclick="guardar();">Guardar</button>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        //Get Conceptos de inventario
        var items1 = "";
        $.getJSON("../ajax/get.inventarios.conceptos.php?naturalezaconcepto=E", function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invconcepto").html(items1);
        });

        //Get Almacenes
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            itemsal += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(itemsal);
        });

        $("#invalmacen").change(function() {
            const almacenId = $("#invalmacen").val();
            $("#invsucursal").val("");
            $("#invtipoalmacen").val("");

            if (!almacenId) {
                return;
            }

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data.SUCURSAL_NOMBRE) {
                    $("#invsucursal").val(data.SUCURSAL_NOMBRE);
                }
                if (data.TIPOALMACEN_NOMBRE) {
                    $("#invtipoalmacen").val(data.TIPOALMACEN_NOMBRE);
                }
            });
        });

        //Get Proveedores

        async function obtenerproveedores() {
            return await $.getJSON("../ajax/get.proveedores.php");
        }

        obtenerproveedores().then(function(resultprov) {
            let seleccionado = false;

            $("#proveedor").autocomplete({
                source: function(request, response) {
                    response($.map(resultprov, function(obj) {
                        var label = obj.NOMBRE.toUpperCase();
                        if (label.includes(request.term.toUpperCase())) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                correo: obj.EMAIL
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#proveedor").val(ui.item.label);
                    $("#idproveedor").val(ui.item.id);
                    $("#correoproveedor").val(ui.item.correo);
                    seleccionado = true;
                    return false;
                }
            });

            // Si el usuario escribe, desactiva la selección
            $("#proveedor").on("input", function() {
                seleccionado = false;
            });

            // Al salir del campo, valida si se seleccionó algo; si no, limpia los campos
            $("#proveedor").on("blur", function() {
                if (!seleccionado) {
                    $("#proveedor").val('');
                    $("#idproveedor").val('');
                    $("#correoproveedor").val('');
                }
            });
        });

        async function obtenerArticulos() {
            return await $.getJSON("../ajax/get.articulos.catalogo.php");
        }

        obtenerArticulos().then(function(resultarticulos) {
            $("#articulo").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        var label = obj.CLAVE_ARTICULO + ' - ' + obj.NOMBRE.toUpperCase();
                        if (label.includes(request.term.toUpperCase())) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                seguimiento: obj.SEGUIMIENTO,
                                cve: obj.CLAVE_ARTICULO
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#articulo").val(ui.item.label);
                    $("#idarticulo").val(ui.item.id);
                    $("#seguimiento").val(ui.item.seguimiento);
                    return false;
                }
            });
        });

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var seguimiento = $("#seguimiento").val();
            var cantidad = parseInt($("#cantidad").val());

            var loteGlobal = $("#lote_global").val();
            var caducidadGlobal = $("#caducidad_global").val();
            var serieGlobal = $("#serie_global").val();

            if (idArticulo === "" || articulo === "" || isNaN(cantidad) || cantidad <= 0) {
                Swal.fire({
                    html: "Seleccione un artículo válido y una cantidad mayor a 0.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
                return;
            }

            for (let i = 0; i < cantidad; i++) {
                let readonlyLote = '';
                let readonlyCaducidad = '';
                let readonlySerie = '';

                var nuevaFila = `
                    <tr>
                        <td>
                            <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                            <input type="hidden" name="seguimientoarray[]" value="${seguimiento}">
                            <input type="text" class="form-control" value="${articulo}" readonly>
                        </td>
                        <input type="hidden" class="form-control" name="cantidadarray[]" value="1" min="1" readonly>
                        <td>
                            <input type="text" class="form-control" name="lotearray[]" placeholder="Lote" value="${loteGlobal}" ${readonlyLote}>
                        </td>
                        <td>
                            <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="${caducidadGlobal}" ${readonlyCaducidad}>
                            <input type="hidden" name="caducidadmenor1anio[]" class="banderaCaducidad">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="seriearray[]" placeholder="Serie" value="${serieGlobal}" ${readonlySerie}>
                        </td>
                        <td>
                            <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                        </td>
                    </tr>
                `;
                $("#productosBody").append(nuevaFila);
                // Evaluar bandera de caducidad para la fila recién insertada
                evaluarBanderaCaducidad($("#productosBody").find(".caducidadInput").last());
            }

            // Limpiar los campos después de agregar
            $("#idarticulo").val("");
            $("#articulo").val("");
            $("#seguimiento").val("");
            $("#cantidad").val("");
            $("#lote_global").val("");
            $("#caducidad_global").val("");
            $("#serie_global").val("");

            // Re-evaluar visibilidad de sección carta canje
            if (verificaCaducidadMenorUnAnio()) {
                $("#seccion-documentos-cc").fadeIn();
            } else {
                $("#seccion-documentos-cc").fadeOut();
            }
        });


        $("#plantillaArchivo").change(function() {
            var archivo = this.files[0];
            if (!archivo) return;

            var formData = new FormData();
            formData.append("archivo", archivo);

            $.ajax({
                url: "../ajax/entradasalida.cargaplantilla.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json', // jQuery parseará automáticamente el JSON
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(datos) {
                    // Ya no necesitamos JSON.parse(), 'datos' ya es un objeto

                    if (datos.error) {
                        Swal.fire({
                            html: "Error: " + datos.error,
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success' // usa clases de Bootstrap
                            }
                        });
                        return;
                    }

                    let advertencias = [];

                    datos.encontrados.forEach(function(item) {
                        let readonlyLote = '',
                            readonlyCaducidad = '',
                            readonlySerie = '';

                        if (item.advertencia) {
                            advertencias.push(item.advertencia);
                        }

                        let cantidadItems = parseInt(item.cantidad) || 1;
                        if (cantidadItems < 1) cantidadItems = 1;

                        for (let i = 0; i < cantidadItems; i++) {
                            var nuevaFila = `
                                <tr>
                                    <td>
                                        <input type="hidden" name="idarticuloarray[]" value="${item.id}">   
                                        <input type="hidden" name="seguimientoarray[]" value="${item.seguimiento}">
                                        <input type="text" class="form-control" value="${item.clave} - ${item.nombre}" readonly>
                                    </td>
                                    <input type="hidden" class="form-control" name="cantidadarray[]" value="1" readonly>
                                    <td><input type="text" class="form-control" name="lotearray[]" value="${item.lote}" ${readonlyLote}></td>
                                    <td>
                                        <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="${item.caducidad}" ${readonlyCaducidad}>
                                        <input type="hidden" name="caducidadmenor1anio[]" class="banderaCaducidad">
                                    </td>
                                    <td><input type="text" class="form-control" name="seriearray[]" value="${item.serie}" ${readonlySerie}></td>
                                    <td>
                                        <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                                    </td>
                                </tr>
                            `;
                            $("#productosBody").append(nuevaFila);
                            evaluarBanderaCaducidad($("#productosBody").find(".caducidadInput").last());
                            if (verificaCaducidadMenorUnAnio()) {
                                $("#seccion-documentos-cc").fadeIn();
                            }
                        }
                    });

                    // Mostrar advertencias si las hay
                    if (advertencias.length > 0) {
                        Swal.fire({
                            html: advertencias.join("<br>"),
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                    }

                    if (datos.no_encontrados.length > 0) {
                        let msg = "Las siguientes claves no se encontraron:\n\n";
                        datos.no_encontrados.forEach(function(item) {
                            msg += `Fila ${item.fila}: ${item.clave}\n`;
                        });
                        Swal.fire({
                            html: msg,
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success' // usa clases de Bootstrap
                            }
                        });
                    }
                },
                complete: function() {
                    $("#loading").hide();
                },
                error: function() {
                    Swal.fire({
                        html: "Error al procesar el archivo",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        }
                    });
                    $("#loading").hide();
                }
            });
        });

        $(document).on("change", ".caducidadInput", function() {
            const hoy = new Date();
            const unAnioDespues = new Date();
            unAnioDespues.setFullYear(hoy.getFullYear() + 1);

            const fechaCaducidad = new Date($(this).val());
            const banderaInput = $(this).siblings(".banderaCaducidad");

            if (fechaCaducidad < unAnioDespues) {
                banderaInput.val("1"); // menor a un año
            } else {
                banderaInput.val("0"); // mayor o igual a un año
            }

            if (verificaCaducidadMenorUnAnio()) {
                $("#seccion-documentos-cc").fadeIn();
            } else {
                $("#seccion-documentos-cc").fadeOut();
            }
        });

        $("#form-salida-inventario").on("submit", function(e) {
            let requiereProveedor = false;
            $(".banderaCaducidad").each(function() {
                if ($(this).val() === "1") {
                    requiereProveedor = true;
                    return false; // rompe each
                }
            });
            if (requiereProveedor) {
                const proveedor = $("#proveedor").val().trim();
                const correo = $("#correoproveedor").val().trim();
                if (!proveedor || !correo || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                    alert("Si hay productos con caducidad menor a un año, debes seleccionar un proveedor válido con correo.");
                    e.preventDefault();
                }
            }
        });

        $(document).on("click", ".eliminarFila", function() {
            $(this).closest("tr").remove();

            // Después de eliminar la fila, verifica si sigue habiendo caducidad menor a 1 año
            if (verificaCaducidadMenorUnAnio()) {
                console.log("Hay productos con caducidad menor a un año, proveedor obligatorio.");
            } else {
                console.log("No hay productos con caducidad menor a un año, proveedor opcional.");
                $("#seccion-documentos-cc").fadeOut();
            }
        });

    });

    function plantillaalmacen() {
        Swal.fire({
            title: 'Formato requerido para Excel',
            html: `
                <div style="text-align: left;">
                    <p>Antes de subir el archivo, asegúrate de que tenga el siguiente formato:</p>
                        <li><strong>Columna A:</strong> <code>CLAVE</code> (Alfanumérica)</li>
                        <li><strong>Columna B:</strong> <code>ARTICULO</code> (Alfanumérico)</li>
                        <li><strong>Columna C:</strong> <code>CANTIDAD</code> (Numérica)</li>
                        <li><strong>Columna D:</strong> <code>LOTE</code> (Alfanumérica)</li>
                        <li><strong>Columna E:</strong> <code>CADUCIDAD</code> (Fecha YYYY-MM-DD o formato compacto YYMMDD)</li>
                        <li><strong>Columna F:</strong> <code>SERIE</code> (Alfanumérica)</li>
                    </ul>
                    <p><strong>Ejemplo:</strong></p>
                    <table class="table table-bordered table-sm text-center">
                    <thead>
                        <tr>
                        <th>CLAVE</th>
                        <th>ARTICULO</th>
                        <th>CANTIDAD</th>
                        <th>LOTE</th>
                        <th>CADUCIDAD</th>
                        <th>SERIE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ABC123</td>
                            <td>Guantes de látex</td>
                            <td>5</td>
                            <td>LOTE123</td>
                            <td>2025-12-30</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>XYZ789</td>
                            <td>Gasas estériles</td>
                            <td>10</td>
                            <td>LOTE123</td>
                            <td>251230</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>XYZ789</td>
                            <td>Balón</td>
                            <td>1</td>
                            <td></td>
                            <td></td>
                            <td>SERIENUM56</td>
                        </tr>
                    </tbody>
                    </table>
                    <p><strong>Nota:</strong> La caducidad puede ingresarse como <code>2025-12-30</code> o en formato compacto <code>251230</code> (YYMMDD)</p>
                    <p>El archivo debe estar en formato <strong>.xlsx</strong></p>
                </div>
            `,
            icon: 'info',
            width: '60%',
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $("#plantillaArchivo").val(""); // Reinicia el input file
                $("#plantillaArchivo").click();
            }
        });
    }

    function guardar() {
        Swal.fire({
            text: '¿Seguro que deseas crear la solicitud de entrada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                if (!$("#invalmacen").val()) {
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invalmacen').focus();
                        }
                    });
                    return;
                }
                if ($("#invconcepto").val() == "") {
                    Swal.fire({
                        html: "Concepto es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invconcepto').focus();
                        }
                    });
                    return;
                }
                if ($("#invmotivo").val() == "") {
                    Swal.fire({
                        html: "Descripción es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invmotivo').focus();
                        }
                    });
                    return;
                }
                if ($("#proveedor").val() == "") {
                    Swal.fire({
                        html: "Proveedor es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#proveedor').focus();
                        }
                    });
                    return;
                }
                if ($("#correoproveedor").val() == "") {
                    Swal.fire({
                        html: "Correo Proveedor es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#correoproveedor').focus();
                        }
                    });
                    return;
                }
                // Validar formato de correo electrónico
                let correoaux = $("#correoproveedor").val();
                let regexCorreoaux = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!regexCorreoaux.test(correoaux)) {
                    Swal.fire({
                        html: "Ingrese un correo electrónico válido",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#correoproveedor').focus();
                        }
                    });
                    return;
                }

                // Validar que haya al menos un artículo agregado
                if ($("#productosBody tr").length === 0) {
                    Swal.fire({
                        html: "Debes agregar al menos un artículo antes de guardar.",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    return;
                }

                // Validación para campos lote, caducidad y serie según seguimiento
                let valid = true;
                let mensajeError = '';

                $("#productosBody tr").each(function(index, tr) {
                    const seguimiento = $(tr).find('input[name="seguimientoarray[]"]').val();
                    const $lote = $(tr).find('input[name="lotearray[]"]');
                    const $caducidad = $(tr).find('input[name="caducidadarray[]"]');
                    const $serie = $(tr).find('input[name="seriearray[]"]');
                    const articulo = $(tr).find('input[type="text"]').first().val();

                    // Solo la caducidad es obligatoria
                    if ($caducidad.val().trim() === '') {
                        mensajeError = `El artículo "${articulo}" requiere que el campo Caducidad esté completo.`;
                        valid = false;
                        $caducidad.focus();
                        return false;
                    }
                });

                if (!valid) {
                    Swal.fire({
                        html: mensajeError,
                        icon: 'warning',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    return;
                }

                // Validar fechas de caducidad para artículos con seguimiento 'L'
                let articulosCaducidadInvalida = [];

                $("#productosBody tr").each(function(index, tr) {
                    const seguimiento = $(tr).find('input[name="seguimientoarray[]"]').val();
                    const $caducidad = $(tr).find('input[name="caducidadarray[]"]');
                    const articulo = $(tr).find('input[type="text"]').first().val();

                    if (seguimiento === 'L') {
                        const fechaCaducidadStr = $caducidad.val();
                        if (fechaCaducidadStr) {
                            const fechaCaducidad = new Date(fechaCaducidadStr);
                            const hoy = new Date();
                            hoy.setHours(0, 0, 0, 0); // Limpiar horas

                            if (fechaCaducidad < hoy) {
                                articulosCaducidadInvalida.push(articulo);
                            }
                        }
                    }
                });

                if (articulosCaducidadInvalida.length > 0) {
                    Swal.fire({
                        html: `Los siguientes artículos tienen fecha de caducidad menor a hoy y no pueden agregarse:<br><strong>${articulosCaducidadInvalida.join("<br>")}</strong>`,
                        icon: "error",
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                    return;
                }

                // Verifica si hay productos con caducidad menor a un año
                let requiereProveedor = false;
                $(".banderaCaducidad").each(function() {
                    if ($(this).val() === "1") {
                        requiereProveedor = true;
                        return false; // rompe el each
                    }
                });

                const proveedor = $("#proveedor").val().trim();
                const idProveedor = $("#idproveedor").val().trim();
                const correo = $("#correoproveedor").val().trim();
                const correoRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                // Si se requiere proveedor y no está bien capturado
                if (requiereProveedor) {
                    if (!proveedor || !idProveedor || !correo || !correoRegex.test(correo)) {
                        Swal.fire({
                            html: "Hay productos con caducidad menor a un año. Debes seleccionar un proveedor válido con correo.",
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success'
                            },
                            didClose: () => {
                                $('#proveedor').focus();
                            }
                        });
                        return;
                    }
                    // Si no se requiere proveedor pero hay uno capturado, validar su correo si lo hay
                    if (proveedor !== "" && idProveedor !== "") {
                        if (correo === "" || !correoRegex.test(correo)) {
                            Swal.fire({
                                html: "El proveedor seleccionado no tiene un correo válido.",
                                icon: "warning",
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                didClose: () => {
                                    $('#correoproveedor').focus();
                                }
                            });
                            return;
                        }
                    }
                }

                // Validar Carta Canje y Num Delivery si son requeridos
                if (requiereProveedor) {
                    const archivoCC = $('#archivo_carta_canje_general')[0];
                    const numDelivery = $('#num_delivery_general').val().trim();

                    if (!archivoCC.files || archivoCC.files.length === 0) {
                        $('#archivo_carta_canje_general').addClass('is-invalid');
                        Swal.fire({
                            html: "Debe subir la Carta Canje para los artículos con caducidad menor a 1 año.",
                            icon: "warning",
                            customClass: { confirmButton: 'btn btn-success' }
                        });
                        return;
                    } else {
                        $('#archivo_carta_canje_general').removeClass('is-invalid');
                    }

                    if (numDelivery === "") {
                        $('#num_delivery_general').addClass('is-invalid');
                        Swal.fire({
                            html: "Debe ingresar el Número de Delivery.",
                            icon: "warning",
                            customClass: { confirmButton: 'btn btn-success' },
                            didClose: () => $('#num_delivery_general').focus()
                        });
                        return;
                    } else {
                        $('#num_delivery_general').removeClass('is-invalid');
                    }
                }

                // Ahora sí: todo está validado
                var formData = new FormData(document.getElementById("form-salida-inventario"));
                $.ajax({
                    url: '../ajax/entradasalida.registro.php',
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
                                html: "Registro guardado con éxito",
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

            } else {
                return false;
            }
        });
    }

    function verificaCaducidadMenorUnAnio() {
        let tieneCaducidadMenor = false;
        $(".banderaCaducidad").each(function() {
            if ($(this).val() === "1") {
                tieneCaducidadMenor = true;
                return false; // rompe el each
            }
        });
        return tieneCaducidadMenor;
    }

    function evaluarBanderaCaducidad($input) {
        let hoy = new Date();
        let unAnioDespues = new Date();
        unAnioDespues.setFullYear(hoy.getFullYear() + 1);

        let dateVal = $input.val();
        let banderaInput = $input.siblings(".banderaCaducidad");

        if (!dateVal) {
            banderaInput.val("0");
            return;
        }

        let fechaCaducidad = new Date(dateVal);
        if (fechaCaducidad < unAnioDespues) {
            banderaInput.val("1");
        } else {
            banderaInput.val("0");
        }
    }
</script>