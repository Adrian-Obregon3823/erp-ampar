<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <?php $oc = new oc(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php //include_once("../includes/remisiones.menu.php");
                ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
                        <h4 class="card-title mb-2 mb-sm-0 font-weight-bold text-dark">Ordenes de Compra</h4>
                        <button class="btn btn-primary btn-rounded shadow-sm" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Orden de Compra" data-url="../includes/oc.nueva.php">
                          <i class="mdi mdi-plus mr-1"></i> Nueva Orden
                        </button>
                      </div>
                      <div class="card-body">
                        <?php
                        // Si no hay filtro, mostramos Guardadas (1) por defecto
                        $filtroStatus = isset($_GET['status']) ? $_GET['status'] : '1';
                        $res = $oc->getoc($filtroStatus);
                        ?>

                        <!-- TABS DE FILTRO -->
                        <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #dee2e6;">
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '1') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=1">Guardadas</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '30') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=30">Enviadas al Proveedor</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '19') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=19">Confirmadas</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '27') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=27">Parciales</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '3') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=3">Finalizadas</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '6') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=6">Rechazadas</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '5') ? 'active font-weight-bold' : 'text-muted' ?>" href="oc.php?status=5">Canceladas</a>
                          </li>
                        </ul>

                        <?php
                        if ($res <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <tr>
                                <th>Folio</th>
                                <th>Status</th>
                                <th>Fecha</th>
                                <th>Sucursal</th>
                                <th>Total</th>
                                <th></th>
                              </tr>
                              <?php
                              foreach ($res as $r) {
                              ?>
                                <tr>
                                  <td><?= $r['OC_FOLIO'] ?></td>
                                  <td style="font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                  <td><?= $r['OC_FECHA'] ?></td>
                                  <td><?= ($r['ALMACEN_NOMBRE'] ? $r['ALMACEN_NOMBRE'] . ' (' . $r['NOMBRE'] . ')' : $r['NOMBRE']) ?></td>
                                  <td><b>$<?= number_format($r['TOTAL'], 2, '.', ',') ?></b></td>
                                  <td>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Orden de Compra" data-url="../includes/oc.detalle.php?ocid=<?= base64_encode($r['OC_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                    <?php if ($r['OC_STATUS'] == 1 || $r['OC_STATUS'] == 6) { // 1 = Borrador, 6 = Rechazada 
                                    ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Editar Orden de Compra" data-url="../includes/oc.editar.php?ocid=<?= base64_encode($r['OC_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer; margin-left:5px;" title="Editar"></a>
                                    <?php } ?>
                                    <a href="../gdocs/oc.formato.php?ocid=<?= base64_encode($r['OC_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:25px; height:auto;" title="Orden de Compra"></a>
                                    <?php if ($r['OC_STATUS'] == 1 || $r['OC_STATUS'] == 6) { ?>
                                      <a href="javascript:void(0)" class="btn-enviar-proveedor" data-id="<?= $r['OC_ID'] ?>"><i class="mdi mdi-send text-primary" style="font-size: 22px; vertical-align: middle; margin-left: 5px;" title="Enviar a Proveedor"></i></a>
                                    <?php } ?>
                                    <?php if ($r['OC_STATUS'] == 30) { ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Revisar Orden de Compra" data-url="../includes/oc.detalle.php?ocid=<?= base64_encode($r['OC_ID']) ?>&revisar=1" aria-selected="false"><img src="../img/check.png" style="width:25px; height:auto; cursor:pointer; margin-left: 5px;" title="Revisar Orden"></a>
                                    <?php } ?>
                                  </td>
                                </tr>
                              <?php
                              }
                              ?>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $r) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $r['OC_FOLIO'] ?></span>
                                  <span class="mobile-card-badge" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                    <?= $r['STATUS_NOMBRE'] ?>
                                  </span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Fecha</span>
                                    <span class="mobile-card-value"><?= $r['OC_FECHA'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Destino</span>
                                    <span class="mobile-card-value"><?= ($r['ALMACEN_NOMBRE'] ? $r['ALMACEN_NOMBRE'] . ' (' . $r['NOMBRE'] . ')' : $r['NOMBRE']) ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Total</span>
                                    <span class="mobile-card-value font-weight-bold">$<?= number_format($r['TOTAL'], 2, '.', ',') ?></span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Orden de Compra" data-url="../includes/oc.detalle.php?ocid=<?= base64_encode($r['OC_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                  <?php if ($r['OC_STATUS'] == 1 || $r['OC_STATUS'] == 6) { // 1 = Borrador, 6 = Rechazada 
                                  ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Editar Orden de Compra" data-url="../includes/oc.editar.php?ocid=<?= base64_encode($r['OC_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:28px; height:auto; cursor:pointer; margin-left:5px;" title="Editar"></a>
                                  <?php } ?>
                                  <a href="../gdocs/oc.formato.php?ocid=<?= base64_encode($r['OC_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:28px; height:auto;" title="Orden de Compra"></a>
                                  <?php if ($r['OC_STATUS'] == 1 || $r['OC_STATUS'] == 6) { ?>
                                    <a href="javascript:void(0)" class="btn-enviar-proveedor ml-2" data-id="<?= $r['OC_ID'] ?>"><i class="mdi mdi-send text-primary" style="font-size: 26px; vertical-align: middle;" title="Enviar a Proveedor"></i></a>
                                  <?php } ?>
                                  <?php if ($r['OC_STATUS'] == 30) { ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Revisar Orden de Compra" data-url="../includes/oc.detalle.php?ocid=<?= base64_encode($r['OC_ID']) ?>&revisar=1" aria-selected="false" class="ml-2"><img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" title="Revisar Orden"></a>
                                  <?php } ?>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        <?php

                        } else {
                          echo '<div class="alert alert-warning text-center">No se encontró información</div>';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Finaliza contenido princial -->
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
  $('#modalglobal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var url = button.data('url');
    var title = button.data('title');

    var modal = $(this);
    modal.find('.modal-title').text(title);
    modal.find('.modal-dialog').addClass('modal-xl')

    // Mostrar loading
    $('#loading').show();

    // AJAX
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        // Cargar el contenido en el cuerpo del modal
        modal.find('.modal-body').html(response);
      },
      error: function() {
        modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
      },
      complete: function() {
        // Ocultar loading al finalizar (éxito o error)
        $('#loading').hide();
      }
    });
  });

  $(document).ready(function() {
    $('.btn-enviar-proveedor').on('click', function(e) {
      e.preventDefault();
      const ocId = $(this).data('id');

      Swal.fire({
        title: '¿Enviar al Proveedor?',
        text: 'La orden de compra pasará a estado "Enviada al Proveedor".',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          $.post('../ajax/oc.changestatus.php', {
            ocid: ocId,
            status: 30 // 30 = Enviada al Proveedor
          }, function(r) {
            if (r.ok) {
              Swal.fire(
                '¡Enviada!',
                'La orden ha sido enviada al proveedor.',
                'success'
              ).then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', r.msg || 'No se pudo actualizar el estado.', 'error');
            }
          }, 'json').fail(function() {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
          });
        }
      });
    });

    $(document).on('click', '.btn-autorizar-oc', function(e) {
      e.preventDefault();
      const ocId = $(this).data('id');
      Swal.fire({
        title: '¿Autorizar y Confirmar Proveedor?',
        text: 'La orden pasará a estatus Confirmada.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Autorizar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          $.post('../ajax/oc.revisar.php', {
            ocid: ocId,
            accion: 'autorizar'
          }, function(r) {
            if (r.ok) {
              Swal.fire('¡Autorizada!', 'Orden de compra confirmada.', 'success').then(() => location.reload());
            } else {
              Swal.fire('Error', r.msg, 'error');
            }
          }, 'json');
        }
      });
    });

    $(document).on('click', '.btn-rechazar-oc', function(e) {
      e.preventDefault();
      const ocId = $(this).data('id');
      Swal.fire({
        title: 'Rechazar Orden de Compra',
        input: 'select',
        inputOptions: {
          'precios': 'Precios incorrectos',
          'abastecimiento': 'Falta de abastecimiento del proveedor'
        },
        inputPlaceholder: 'Selecciona un motivo',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Rechazar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
          if (!value) {
            return 'Debes seleccionar un motivo'
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          $.post('../ajax/oc.revisar.php', {
            ocid: ocId,
            accion: 'rechazar',
            motivo: result.value
          }, function(r) {
            if (r.ok) {
              Swal.fire('¡Rechazada!', 'La orden ha sido rechazada.', 'success').then(() => location.reload());
            } else {
              Swal.fire('Error', r.msg, 'error');
            }
          }, 'json');
        }
      });
    });
  });

  function changestatus(id, status, folio) {
    var accion = "";
    var done = "";
    switch (status) {
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

    if (confirm('¿Seguro que deseas ' + accion + ' la nota de Remisión con Folio: ' + folio + '?')) {
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
            alert("Nota de remisión " + done + " con éxito");
            location.reload();
          } else {
            alert(response);
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
  }
</script>