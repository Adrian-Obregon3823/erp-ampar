<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <?php $tickets = new tickets(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php //include_once("../includes/tickets.menu.php");
                ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Incidencias</h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $res = $tickets->gettickets();
                        if ($res <> 0) {
                            $tipos_unicos = [];
                            $status_unicos = [];
                            foreach ($res as $row) {
                                // Tipos
                                $t = trim($row['TICKETTIPO_NOMBRE']);
                                if ($t == '') $t = 'Otros';
                                if (!in_array($t, $tipos_unicos)) $tipos_unicos[] = $t;
                                
                                // Estatus
                                $s = trim($row['STATUS_NOMBRE']);
                                if (strtolower($s) == 'guardado') $s = 'Abierta';
                                if (strtolower($s) == 'en proceso') $s = 'Abierta'; // Si no es válido, se regresa a abierta
                                if (strtolower($s) == 'enviado') $s = 'Resuelta';
                                
                                if (!in_array($s, $status_unicos)) $status_unicos[] = $s;
                            }
                            sort($tipos_unicos);
                            sort($status_unicos);
                            
                            $firstTipo = $tipos_unicos[0] ?? '';
                            $firstStatus = in_array('Abierta', $status_unicos) ? 'Abierta' : ($status_unicos[0] ?? '');
                        ?>
                                      <div class="row mb-4">
                                          <div class="col-md-6 mb-3 mb-md-0">
                                              <label class="text-muted" style="font-size: 11px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Filtrar por Tipo</label>
                                              <div class="d-flex flex-wrap" style="gap: 10px;">
                                                <?php foreach ($tipos_unicos as $i => $t) { ?>
                                                  <button type="button" class="btn btn-sm <?= $i == 0 ? 'btn-primary' : 'btn-outline-primary' ?>" id="btn-tipo-<?= md5($t) ?>" 
                                                          onclick="filtrarPorTipo('<?= $t ?>'); document.querySelectorAll('[id^=btn-tipo-]').forEach(b=>{b.classList.remove('btn-primary'); b.classList.add('btn-outline-primary');}); this.classList.remove('btn-outline-primary'); this.classList.add('btn-primary');" 
                                                          style="border-radius: 20px; padding: 6px 18px; font-weight: 600; box-shadow: none;"><?= $t ?></button>
                                                <?php } ?>
                                              </div>
                                          </div>
                                          <div class="col-md-6">
                                              <label class="text-muted" style="font-size: 11px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Filtrar por Estatus</label>
                                              <div class="d-flex flex-wrap" style="gap: 10px;">
                                                <?php foreach ($status_unicos as $i => $s) { 
                                                    $btnClass = ($s == 'Resuelta') ? 'success' : 'info';
                                                ?>
                                                  <button type="button" class="btn btn-sm <?= $s == $firstStatus ? 'btn-'.$btnClass : 'btn-outline-'.$btnClass ?>" id="btn-status-<?= md5($s) ?>" data-color="<?= $btnClass ?>"
                                                          onclick="filtrarPorStatus('<?= $s ?>'); document.querySelectorAll('[id^=btn-status-]').forEach(b=>{let c=b.getAttribute('data-color'); b.classList.remove('btn-'+c); b.classList.add('btn-outline-'+c);}); this.classList.remove('btn-outline-'+this.getAttribute('data-color')); this.classList.add('btn-'+this.getAttribute('data-color'));" 
                                                          style="border-radius: 20px; padding: 6px 18px; font-weight: 600; box-shadow: none;"><?= $s ?></button>
                                                <?php } ?>
                                              </div>
                                          </div>
                                      </div>

                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col">Status</th>
                                      <th scope="col">Tipo</th>
                                      <th scope="col">Folio</th>
                                      <th scope="col">Fecha</th>
                                      <th scope="col">Concepto</th>
                                      <th scope="col" width="100px"></th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                      <?php foreach ($res as $row) { 
                                          $miTipo = trim($row['TICKETTIPO_NOMBRE']) == '' ? 'Otros' : trim($row['TICKETTIPO_NOMBRE']);
                                          
                                          // Profesionalizar nombres de estatus
                                          $statusNombre = $row['STATUS_NOMBRE'];
                                          if (strtolower(trim($statusNombre)) == 'guardado') $statusNombre = 'Abierta';
                                          if (strtolower(trim($statusNombre)) == 'en proceso') $statusNombre = 'Abierta';
                                          if (strtolower(trim($statusNombre)) == 'enviado') $statusNombre = 'Resuelta';
                                      ?>
                                        <tr class="ticket-row" data-tipo="<?= $miTipo ?>" data-status="<?= $statusNombre ?>">
                                        <td style="color:#<?= $row['STATUS_COLOR'] ?>"><b><?= $statusNombre ?></b></td>
                                        <td><?= $row['TICKETTIPO_NOMBRE'] ?></td>
                                        <td><?= $row['TICKET_FOLIO'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['TICKET_FECHA'])) ?></td>
                                        <td><?= $row['TICKET_CONCEPTO'] ?></td>
                                        <td>
                                          <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Incidencia" data-url="../includes/ticket.info.php?ticketid=<?= base64_encode($row['TICKET_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                          <a href="../gdocs/ticket.formato.php?ticketid=<?= base64_encode($row['TICKET_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:25px; height:auto; cursor:pointer;" title="Formato"></a>
                                          <?php if ($row['TICKET_STATUS'] == 1) { ?>
                                            <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                                              <a onclick="autorizar('<?= $row['TICKET_ID'] ?>')"><img src="../img/check.png" style="width:22px; height:auto; cursor:pointer;" title="Autorizar"></a>
                                            <?php endif; ?>
                                            <a onclick="cancelar('<?= $row['TICKET_ID'] ?>');"><img src="../img/eliminar.png" style="width:25px; height:auto; cursor:pointer;" title="Rechazar"></a>
                                          <?php } ?>
                                        </td>
                                      </tr>
                                    <?php } ?>
                                  </tbody>
                                </table>
                              </div>
                              
                              <!-- Mobile View (Cards) -->
                                <div class="d-block d-md-none mt-2">
                                <?php foreach ($res as $row) { 
                                    $miTipo = trim($row['TICKETTIPO_NOMBRE']) == '' ? 'Otros' : trim($row['TICKETTIPO_NOMBRE']);

                                    // Profesionalizar nombres de estatus
                                    $statusNombre = $row['STATUS_NOMBRE'];
                                    if (strtolower(trim($statusNombre)) == 'guardado') $statusNombre = 'Abierta';
                                    if (strtolower(trim($statusNombre)) == 'en proceso') $statusNombre = 'Abierta';
                                    if (strtolower(trim($statusNombre)) == 'enviado') $statusNombre = 'Resuelta';
                                ?>
                                  <div class="mobile-card ticket-row" data-tipo="<?= $miTipo ?>" data-status="<?= $statusNombre ?>">
                                    <div class="mobile-card-header">
                                      <span class="mobile-card-title"><?= $row['TICKET_FOLIO'] ?></span>
                                      <span class="mobile-card-badge" style="color:#<?= $row['STATUS_COLOR'] ?>; border: 1px solid #<?= $row['STATUS_COLOR'] ?>40; background-color: #<?= $row['STATUS_COLOR'] ?>15;">
                                        <?= $statusNombre ?>
                                      </span>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Tipo</span>
                                        <span class="mobile-card-value"><?= $row['TICKETTIPO_NOMBRE'] ?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Fecha</span>
                                        <span class="mobile-card-value"><?= date('d/m/Y', strtotime($row['TICKET_FECHA'])) ?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Concepto</span>
                                        <span class="mobile-card-value" style="text-align: right;">
                                          <?= $row['TICKET_CONCEPTO'] ?>
                                        </span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Incidencia" data-url="../includes/ticket.info.php?ticketid=<?= base64_encode($row['TICKET_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                      <a href="../gdocs/ticket.formato.php?ticketid=<?= base64_encode($row['TICKET_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:28px; height:auto; cursor:pointer;" title="Formato"></a>
                                      <?php if ($row['TICKET_STATUS'] == 1) { ?>
                                        <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                                          <a onclick="autorizar('<?= $row['TICKET_ID'] ?>')"><img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" title="Autorizar"></a>
                                        <?php endif; ?>
                                        <a onclick="cancelar('<?= $row['TICKET_ID'] ?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Rechazar"></a>
                                      <?php } ?>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                        <?php
                        } else {
                          echo "No se encontraron registros<br>";
                        } ?>
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
    var url = button.data('url'); // Obtén la URL del atributo data-url
    var title = button.data('title'); // Obtén el título del atributo data-title

    // Cambia el título del encabezado del modal
    var modal = $(this);
    modal.find('.modal-title').text(title); // Actualiza el título en el header del modal

    // Asegurar que el modal tenga el tamaño xl
    modal.find('.modal-dialog').addClass('modal-xl')

    // Realiza la solicitud AJAX para cargar el contenido de la URL en el modal
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        modal.find('.modal-body').html(response); // Inserta el contenido en el modal
      },
      error: function() {
        modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
      }
    });
  });

  function cancelar(id) {
    // 1) Pido el comentario
    var comentario = prompt('Por favor, escribe el motivo de la cancelación:');
    if (comentario === null) {
      // El usuario pulsó "Cancelar" en el prompt
      return false;
    }
    comentario = comentario.trim();
    if (comentario === '') {
      alert('Debes escribir un comentario para continuar.');
      return false;
    }

    // 2) Confirmo la operación
    if (!confirm('¿Seguro que deseas rechazar la Incidencia?')) {
      return false;
    }

    // 3) Envío vía AJAX incluyendo el comentario
    $.ajax({
      url: '../ajax/ticket.update.status.php',
      type: 'POST',
      data: {
        id: id,
        status: 3,
        comentario: comentario // aquí va tu campo
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        if (response === "") {
          alert("Incidencia rechazada con éxito");
          location.reload();
        } else {
          alert(response);
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

  function autorizar(id) {
    // 1) Pido el comentario
    var comentario = prompt('Por favor, escribe una nota para la autorización');
    if (comentario === null) {
      // El usuario pulsó "Cancelar" en el prompt
      return false;
    }
    comentario = comentario.trim();
    if (comentario === '') {
      alert('Debes escribir una nota de autorización para continuar.');
      return false;
    }

    // 2) Confirmo la operación
    if (!confirm('¿Seguro que deseas resolver la Incidencia?')) {
      return false;
    }

    $.ajax({
      url: '../ajax/ticket.update.status.php',
      type: 'POST',
      data: {
        id: id,
        status: 9,
        comentario: comentario // aquí va tu campo
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        if (response === "") {
          alert("Incidencia resuelta con éxito");
          location.reload();
        } else {
          alert(response);
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

  var currentTipo = '<?= $firstTipo ?>';
  var currentStatus = '<?= $firstStatus ?>';

  function filtrarPorTipo(tipo) {
    currentTipo = tipo;
    aplicarFiltros();
  }

  function filtrarPorStatus(status) {
    currentStatus = status;
    aplicarFiltros();
  }

  function aplicarFiltros() {
    var rows = document.querySelectorAll('.ticket-row');
    rows.forEach(function(r) {
      var matchTipo = (currentTipo === 'Todos' || r.getAttribute('data-tipo') === currentTipo);
      var matchStatus = (currentStatus === 'Todos' || r.getAttribute('data-status') === currentStatus);
      if (matchTipo && matchStatus) {
        r.style.display = '';
      } else {
        r.style.display = 'none';
      }
    });
  }

  // Aplicar filtros al cargar la página
  document.addEventListener("DOMContentLoaded", function() {
    aplicarFiltros();
  });
</script>