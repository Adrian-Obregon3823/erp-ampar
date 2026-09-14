<?php include_once("../includes/includes.php"); ?>
<?php include_once("../includes/head.php"); ?>
<!-- Cliente de Impresión Local -->
<script src="../js/printer-client.js"></script>
<?php
$entradasalida = new entradasalida();
$res = $entradasalida->getinfostockbyentradasalidabyid(base64_decode($_GET['esid']));

// Obtener los folios para JavaScript
$folios = array_column($res, 'STOCK_FOLIO');
?>
<div class="col-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Entrada de Almacén </h4>
            <button type="button" class="btn btn-primary" onclick="imprimir();">Imprimir</button>
        </div>
        <div class="card-body">
            <div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
                <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="cursor: pointer;">
                    <input type="checkbox" id="selectAll" checked onchange="toggleSelectAll()">
                    <b>Seleccionar todo</b>
                </label>
            </div>
            <table style="padding: 10px;">
                <?php foreach ($res as $index => $re) { ?>
                    <tr>
                        <td style="padding: 10px; vertical-align: middle;">
                            <input type="checkbox" class="etiqueta-checkbox" value="<?= $re['STOCK_FOLIO'] ?>" checked onchange="updateSelectAll()">
                        </td>
                        <td style="padding: 10px;">
                            <img src="../includes/entradasalida.cb.php?code=<?= $re['STOCK_FOLIO'] ?>" style="height:110px; width:300px; display:block; margin:0 auto;"><br>
                        </td>
                        <td style="padding: 10px;">
                            <img src="../includes/entradasalida.qr.php?code=<?= base64_encode($re['STOCK_ID']) ?>" style="height:110px; width:110px; display:block; margin:0 auto;"><br>
                        </td>
                        <td style="padding: 10px;">
                            <b style="font-size:20px"><?= $re['STOCK_FOLIO']; ?></b><br>
                            <?= $re['CLAVE_ARTICULO'] ?><br>
                            <?= $re['ARTICULO_NOMBRE'] ?><br>
                            <?= $re['ESDET_LOTE'] ?><br>
                            <?= $re['ESDET_CADUCIDAD'] ?><br>
                            <?= $re['ESDET_SERIE'] ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>

        </div>
    </div>
</div>
<script>
    var allFolios = <?= json_encode($folios) ?>;

    // ============================================
    // CONFIGURACIÓN DEL CLIENTE DE IMPRESIÓN
    // ============================================

    // Crear cliente que conecta al localhost DEL CLIENTE (su propia computadora)
    const printer = createPrinterClient({
        host: 'localhost', // o '127.0.0.1' - API local del cliente
        port: '8090', // Puerto de tu API de impresión
        protocol: 'http', // Cambiar a 'https' si tienes SSL en la API local
        debug: true, // Ver logs en consola (desactivar en producción)
        useProxy: false // No usar proxy para localhost
    });

    // Verificar conexión al cargar (opcional)
    printer.healthCheck().then(result => {
        if (result.success) {
            console.log('✅ API de impresión local disponible');
        } else {
            console.warn('⚠️ API de impresión no disponible:', result.error);
            if (window.location.protocol === 'https:') {
                console.info('ℹ️ Estás en HTTPS. Si la API local está en HTTP, se usará el proxy PHP automáticamente.');
                console.info('ℹ️ O configura certificado SSL en tu API local para conexión directa.');
            }
        }
    });

    // ============================================
    // FUNCIONES DE LA INTERFAZ
    // ============================================

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

        // Mostrar spinner
        document.getElementById('spinnerOverlay').style.display = 'flex';

        try {
            let result;

            // Imprimir según cantidad de folios
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

            // Mensajes de error específicos
            let errorMsg = error.message;
            let helpText = '';

            if (errorMsg.includes('Failed to fetch') || errorMsg.includes('NetworkError')) {
                errorMsg = 'No se pudo conectar a la impresora local';
                helpText = '<br><small>Verifica que la API de impresión esté corriendo en tu computadora en el puerto 8090</small>';
            } else if (errorMsg.includes('timeout') || errorMsg.includes('aborted')) {
                errorMsg = 'La impresora no respondió a tiempo';
                helpText = '<br><small>Verifica que la API de impresión esté funcionando correctamente</small>';
            } else if (errorMsg.includes('Mixed Content') || errorMsg.includes('blocked')) {
                errorMsg = 'Conexión bloqueada por seguridad del navegador';
                helpText = '<br><small>Intentando usar conexión segura alternativa...</small>';
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
</script>
<?php include_once("../includes/foot.php"); ?>