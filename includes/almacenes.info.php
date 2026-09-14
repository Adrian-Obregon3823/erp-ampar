<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Interfaz para ver información del Almacen
*********************************************************************************
*/
?>
<?php
$busqueda = (isset($_GET['busqueda'])) ? $_GET['busqueda'] : '';
$almacenes = new almacenes();
$info = $almacenes->getinfoalmacen(base64_decode($_GET['almacenid']));
$res = $almacenes->getarticulosalmacenbyalmacenid(base64_decode($_GET['almacenid']), $busqueda);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Detalle de Almacén <strong><?= $info[0]['ALMACEN_FOLIO'] ?></strong></h4>
        </div>
        <div class="card-body">
            <!-- Información de Almacen -->
            <div class="table-responsive">
                <table class="table" width="100%">
                    <tr>
                        <th width="100px">Tipo</th>
                        <td><?= $info[0]['TIPOALMACEN_NOMBRE'] ?> (<span style="color:#<?= $info[0]['STATUS_COLOR'] ?>"><?= $info[0]['STATUS_NOMBRE'] ?></span>)</td>
                    </tr>
                    <?php if ($info[0]['ALMACEN_TIPOALMACEN'] == 3) { ?>
                        <tr>
                            <th>Tipo de Maleta</th>
                            <td><?= $info[0]['TIPOMALETA_NOMBRE'] ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th width="100px">Categoría</th>
                        <td><strong><?= $info[0]['SUCURSAL_NOMBRE'] ?></strong></td>
                    </tr>
                    <tr>
                        <th>Nombre</th>
                        <td><?= $info[0]['ALMACEN_NOMBRE'] ?></td>
                    </tr>
                    <tr>
                        <th>Descripción</th>
                        <td><?= $info[0]['ALMACEN_DESCRIPCION'] ?></td>
                    </tr>
                    <?php if ($info[0]['ALMACEN_TIPOALMACEN'] != 3) { ?>
                        <tr>
                            <th>Dirección</th>
                            <td>
                                <?= $info[0]['ALMACEN_CALLE'] ?> <?= $info[0]['ALMACEN_NUMEXT'] ?> <?= ($info[0]['ALMACEN_NUMINT']) ? 'interior ' . $info[0]['ALMACEN_NUMINT'] : '' ?><?= ($info[0]['ALMACEN_COLONIA']) ? ',<br>colonia ' . $info[0]['ALMACEN_COLONIA'] : '' ?><?= ($info[0]['ALMACEN_CP']) ? ', C.P ' . $info[0]['ALMACEN_CP'] : '' ?><br>
                                <?= $info[0]['MUNICIPIO_NOMBRE'] ?> <?= $info[0]['ESTADO_NOMBRE'] ?> <?= $info[0]['PAIS_NOMBRE'] ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Teléfono</th>
                            <td><?= $info[0]['ALMACEN_TELEFONO'] ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <br>
            <!-- Listado de productos -->
            <div class="row mb-3 align-items-center">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <h4 class="mb-0">Listado de Productos</h4>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                        <form class="d-flex" onsubmit="buscarProducto(event)" style="flex-grow: 1; max-width: 300px;">
                            <input type="text" class="form-control me-2" id="inputBusqueda" placeholder="Folio, clave o nombre" value="<?= $busqueda ?>">
                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                        </form>
                        <button id="btnDescargarExcel" class="btn btn-success mt-2 mt-md-0">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar Excel
                        </button>
                    </div>
                </div>
            </div>
            <?php
            if ($res == 0) {
                echo '<div class="alert alert-warning text-center">No se encontraron productos</div>';
            } else {
            ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h4>Total de productos: <strong><?= count($res) ?></strong></h4>
                        </div>
                    </div>
                </div>
                <div class="card card-rounded">
                    <div class="card-body">
                        <div class="table table-responsive">
                            <table class="table table-bordered" style="font-size:9px !important">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30px"></th>
                                        <th>Folio</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Almacén</th>
                                        <th>Sucursal</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($res as $p): ?>
                                        <tr>
                                            <td>
                                                <a href="#" class="btnAbrirBitacora"
                                                    data-url="../includes/bitacora.detalle.php?stockid=<?= base64_encode($p['STOCK_ID']) ?>&return=<?= urlencode(base64_encode($_SERVER['REQUEST_URI'])) ?>"
                                                    data-title="Bitácora detalle">
                                                    <img src="../img/bitacora.jpg" title="Bitácora" style="width:20px; height:auto;">
                                                </a>
                                            </td>
                                            <td><strong><?= $p['STOCK_FOLIO'] ?></strong></td>
                                            <td><?= $p['CLAVE_ARTICULO'] ?></td>
                                            <td><?= $p['NOMBRE'] ?></td>
                                            <td><?= $p['ALMACEN_NOMBRE'] ?></td>
                                            <td><?= $p['SUCURSAL_NOMBRE'] ?></td>
                                            <td>
                                                <?php
                                                $estado = $p['STOCK_STOCKSTATUSID'];
                                                $badge = match ($estado) {
                                                    1 => 'success',
                                                    2 => 'warning',
                                                    3 => 'secondary',
                                                    default => 'light'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badge ?>" style="font-size: 0.2rem;">&nbsp;</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>
<iframe id="iframeDescarga" style="display: none;"></iframe>
<script>
    function buscarProducto(event) {
        event.preventDefault(); // Evita que recargue la página

        const valorBusqueda = document.getElementById("inputBusqueda").value;

        $('#loading').show();

        $.ajax({
            url: '../includes/almacenes.info.php?almacenid=<?= $_GET['almacenid'] ?>&busqueda=' + $("#inputBusqueda").val(), // Tu script PHP que hace la búsqueda y regresa HTML
            type: 'GET',
            data: {
                buscar: valorBusqueda
            },
            success: function(response) {
                $('#modalglobal .modal-body').html(response);
            },
            error: function() {
                $('#modalglobal .modal-body').html('<p>Error al cargar búsqueda.</p>');
            },
            complete: function() {
                $('#loading').hide();
            }
        });
    }

    // Descargar Excel sin abrir otra pestaña
    document.getElementById("btnDescargarExcel").addEventListener("click", function() {

        const valorAlmacenId = "<?= rawurlencode($_GET['almacenid']) ?>";
        const valorBusqueda = encodeURIComponent(document.getElementById("inputBusqueda").value);

        // Mostrar loading
        document.getElementById("loading").style.display = "block";

        fetch(`../includes/almacenes.info.exportarexcel.php?almacenid=${valorAlmacenId}&busqueda=${valorBusqueda}`, {
                method: 'GET'
            })
            .then(async response => {
                const contentType = response.headers.get('Content-Type');

                if (contentType && contentType.includes('application/json')) {
                    // Es JSON => puede ser mensaje de error
                    const json = await response.json();
                    if (json.error) {
                        throw new Error(json.message);
                    }
                    // Si JSON sin error, puede manejarse aquí (opcional)
                }
                if (!response.ok) {
                    // Otro tipo de error HTTP
                    throw new Error("Error en la descarga.");
                }
                // Respuesta válida, retornar blob para descarga
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = "Detalle.<?= $info[0]['ALMACEN_FOLIO'] ?>.<?= date("Y-m-d") ?>.xlsx";
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                Swal.fire({
                    html: error.message,
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
            })
            .finally(() => {
                document.getElementById("loading").style.display = "none";
            });
    });
</script>