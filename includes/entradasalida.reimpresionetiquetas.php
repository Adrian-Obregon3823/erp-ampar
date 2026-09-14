<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include_once("../includes/head.php"); ?>
    <!-- Cliente de Impresión Local -->
    <script src="../js/printer-client.js"></script>
    <style>
        /* Estilos para reparar la apariencia del autocompletado */
        .ui-autocomplete {
            z-index: 10000 !important;
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 !important;
        }
        .ui-menu-item {
            list-style: none !important;
        }
        .ui-menu-item-wrapper {
            padding: 10px 15px !important;
            cursor: pointer !important;
            color: #374151 !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border-bottom: 1px solid #f3f4f6 !important;
            display: block !important;
        }
        .ui-menu-item:last-child .ui-menu-item-wrapper {
            border-bottom: none !important;
        }
        .ui-menu-item-wrapper:hover, 
        .ui-menu-item-wrapper.ui-state-active {
            background-color: #f3f4f6 !important;
            color: #1f2937 !important;
            border-radius: 0 !important;
            border: none !important;
            margin: 0 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Reimpresión de Etiqueta</h4>
                    <button type="button" class="btn btn-primary" onclick="imprimir();" id="btnImprimir" style="display:none;">Imprimir</button>
                </div>
                <div class="card-body">
                    <div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
                        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Cargando...</span>
                        </div>
                    </div>
                    
                    <form onsubmit="buscarFolio(event)" class="mb-4 d-flex" autocomplete="off">
                        <input type="text" id="folioInput" class="form-control mr-2" placeholder="Ej: A00123-24" style="max-width: 300px;" required autofocus autocomplete="off">
                        <button type="submit" class="btn btn-info">Agregar</button>
                    </form>

                    <div style="margin-bottom: 10px; display: none;" id="selectAllContainer">
                        <label style="cursor: pointer;">
                            <input type="checkbox" id="selectAll" checked onchange="toggleSelectAll()">
                            <b>Seleccionar todo</b>
                        </label>
                    </div>

                    <table style="padding: 10px; width: 100%;" id="tablaEtiquetas">
                        <tbody>
                            <!-- Filas agregadas por AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("../includes/foot.php"); ?>
    
    <script>
        // ============================================
        // CONFIGURACIÓN DEL CLIENTE DE IMPRESIÓN
        // ============================================

        const printer = createPrinterClient({
            host: 'localhost', 
            port: '8090', 
            protocol: 'http', 
            debug: true, 
            useProxy: false 
        });

        printer.healthCheck().then(result => {
            if (result.success) {
                console.log('✅ API de impresión local disponible');
            } else {
                console.warn('⚠️ API de impresión no disponible:', result.error);
            }
        });

        function toggleSelectAll() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('.etiqueta-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        }

        function updateSelectAll() {
            var checkboxes = document.querySelectorAll('.etiqueta-checkbox');
            var selectAll = document.getElementById('selectAll');
            var allChecked = true;
            checkboxes.forEach(function(cb) {
                if (!cb.checked) allChecked = false;
            });
            selectAll.checked = allChecked;
        }

        function checkTableData() {
            var rowCount = document.querySelectorAll('#tablaEtiquetas tbody tr').length;
            if (rowCount > 0) {
                document.getElementById('btnImprimir').style.display = 'block';
                document.getElementById('selectAllContainer').style.display = 'block';
            } else {
                document.getElementById('btnImprimir').style.display = 'none';
                document.getElementById('selectAllContainer').style.display = 'none';
            }
            updateSelectAll();
        }

        function removerFila(rowId) {
            var row = document.getElementById(rowId);
            if (row) {
                row.remove();
                checkTableData();
            }
        }

        async function buscarFolio(e) {
            e.preventDefault();
            var folio = document.getElementById('folioInput').value.trim();
            if (folio === "") return;

            // Verificar si ya existe en la tabla
            if (document.querySelector(`.etiqueta-checkbox[value='${folio}']`)) {
                Swal.fire({
                    title: "Atención",
                    text: "Esta etiqueta ya está en la lista.",
                    icon: "warning"
                });
                document.getElementById('folioInput').value = '';
                document.getElementById('folioInput').focus();
                return;
            }

            document.getElementById('spinnerOverlay').style.display = 'flex';

            try {
                const response = await fetch(`../ajax/entradasalida.buscar_etiqueta.php?folio=${encodeURIComponent(folio)}`);
                const text = await response.text();
                
                document.getElementById('spinnerOverlay').style.display = 'none';

                if (text.includes("NOT_FOUND")) {
                    Swal.fire({
                        title: "No encontrado",
                        text: "No se encontró ninguna etiqueta con ese folio.",
                        icon: "error"
                    });
                } else {
                    var tbody = document.querySelector('#tablaEtiquetas tbody');
                    tbody.insertAdjacentHTML('beforeend', text);
                    document.getElementById('folioInput').value = '';
                    checkTableData();
                }
            } catch (error) {
                document.getElementById('spinnerOverlay').style.display = 'none';
                Swal.fire("Error", "Ocurrió un problema al buscar la etiqueta.", "error");
            }
            document.getElementById('folioInput').focus();
        }

        function getSelectedFolios() {
            var selected = [];
            document.querySelectorAll('.etiqueta-checkbox:checked').forEach(function(cb) {
                selected.push(cb.value);
            });
            return selected;
        }

        async function imprimir() {
            var folios = getSelectedFolios();

            if (folios.length === 0) {
                Swal.fire({
                    html: "Selecciona al menos una etiqueta para imprimir",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-warning'
                    }
                });
                return;
            }

            document.getElementById('spinnerOverlay').style.display = 'flex';

            try {
                let result;
                if (folios.length === 1) {
                    result = await printer.imprimirFolio(folios[0]);
                } else {
                    result = await printer.imprimirFolios(folios);
                }

                document.getElementById('spinnerOverlay').style.display = 'none';

                Swal.fire({
                    html: `Impresión enviada correctamente<br><small>${folios.length} etiqueta(s)</small>`,
                    icon: "success",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });

            } catch (error) {
                document.getElementById('spinnerOverlay').style.display = 'none';
                let errorMsg = error.message;
                let helpText = '';
                if (errorMsg.includes('Failed to fetch') || errorMsg.includes('NetworkError')) {
                    errorMsg = 'No se pudo conectar a la impresora local';
                    helpText = '<br><small>Verifica que la API de impresión esté corriendo en tu computadora en el puerto 8090</small>';
                }
                Swal.fire({
                    title: "Error de Impresión",
                    html: errorMsg + helpText,
                    icon: "error",
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
                console.error('Error de impresión:', error);
            }
        }

        $(document).ready(function() {
            $("#folioInput").autocomplete({
                source: "../ajax/entradasalida.autocomplete_folio.php",
                minLength: 2,
                select: function(event, ui) {
                    // Actualiza el valor y lanza la búsqueda automáticamente
                    $("#folioInput").val(ui.item.value);
                    buscarFolio(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>
