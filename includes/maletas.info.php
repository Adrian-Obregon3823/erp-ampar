<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php include_once('../lib/phpqrcode/qrlib.php'); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Interfaz para ver información de Maletas
*********************************************************************************
*/
?>
<?php
$busqueda = (isset($_GET['busqueda'])) ? $_GET['busqueda'] : '';
$almacenes = new almacenes();
$maletas = new maletas();
$info = $maletas->getinfomaleta(base64_decode($_GET['maletaid']));
$res = $almacenes->getarticulosalmacenbyalmacenid(base64_decode($_GET['maletaid']), $busqueda);
$resarticulos = $maletas->getinfomaletaexistenciasbymaletaid(base64_decode($_GET['maletaid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Detalle de Maleta <strong><?= $info[0]['ALMACEN_FOLIO'] ?></strong></h4>
        </div>
        <div class="card-body">
            <!-- Información General de Almacen -->
            <div class="table-responsive">
                <table class="table" width="100%">
                    <tr>
                        <th colspan=2><img src="../img/rfid2.png" width="40px" alt="RFID" title="Imprimir etiqueta RFID" onclick="imprimirfid('<?= $info[0]['ALMACEN_NOMBRE'] ?>');" style="cursor:pointer"></th>
                    </tr>
                    <tr>
                        <th width="120px">Tipo de Maleta</th>
                        <td><?= $info[0]['TIPOMALETA_NOMBRE'] ?> (<span style="color:#<?= $info[0]['STATUS_COLOR'] ?>"><?= $info[0]['STATUS_NOMBRE'] ?></span>)</td>
                    </tr>
                    <tr>
                        <th width="120px">Familia</th>
                        <td><?= $info[0]['FAMILIA_NOMBRE'] ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th width="120px">División</th>
                        <td><?= $info[0]['DIVISION_NOMBRE'] ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th width="120px">Tipo de Almacén</th>
                        <td><strong><?= $info[0]['PADRE_TIPOALMACEN_NOMBRE'] ?></strong></td>
                    </tr>
                    <tr>
                        <th width="100px">Almacén</th>
                        <td><strong><?= $info[0]['PADRE_ALMACEN_NOMBRE'] ?></strong></td>
                    </tr>
                    <tr>
                        <th>Nombre</th>
                        <td><?= $info[0]['ALMACEN_NOMBRE'] ?></td>
                    </tr>
                    <tr>
                        <th>Descripción</th>
                        <td><?= $info[0]['ALMACEN_DESCRIPCION'] ?></td>
                    </tr>
                </table>
            </div>
            <br>
            <!-- Listado de productos -->
            <div class="row mb-3 align-items-center">
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <h4 class="mb-0">Listado de Productos</h4>
                </div>
                <div class="col-12 col-md-8">
                    <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
                        <form class="d-flex me-2 flex-grow-1 flex-md-grow-0" onsubmit="buscarProductoMaleta(event)">
                            <input type="text" class="form-control me-2" id="inputBusquedaMaleta" placeholder="Folio, clave o nombre" value="<?= $busqueda ?>">
                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                        </form>
                        <button id="btnDescargarExcelMaleta" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar Excel
                        </button>
                        <button id="btnPorcentajesMaleta" class="btn btn-warning">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Porcentajes
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
                                        <th>Caducidad</th>
                                        <th>Almacén</th>
                                        <th>Sucursal</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Ordenar por fecha de caducidad (los más próximos primero, los sin fecha al final)
                                    usort($res, function ($a, $b) {
                                        $aHasCaducidad = !empty($a['STOCK_CADUCIDAD']);
                                        $bHasCaducidad = !empty($b['STOCK_CADUCIDAD']);
                                        if ($aHasCaducidad && !$bHasCaducidad) return -1;
                                        if (!$aHasCaducidad && $bHasCaducidad) return 1;
                                        if ($aHasCaducidad && $bHasCaducidad) {
                                            return strtotime($a['STOCK_CADUCIDAD']) - strtotime($b['STOCK_CADUCIDAD']);
                                        }
                                        return 0;
                                    });

                                    // Fecha límite: 2 meses a partir de hoy
                                    $fechaLimite = strtotime('+2 months');
                                    ?>
                                    <?php foreach ($res as $p):
                                        $hasCaducidad = !empty($p['STOCK_CADUCIDAD']);
                                        $proximoACaducar = $hasCaducidad && strtotime($p['STOCK_CADUCIDAD']) <= $fechaLimite;
                                        $rowStyle = $proximoACaducar ? 'background-color: #fffde7;' : '';
                                    ?>
                                        <tr style="<?= $rowStyle ?>">
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
                                            <td><?= !empty($p['STOCK_CADUCIDAD']) ? $p['STOCK_CADUCIDAD'] : '-' ?></td>
                                            <td><?= $p['ALMACEN_NOMBRE'] ?></td>
                                            <td><?= $p['SUCURSAL_NOMBRE'] ?></td>
                                            <td>
                                                <?php
                                                $estado = $p['STOCK_STOCKSTATUSID'];
                                                $badge = match ($estado) {
                                                    1 => 'inverse-success',
                                                    2 => 'inverse-warning',
                                                    3 => 'incerse-secondary',
                                                    default => 'light'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badge ?>" style="font-size: 0.4rem; color:#000"><?= $p['STOCK_STOCKSTATUSNOMBRE'] ?></span>
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
<iframe id="iframeDescargaMaleta" style="display: none;"></iframe>
<script>
    function buscarProductoMaleta(event) {

        event.preventDefault(); // Evita que recargue la página

        const valorBusquedaMaleta = document.getElementById("inputBusquedaMaleta").value;

        $('#loading').show();

        $.ajax({
            url: '../includes/maletas.info.php?maletaid=<?= $_GET['maletaid'] ?>&busqueda=' + $("#inputBusquedaMaleta").val(), // Tu script PHP que hace la búsqueda y regresa HTML
            type: 'GET',
            data: {
                buscar: valorBusquedaMaleta
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

    function imprimirfid(almacenid) {
        Swal.fire({
            html: "Impresión de etiqueta RFID",
            icon: "success",
            customClass: {
                confirmButton: 'btn btn-success' // usa clases de Bootstrap
            }
        });
    }
</script>
<script>
    // Descargar Excel sin abrir otra pestaña
    document.getElementById("btnDescargarExcelMaleta").addEventListener("click", function() {

        const valorMaletaId = "<?= rawurlencode($_GET['maletaid']) ?>";
        const valorBusquedaMaleta = encodeURIComponent(document.getElementById("inputBusquedaMaleta").value);

        // Mostrar loading
        document.getElementById("loading").style.display = "block";

        fetch(`../includes/maletas.info.exportarexcel.php?maletaid=${valorMaletaId}&busqueda=${valorBusquedaMaleta}`, {
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
                //console.error(error);
            })
            .finally(() => {
                document.getElementById("loading").style.display = "none";
            });
    });

    // Usamos delegación de eventos porque el botón se carga dinámicamente
    $(document).on('click', '#btnPorcentajesMaleta', function() {
        const modalBody = $('#modalglobal .modal-body');
        const maletaid = "<?= $_GET['maletaid'] ?>"; // la misma maletaid que ya estás usando

        // Mostrar mensaje temporal
        modalBody.html('<p>Hola...</p>');

        // Cargar contenido de porcentajes
        $.ajax({
            url: `../includes/maletas.porcentajes.php?maletaid=${maletaid}`,
            type: 'GET',
            success: function(response) {
                modalBody.html(response);

                // Agregar botón de regresar
                const btnRegresar = $('<button class="btn btn-secondary mt-3">Regresar</button>');
                btnRegresar.on('click', function() {
                    // Volver a cargar el contenido original
                    $.ajax({
                        url: `../includes/maletas.info.php?maletaid=${maletaid}`,
                        type: 'GET',
                        success: function(res) {
                            modalBody.html(res);
                        },
                        error: function() {
                            modalBody.html('<p>Error al cargar la información original.</p>');
                        }
                    });
                });

                // Añadir botón al final del contenido
                modalBody.append(btnRegresar);
            },
            error: function() {
                modalBody.html('<p>Error al cargar los porcentajes.</p>');
            }
        });
    });

    // Delegación porque el botón se carga dinámicamente
    $(document).on('click', '#btnRegresarMaleta', function() {
        const modalBody = $('#modalglobal .modal-body');
        const maletaid = "<?= $_GET['maletaid'] ?>";

        modalBody.html('<p>Cargando información...</p>');

        $.ajax({
            url: `../includes/maletas.info.php?maletaid=${maletaid}`,
            type: 'GET',
            success: function(response) {
                modalBody.html(response);
            },
            error: function() {
                modalBody.html('<p>Error al cargar la información original.</p>');
            }
        });
    });
</script>