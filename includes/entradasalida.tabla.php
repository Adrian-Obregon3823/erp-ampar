<?php
if (empty($datosTablaES)) {
    echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
} else {
?>
<div class="table-responsive d-none d-md-block" style="width: 100%;">
  <table class="table table-striped">
    <thead>
      <tr>
        <th scope="col" width="140px"></th>
        <th scope="col" width="200px">Folio</th>
        <th scope="col">Categoría</th>
        <th scope="col">Almacén</th>
        <th scope="col">Tipo</th>
        <th scope="col">Cantidad</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($datosTablaES as $row) { ?>
        <tr>
          <td width="140px">
            <!-- Boton de Información -->
            <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Solicitud de <?= ($row['ES_TIPO'] == 'E' || $row['ES_TIPO'] == 'R') ? 'Entrada' : 'Salida' ?>" data-url="../includes/entradasalida.info.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información"></a>
            <!-- Boton de Edición -->
            <?php if ($row['ES_STATUS'] <> 3 and $row['ES_STATUS'] <> 5) { ?>
              <?php if ($row['ES_TIPO'] == 'E') { ?>
                <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Entrada" data-url="../includes/entradasalida.editarentrada.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar entrada"></a>
              <?php } else if ($row['ES_TIPO'] == 'S') { ?>
                <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Salida" data-url="../includes/entradasalida.editarsalida.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar salida"></a>
              <?php } else { ?>
                <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Reposición" data-url="../includes/entradasalida.editarreposicion.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar salida"></a>
              <?php } ?>
            <?php } ?>
            <?php if ($row['ES_STATUS'] == 1 || $row['ES_STATUS'] == 6) { ?>
              <a onclick="changestatus('<?= $row['ES_ID'] ?>',9,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>')"><img src="../img/send.png" style="width:20px; height:auto; cursor:pointer;" title="Enviar"></a>
              <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer;" title="Cancelar"></a>
            <?php } ?>
            <?php if ($row['ES_STATUS'] <> 1) { ?>
              <a href="../gdocs/entradasalida.formato.php?esid=<?= base64_encode($row['ES_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:20px; height:auto; cursor:pointer;" title="Formato"></a>
            <?php } ?>
            <?php if ($row['ES_STATUS'] == 8) { ?>
              <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                <a onclick="mostrarConfirmacionArticulos('<?= $row['ES_ID'] ?>','<?= $row['ES_TIPO'] ?>','<?= $row['ES_ALMACENID'] ?>')"><img src="../img/check.png" style="width:20px; height:auto; cursor:pointer;" title="Autorizar"></a>
              <?php endif; ?>
              <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer;" title="Cancelar"></a>
            <?php } ?>
            <?php if ($row['ES_STATUS'] == 9) { ?>
              <a onclick="changestatus('<?= $row['ES_ID'] ?>',8,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>')"><img src="../img/REVISAR.png" style="width:20px; height:auto; cursor:pointer;" title="Cambiar status a en Revisión"></a>
              <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer;" title="Cancelar"></a>
            <?php } ?>
            <?php if ($row['ES_STATUS'] == 3 and $row['ES_TIPO'] == "E") { ?>
              <a data-toggle="modal" data-target="#modalglobal" data-title="Impresión de Etiquetas" data-url="../includes/entradasalida.impresionetiquetas.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/rfid2.png" style="width:20px; height:auto; cursor:pointer;" title="Impresión de Etiquetas"></a>
            <?php } ?>
          </td>
          <td width="200px">
            <?= (($row['CADUCIDADMENOS1ANIO'] > 0) ? '<span style="color:red; font-weight:bold; font-size:15px">*</span>' : '&nbsp;&nbsp;&nbsp;') ?>
            <?= $row['ES_FOLIO'] ?> <span style="font-size:10px; color:#<?= $row['STATUS_COLOR'] ?>">(<?= $row['STATUS_NOMBRE'] ?>)</span>
          </td>
          <td><?= $row['NOMBRE'] ?></td>
          <td><?= $row['ALMACEN_NOMBRE'] ?></td>
          <td><b><?= (($row['ES_TIPO'] == "E") ? "Entrada" : (($row['ES_TIPO'] == "S") ? "Salida" : (($row['ES_TIPO'] == "R") ? "Reposición" : ""))) ?></b></td>
          <td><?= $row['CANTIDAD'] ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Mobile View (Cards) -->
<div class="d-block d-md-none mt-2">
  <?php foreach ($datosTablaES as $row) { ?>
    <div class="mobile-card">
      <div class="mobile-card-header">
        <span class="mobile-card-title">
          <?= (($row['CADUCIDADMENOS1ANIO'] > 0) ? '<span style="color:red; font-weight:bold; font-size:15px">*</span>' : '') ?>
          <?= $row['ES_FOLIO'] ?>
        </span>
        <span class="mobile-card-badge" style="color:#<?= $row['STATUS_COLOR'] ?>; border: 1px solid #<?= $row['STATUS_COLOR'] ?>40; background-color: #<?= $row['STATUS_COLOR'] ?>15;">
          <?= $row['STATUS_NOMBRE'] ?>
        </span>
      </div>
      <div class="mobile-card-body">
        <div class="mobile-card-row">
          <span class="mobile-card-label">Categoría</span>
          <span class="mobile-card-value"><?= $row['NOMBRE'] ?></span>
        </div>
        <div class="mobile-card-row">
          <span class="mobile-card-label">Almacén</span>
          <span class="mobile-card-value"><?= $row['ALMACEN_NOMBRE'] ?></span>
        </div>
        <div class="mobile-card-row">
          <span class="mobile-card-label">Tipo</span>
          <span class="mobile-card-value"><b><?= (($row['ES_TIPO'] == "E") ? "Entrada" : (($row['ES_TIPO'] == "S") ? "Salida" : (($row['ES_TIPO'] == "R") ? "Reposición" : ""))) ?></b></span>
        </div>
        <div class="mobile-card-row">
          <span class="mobile-card-label">Cantidad</span>
          <span class="mobile-card-value"><?= $row['CANTIDAD'] ?></span>
        </div>
      </div>
      <div class="mobile-card-actions">
        <!-- Boton de Información -->
        <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Solicitud de <?= ($row['ES_TIPO'] == 'E' || $row['ES_TIPO'] == 'R') ? 'Entrada' : 'Salida' ?>" data-url="../includes/entradasalida.info.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
        <!-- Boton de Edición -->
        <?php if ($row['ES_STATUS'] <> 3 and $row['ES_STATUS'] <> 5) { ?>
          <?php if ($row['ES_TIPO'] == 'E') { ?>
            <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Entrada" data-url="../includes/entradasalida.editarentrada.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar entrada"></a>
          <?php } else if ($row['ES_TIPO'] == 'S') { ?>
            <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Salida" data-url="../includes/entradasalida.editarsalida.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar salida"></a>
          <?php } else { ?>
            <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Solicitud de Reposición" data-url="../includes/entradasalida.editarreposicion.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar salida"></a>
          <?php } ?>
        <?php } ?>
        <?php if ($row['ES_STATUS'] == 1 || $row['ES_STATUS'] == 6) { ?>
          <a onclick="changestatus('<?= $row['ES_ID'] ?>',9,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>')"><img src="../img/send.png" style="width:28px; height:auto; cursor:pointer;" title="Enviar"></a>
          <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Cancelar"></a>
        <?php } ?>
        <?php if ($row['ES_STATUS'] <> 1) { ?>
          <a href="../gdocs/entradasalida.formato.php?esid=<?= base64_encode($row['ES_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:28px; height:auto; cursor:pointer;" title="Formato"></a>
        <?php } ?>
        <?php if ($row['ES_STATUS'] == 8) { ?>
          <?php if ($GLOBALS['isAdmin'] ?? false): ?>
            <a onclick="mostrarConfirmacionArticulos('<?= $row['ES_ID'] ?>','<?= $row['ES_TIPO'] ?>','<?= $row['ES_ALMACENID'] ?>')"><img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" title="Autorizar"></a>
          <?php endif; ?>
          <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Cancelar"></a>
        <?php } ?>
        <?php if ($row['ES_STATUS'] == 9) { ?>
          <a onclick="changestatus('<?= $row['ES_ID'] ?>',8,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>')"><img src="../img/REVISAR.png" style="width:28px; height:auto; cursor:pointer;" title="Cambiar status a en Revisión"></a>
          <a onclick="changestatus('<?= $row['ES_ID'] ?>',5,'<?= $row['ES_FOLIO'] ?>','<?= $row['ES_TIPO'] ?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Cancelar"></a>
        <?php } ?>
        <?php if ($row['ES_STATUS'] == 3 and $row['ES_TIPO'] == "E") { ?>
          <a data-toggle="modal" data-target="#modalglobal" data-title="Impresión de Etiquetas" data-url="../includes/entradasalida.impresionetiquetas.php?esid=<?= base64_encode($row['ES_ID']) ?>" aria-selected="false"><img src="../img/rfid2.png" style="width:28px; height:auto; cursor:pointer;" title="Impresión de Etiquetas"></a>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
</div>
<?php } ?>
