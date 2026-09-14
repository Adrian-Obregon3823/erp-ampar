<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nuevo Tipo Maleta</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nuevo-tipomaleta">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="tmfamilia">Familia <span class="text-danger">*</span></label>
                            <select class="form-control" id="tmfamilia" name="tmfamilia"></select>
                        </div>
                        <div class="form-group">
                            <label for="tmdivision">División <span class="text-danger">*</span></label>
                            <select class="form-control" id="tmdivision" name="tmdivision" disabled></select>
                        </div>
                        <div class="form-group">
                            <label for="tmnombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tmnombre" name="tmnombre" placeholder="Nombre de Tipo de maleta" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="tmdescripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="tmdescripcion" name="tmdescripcion" placeholder="Descripción de Tipo de maleta" maxlength="250"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <input type="file" id="tmplantillaArchivo" accept=".xlsx" style="display:none">
                                <a href="../docs/TipoMaletaPlantilla.xlsx" download class="btn btn-light text-dark border border-success">
                                    <i class="fa fa-download"></i> Descargar Plantilla
                                </a>
                                <a onclick="tmplantillaExcel();" class="btn btn-success text-white border border-success" style="cursor:pointer;">
                                    <i class="fa fa-upload"></i> Subir Plantilla Excel
                                </a>
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group mb-0">
                                    <input type="hidden" id="tmidarticulo" name="tmidarticulo">
                                    <input type="hidden" id="tmcvearticulo" name="tmcvearticulo">
                                    <input type="text" class="form-control" id="tmarticulo" name="tmarticulo" placeholder="Artículo">
                                </div>
                            </div>
                            <div class="col-12 col-md-3 mb-2 mb-md-0">
                                <div class="form-group mb-0">
                                    <input type="number" class="form-control" id="tmcantidadsugerida" name="tmcantidadsugerida" placeholder="Cantidad" min="1">
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="button" class="btn btn-warning w-100" id="tmagregararticulo">Agregar Artículo</button>
                            </div>
                        </div>
                        <div class="table-responsive w-100 mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="150px">Clave</th>
                                        <th>Artículo</th>
                                        <th width="100px">Cantidad</th>
                                        <th width="50px">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tmarticulosBody"></tbody>
                            </table>
                        </div>
                        <br>
                        <button type="button" class="btn btn-success" onclick="guardartm();">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        //Get Familias
        var itemsfamilia = "";
        $.getJSON("../ajax/get.familias.php", function(data) {
            itemsfamilia += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsfamilia += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#tmfamilia").html(itemsfamilia);
        });

        // Al cambiar familia, cargar divisiones
        $("#tmfamilia").change(function() {
            var familiaid = $(this).val();
            var itemsdiv = "<option value=''></option>";
            $("#tmdivision").html(itemsdiv).prop("disabled", true);

            if (familiaid !== "") {
                $.getJSON("../ajax/get.divisiones.php?familiaid=" + familiaid, function(data) {
                    if (data && data[0] && data[0].ID !== "") {
                        $.each(data, function(index, item) {
                            itemsdiv += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                        });
                        $("#tmdivision").html(itemsdiv).prop("disabled", false);
                    }
                });
            }
        });


    });

    async function tmobtenerArticulos() {
        return await $.getJSON("../ajax/get.articulos.catalogo.php");
    }

    tmobtenerArticulos().then(function(resultarticulos) {
        $("#tmarticulo").autocomplete({
            source: function(request, response) {
                response($.map(resultarticulos, function(obj) {
                    var label = obj.CLAVE_ARTICULO + ' - ' + obj.NOMBRE.toUpperCase();
                    if (label.includes(request.term.toUpperCase())) {
                        return {
                            label: label,
                            id: obj.ID,
                            nombre: obj.NOMBRE,
                            cve: obj.CLAVE_ARTICULO
                        };
                    }
                    return null;
                }).filter(Boolean));
            },
            minLength: 1,
            select: function(event, ui) {
                $("#tmcvearticulo").val(ui.item.cve);
                $("#tmarticulo").val(ui.item.nombre);
                $("#tmidarticulo").val(ui.item.id);
                return false;
            }
        });
    });

    $("#tmagregararticulo").click(function() {
        var idArticulo = $("#tmidarticulo").val();
        var cve = $("#tmcvearticulo").val();
        var articulo = $("#tmarticulo").val();
        var cantidad = $("#tmcantidadsugerida").val();

        if (idArticulo === "" || articulo === "" || cantidad === "" || cantidad < 0) {
            Swal.fire({
                html: "Seleccione un artículo válido y una cantidad sugerida mayor o igual a 0.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            });
            return;
        }

        // 🚨 Validación: ¿ya existe ese artículo?
        var yaExiste = false;
        $("input[name='tmidarticuloarray[]']").each(function() {
            if ($(this).val() === idArticulo) {
                yaExiste = true;
                return false; // salir del each
            }
        });

        if (yaExiste) {
            Swal.fire({
                html: "Este artículo ya ha sido agregado.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            });
            return;
        }

        // ✅ Si no existe, lo agregamos
        var nuevaFila = `
            <tr>
                <td>
                    <input type="hidden" name="tmidarticuloarray[]" value="${idArticulo}">
                    <input type="text" class="form-control" name="tmcvearticuloarray[]" value="${cve}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" name="tmarticuloarray[]" value="${articulo}" readonly>
                </td>
                <td><input type="number" class="form-control" name="tmcantidadarray[]" value="${cantidad}" min="1" readonly></td>
                <td>
                    <img src="../img/eliminar.png" alt="Eliminar" class="tmeliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                </td>
            </tr>
        `;

        $("#tmarticulosBody").append(nuevaFila);

        // Limpiar los campos después de agregar
        $("#tmidarticulo").val("");
        $("#tmcvearticulo").val("");
        $("#tmarticulo").val("");
        $("#tmcantidadsugerida").val("");
    });

    // Eliminar fila
    $(document).on("click", ".tmeliminarFila", function() {
        $(this).closest("tr").remove();
    });

    // Función para mostrar instrucciones y abrir selector de archivo
    function tmplantillaExcel() {
        Swal.fire({
            title: 'Formato requerido para Excel',
            html: `
                <div style="text-align: left;">
                    <p>Antes de subir el archivo, asegúrate de que tenga el siguiente formato:</p>
                    <ul class="text-left">
                        <li><strong>Columna A:</strong> <code>CLAVE</code> (Clave del artículo)</li>
                        <li><strong>Columna B:</strong> <code>ARTICULO</code> (Nombre, solo referencia)</li>
                        <li><strong>Columna C:</strong> <code>CANTIDAD</code> (Cantidad sugerida)</li>
                    </ul>
                    <p><strong>Ejemplo:</strong></p>
                    <table class="table table-bordered table-sm text-center">
                    <thead>
                        <tr>
                            <th>CLAVE</th>
                            <th>ARTICULO</th>
                            <th>CANTIDAD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ABC123</td>
                            <td>Guantes de látex</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td>XYZ789</td>
                            <td>Gasas estériles</td>
                            <td>5</td>
                        </tr>
                    </tbody>
                    </table>
                    <p class="text-info"><i class="fa fa-info-circle"></i> Puedes usar fórmulas en Excel, se leerá el valor calculado.</p>
                    <p>El archivo debe estar en formato <strong>.xlsx</strong></p>
                </div>
            `,
            icon: 'info',
            width: '50%',
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
                $("#tmplantillaArchivo").val("");
                $("#tmplantillaArchivo").click();
            }
        });
    }

    // Procesar archivo Excel
    $("#tmplantillaArchivo").change(function() {
        var archivo = this.files[0];
        if (!archivo) return;

        var formData = new FormData();
        formData.append("archivo", archivo);

        $.ajax({
            url: "../ajax/tipomaletas.cargaplantilla.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(datos) {
                if (datos.error) {
                    Swal.fire({
                        html: "Error: " + datos.error,
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    return;
                }

                let agregados = 0;
                let duplicados = [];

                // Agregar artículos encontrados
                datos.encontrados.forEach(function(item) {
                    // Verificar si ya existe en la tabla
                    let yaExiste = false;
                    $("input[name='tmidarticuloarray[]']").each(function() {
                        if ($(this).val() === String(item.id)) {
                            yaExiste = true;
                            duplicados.push(item.clave);
                            return false;
                        }
                    });

                    if (!yaExiste) {
                        var nuevaFila = `
                            <tr>
                                <td>
                                    <input type="hidden" name="tmidarticuloarray[]" value="${item.id}">
                                    <input type="text" class="form-control" name="tmcvearticuloarray[]" value="${item.clave}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="tmarticuloarray[]" value="${item.nombre}" readonly>
                                </td>
                                <td><input type="number" class="form-control" name="tmcantidadarray[]" value="${item.cantidad}" min="1" readonly></td>
                                <td>
                                    <img src="../img/eliminar.png" alt="Eliminar" class="tmeliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                                </td>
                            </tr>
                        `;
                        $("#tmarticulosBody").append(nuevaFila);
                        agregados++;
                    }
                });

                // Construir mensaje de resultado
                let mensajes = [];

                if (agregados > 0) {
                    mensajes.push(`<span class="text-success">✔ ${agregados} artículo(s) agregado(s) correctamente.</span>`);
                }

                if (duplicados.length > 0) {
                    mensajes.push(`<span class="text-warning">⚠ ${duplicados.length} artículo(s) ya existían: ${duplicados.join(", ")}</span>`);
                }

                if (datos.no_encontrados.length > 0) {
                    let noEncontradosList = datos.no_encontrados.map(x => `Fila ${x.fila}: ${x.clave}`).join("<br>");
                    mensajes.push(`<span class="text-danger">✖ Claves no encontradas:<br>${noEncontradosList}</span>`);
                }

                if (datos.errores && datos.errores.length > 0) {
                    mensajes.push(`<span class="text-danger">✖ Errores:<br>${datos.errores.join("<br>")}</span>`);
                }

                Swal.fire({
                    title: 'Resultado de carga',
                    html: mensajes.join("<br><br>"),
                    icon: agregados > 0 ? "success" : "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
            },
            complete: function() {
                $("#loading").hide();
            },
            error: function() {
                Swal.fire({
                    html: "Error al procesar el archivo",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                $("#loading").hide();
            }
        });
    });

    function guardartm() {
        Swal.fire({
            text: '¿Seguro que deseas agregar el tipo de maleta?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, agregar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#tmfamilia").val() == "") {
                    Swal.fire({
                        html: "Familia es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#tmfamilia').focus();
                        }
                    });
                } else if ($("#tmdivision").val() == "") {
                    Swal.fire({
                        html: "División es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#tmdivision').focus();
                        }
                    });
                } else if ($("#tmnombre").val() == "") {
                    Swal.fire({
                        html: "Nombre es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#tmnombre').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#tmdescripcion").val() == "") {
                    Swal.fire({
                        html: "Descripción es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#tmdescripcion').focus();
                        }
                    });
                } else if ($("#tmarticulosBody tr").length === 0) {
                    Swal.fire({
                        html: "Debes agregar al menos un artículo al tipo de maleta",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#tmarticulo').focus();
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-nuevo-tipomaleta"));
                    $.ajax({
                        url: '../ajax/tipomaletas.nuevo.php',
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
                                    html: "Tipo Maleta guardado con éxito",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
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