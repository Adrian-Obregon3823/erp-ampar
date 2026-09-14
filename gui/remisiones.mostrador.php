<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$conceptoFiltro = 'VENTA_DIRECTA';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include_once("../includes/head.php"); ?>
</head>

<body>
    <div id="loading" style="display:none">
        <img id="loading-image" src="../img/logo.png" />
    </div>
    <?php $remisiones = new remisiones(); ?>
    <div class="container-scroller">
        <?php include_once("../includes/header.php"); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once("../includes/menu.sidebar.php") ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                                    <h4 class="card-title mb-0 font-weight-bold text-dark">
                                        Venta Directa
                                    </h4>
                                    <button class="btn btn-primary btn-rounded btn-fw shadow-sm" data-toggle="modal" data-target="#modalglobal" data-title="Remisión Mostrador" data-url="../includes/remisiones.mostrador.nueva.php">
                                        <i class="mdi mdi-plus mr-1"></i> Nueva Remisión
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Tabs for filters -->
                                    <ul class="nav nav-tabs mb-3" id="remisionesTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" id="tab-all" data-toggle="tab" href="#all" role="tab" aria-controls="all" aria-selected="true" data-filter="all">Todas</a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="tab-guardado" data-toggle="tab" href="#guardado" role="tab" aria-controls="guardado" aria-selected="false" data-filter="1">Guardado</a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="tab-recepcion" data-toggle="tab" href="#recepcion" role="tab" aria-controls="recepcion" aria-selected="false" data-filter="29">En Recepción</a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="tab-finalizado" data-toggle="tab" href="#finalizado" role="tab" aria-controls="finalizado" aria-selected="false" data-filter="3">Finalizado</a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="tab-cancelado" data-toggle="tab" href="#cancelado" role="tab" aria-controls="cancelado" aria-selected="false" data-filter="5">Cancelado</a>
                                        </li>
                                    </ul>

                                    <?php
                                    $res = $remisiones->getremisionesmostrador($conceptoFiltro);
                                    if ($res <> 0) {
                                    ?>

                                        <!-- Desktop Table -->
                                        <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Folio</th>
                                                        <th>Status</th>
                                                        <th>Fecha</th>
                                                        <th>Sucursal</th>
                                                        <th>Almacén</th>
                                                        <th>Artículos</th>
                                                        <th>Total</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($res as $r) { ?>
                                                        <tr class="remision-row" data-status="<?= $r['REMISION_STATUS'] ?>">
                                                            <td><?= $r['REMISION_FOLIO'] ?></td>
                                                            <td style="font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                                            <td><?= $r['REMISION_FECHA'] ?></td>
                                                            <td><?= $r['SUCURSAL_NOMBRE'] ?></td>
                                                            <td>
                                                                <?= $r['ALMACEN_NOMBRE'] ?>

                                                            </td>
                                                            <td><b><?= $r['CANTIDAD'] ?></b></td>
                                                            <td><b><?= number_format((float)$r['TOTAL'], 2, '.', ',') ?></b></td>
                                                            <td>
                                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                                                <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                                    <a href="../gdocs/remisiones.mostrador.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:25px; height:auto;" title="Formato"></a>
                                                                <?php } ?>
                                                                <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.mostrador.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer;" title="Editar"></a>
                                                                    <img src="../img/send.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                                    <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                                <?php } ?>
                                                                <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Factura de Remisión" data-url="../includes/remisiones.mostrador.archivos.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:25px; height:auto; cursor:pointer;" title="Subir Factura de esta Remisión"></a>
                                                                    <img src="../img/check.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                                    <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Mobile View (Cards) -->
                                        <div class="d-block d-md-none mt-2">
                                            <?php foreach ($res as $r) { ?>
                                                <div class="mobile-card remision-row" data-status="<?= $r['REMISION_STATUS'] ?>">
                                                    <div class="mobile-card-header">
                                                        <span class="mobile-card-title"><?= $r['REMISION_FOLIO'] ?></span>
                                                        <span class="mobile-card-badge" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                                            <?= $r['STATUS_NOMBRE'] ?>
                                                        </span>
                                                    </div>
                                                    <div class="mobile-card-body">
                                                        <div class="mobile-card-row">
                                                            <span class="mobile-card-label">Fecha</span>
                                                            <span class="mobile-card-value"><?= $r['REMISION_FECHA'] ?></span>
                                                        </div>
                                                        <div class="mobile-card-row">
                                                            <span class="mobile-card-label">Sucursal</span>
                                                            <span class="mobile-card-value"><?= $r['SUCURSAL_NOMBRE'] ?></span>
                                                        </div>
                                                        <div class="mobile-card-row">
                                                            <span class="mobile-card-label">Almacén</span>
                                                            <span class="mobile-card-value"><?= $r['ALMACEN_NOMBRE'] ?></span>
                                                        </div>
                                                        <div class="mobile-card-row">
                                                            <span class="mobile-card-label">Artículos</span>
                                                            <span class="mobile-card-value font-weight-bold"><?= $r['CANTIDAD'] ?></span>
                                                        </div>
                                                        <div class="mobile-card-row">
                                                            <span class="mobile-card-label">Total</span>
                                                            <span class="mobile-card-value font-weight-bold">$<?= number_format((float)$r['TOTAL'], 2, '.', ',') ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="mobile-card-actions">
                                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                                        <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                            <a href="../gdocs/remisiones.mostrador.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:28px; height:auto;" title="Formato"></a>
                                                        <?php } ?>
                                                        <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                            <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.mostrador.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                                            <img src="../img/send.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                            <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                        <?php } ?>
                                                        <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                            <a data-toggle="modal" data-target="#modalglobal" data-title="Factura de Remisión" data-url="../includes/remisiones.mostrador.archivos.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:28px; height:auto; cursor:pointer;" title="Subir Factura de esta Remisión"></a>
                                                            <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                            <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php
                                    } else {
                                        echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once("../includes/modalglobal.php") ?>
                <?php include_once("../includes/footer.php"); ?>
            </div>
        </div>
    </div>
    <?php include_once("../includes/foot.php"); ?>
</body>

</html>
<script>
    $(document).ready(function() {
        $('#remisionesTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
            var filter = $(this).data('filter');
            
            if (filter === 'all') {
                $('.remision-row').show();
            } else {
                $('.remision-row').hide();
                $('.remision-row[data-status="' + filter + '"]').show();
            }
        });
    });

    $('#modalglobal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var url = button.data('url');
        var title = button.data('title');

        var modal = $(this);
        modal.find('.modal-title').text(title);
        modal.find('.modal-dialog').addClass('modal-xl');

        $('#loading').show();

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                modal.find('.modal-body').html(response);
            },
            error: function() {
                modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
            },
            complete: function() {
                $('#loading').hide();
            }
        });
    });

    function changestatus(id, status, folio) {
        var accion = "";
        var done = "";

        switch (status) {
            case 29:
                accion = "enviar a recepción";
                done = "enviada a recepción";
                break;
            case 3:
                accion = "finalizar";
                done = "finalizada";
                break;
            case 5:
                accion = "cancelar";
                done = "cancelada";
                break;
            default:
        }

        Swal.fire({
            text: '¿Seguro que deseas ' + accion + ' la nota de Remisión con Folio: ' + folio + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ' + accion,
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/remisiones.update.status.php',
                    type: 'POST',
                    data: {
                        id: id,
                        status: status
                    },
                    dataType: 'html',
                    beforeSend: function() {
                        $("#loading").show();
                    },
                    success: function(response) {
                        if (response.trim() === "") {
                            Swal.fire({
                                html: "Nota de remisión " + done + " con éxito",
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
        })
    }
</script>