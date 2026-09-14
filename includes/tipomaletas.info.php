<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$maletas = new maletas();
$info = $maletas->getinfotipomaletabyid(base64_decode($_GET['tipomaletaid']));
$maletasentipomaleta = $maletas->getmaletasfolioentipomaleta(base64_decode($_GET['tipomaletaid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Detalle de <strong><?= $info[0]['TIPOMALETA_NOMBRE'] ?></strong></h4>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <table class="table" width="100%">
                    <tr>
                        <th width="120px">Familia</th>
                        <td><?= $info[0]['FAMILIA_NOMBRE'] ?? '-'; ?></td>
                    </tr>
                    <tr>
                        <th width="120px">División</th>
                        <td><?= $info[0]['DIVISION_NOMBRE']; ?></td>
                    </tr>
                    <tr>
                        <th>Descripción</th>
                        <td><?= $info[0]['TIPOMALETA_DESCRIPCION'] ?></td>
                    </tr>
                </table>
            </div>
            <br>
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">Maletas</h4>
                </div>
            </div>
            <div class="row mb-3 align-items-center">
                <div class="col-md-12">
                    <?php if ($maletasentipomaleta <> 0) { ?>
                        <?php foreach ($maletasentipomaleta as $mal) { ?>
                            <?= $mal['ALMACEN_FOLIO'] ?> <span style="color:#<?= $mal['STATUS_COLOR'] ?>;font-size:9px">(<?= $mal['STATUS_NOMBRE'] ?>)</span>
                        <?php } ?>
                    <?php } else { ?>
                        Sin maletas
                    <?php } ?>
                </div>
            </div>
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">Listado de Artículos</h4>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end align-items-center">
                        <input type="hidden" id="exportData" value='<?= json_encode($info) ?>'>
                        <button id="btnExportar" class="btn btn-success">Exportar a Excel</button>
                    </div>
                </div>
            </div>
            <?php if ($info[0]['TIPOMALETADET_ID'] <> '') { ?>
                <div class="table-responsive w-100">
                    <table class="table table-bordered" style="font-size:9px !important">
                        <thead class="table-light">
                            <tr>
                                <th>Cve Artículo</th>
                                <th>Artículo</th>
                                <th>Cantidad Sugerida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($info as $re) { ?>
                                <tr>
                                    <td><?= $re['CLAVE_ARTICULO'] ?></td>
                                    <td><?= $re['ARTICULO_NOMBRE'] ?></td>
                                    <td><?= $re['TIPOMALETADET_CANTIDADSUGERIDA'] ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="alert alert-warning text-center">No se encontraron artículos</div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    document.getElementById('btnExportar').addEventListener('click', async function() {
        const data = document.getElementById('exportData').value;

        // Validar si hay datos
        if (!data || data === "[]") {
            Swal.fire({
                html: "No hay datos para exportar.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            });
            return;
        }

        // Mostrar loading
        document.getElementById("loading").style.display = "block";

        try {
            const response = await fetch('../ajax/tipomaletas.exportarplantilla.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'data=' + encodeURIComponent(data)
            });

            // Verificar si la respuesta es Excel o error
            const contentType = response.headers.get('Content-Type');
            if (contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                const blob = await response.blob();
                const filename = response.headers.get('Content-Disposition')
                    ?.split('filename=')[1]
                    ?.replace(/"/g, '') || 'export.xlsx';

                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.click();
                URL.revokeObjectURL(link.href);
            } else {
                const text = await response.text();
                Swal.fire({
                    html: text || "Error al generar el archivo.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
            }

        } catch (error) {
            Swal.fire({
                html: "Error en la conexión.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            });
        } finally {
            document.getElementById("loading").style.display = "none";
        }
    });
</script>