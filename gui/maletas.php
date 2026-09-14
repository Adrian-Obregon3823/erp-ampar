<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php //include_once("../includes/almacenes.menu.php");
                ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12 col-lg-7 mb-4 mb-lg-0">
                    <div class="card">
                      <div class="card-header">
                        <h4>
                          Maletas &nbsp;&nbsp;
                          <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Maleta" data-url="../includes/maletas.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar Maleta"></a>
                          <?php
                          $ver_desactivados = isset($_GET['desactivados']) && $_GET['desactivados'] == 1 ? true : false;
                          if ($ver_desactivados) { ?>
                            <a href="maletas.php" class="btn btn-sm btn-primary float-right" style="margin-top:-5px">Ver Activas</a>
                          <?php } else { ?>
                            <a href="maletas.php?desactivados=1" class="btn btn-sm btn-secondary float-right" style="margin-top:-5px">Ver Desactivadas</a>
                          <?php } ?>
                        </h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $maletas = new maletas();

                        $sucursales_ids = [];
                        if (empty($GLOBALS['isAdmin'])) {
                          $sucursales_usuario = $_SESSION['ampar']['sucursales'] ?? [];
                          foreach ($sucursales_usuario as $sucursal) {
                            if (isset($sucursal['SUCURSAL_ID'])) {
                              $sucursales_ids[] = (int)$sucursal['SUCURSAL_ID'];
                            }
                          }
                          if (empty($sucursales_ids)) {
                            $sucursales_ids = [-1]; // Usuario normal sin sucursales asignadas no debe ver nada
                          }
                        }

                        $res = $maletas->getmaletas($sucursales_ids, $ver_desactivados);

                        if ($res <> 0) {
                            $unique_almacenes = [];
                            foreach ($res as $row) {
                                $almacen_nombre = trim($row['PADRE_ALMACEN_NOMBRE']);
                                if (!empty($almacen_nombre) && !in_array($almacen_nombre, $unique_almacenes)) {
                                    $unique_almacenes[] = $almacen_nombre;
                                }
                            }
                            sort($unique_almacenes);
                        ?>
                          <div class="row mb-3">
                              <div class="col-md-4">
                                  <select id="filtro-almacen" class="form-control form-control-sm" onchange="filtrarPorAlmacen()">
                                      <option value="">Todos los Almacenes...</option>
                                      <?php foreach ($unique_almacenes as $almacen) { ?>
                                          <option value="<?= htmlspecialchars($almacen, ENT_QUOTES) ?>"><?= htmlspecialchars($almacen, ENT_QUOTES) ?></option>
                                      <?php } ?>
                                  </select>
                              </div>
                          </div>
                          <script>
                              function filtrarPorAlmacen() {
                                  var input = document.getElementById("filtro-almacen");
                                  var filter = input.value.toUpperCase();
                                  var table = document.getElementById("tabla-maletas");
                                  if (table) {
                                      var tr = table.getElementsByTagName("tr");
                                      for (var i = 1; i < tr.length; i++) {
                                          var td = tr[i].getElementsByTagName("td")[5]; // Columna de Almacén
                                          if (td) {
                                              var txtValue = td.textContent || td.innerText;
                                              if (filter === "" || txtValue.toUpperCase().trim() === filter) {
                                                  tr[i].style.display = "";
                                              } else {
                                                  tr[i].style.display = "none";
                                              }
                                          }
                                      }
                                  }

                                  var cards = document.getElementsByClassName("mobile-card");
                                  for (var i = 0; i < cards.length; i++) {
                                      var rows = cards[i].getElementsByClassName("mobile-card-row");
                                      if (rows.length > 1) {
                                          var spanValue = rows[1].getElementsByClassName("mobile-card-value")[0];
                                          if (spanValue) {
                                              var txtValue = spanValue.textContent || spanValue.innerText;
                                              if (filter === "" || txtValue.toUpperCase().trim() === filter) {
                                                  cards[i].style.display = "";
                                              } else {
                                                  cards[i].style.display = "none";
                                              }
                                          }
                                      }
                                  }
                              }
                          </script>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped" id="tabla-maletas">
                              <thead>
                                <tr>
                                  <th scope="col" width="100px"></th>
                                  <th scope="col">Folio</th>
                                  <th scope="col">Progreso</th>
                                  <th scope="col"></th>
                                  <th scope="col">Nombre</th>
                                  <th scope="col">Almacén</th>
                                  <th scope="col">Tipo Maleta</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($res as $row) { ?>
                                  <tr>
                                    <td width="100px">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Maleta" data-url="../includes/maletas.info.php?maletaid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Detalle de Maleta"></a>
                                      <?php if ($ver_desactivados) { ?>
                                        <a style="cursor:pointer;" onclick="mactivar('<?= $row['ALMACEN_ID'] ?>');"><img src="../img/check.png" style="width:20px; height:auto; cursor:pointer" title="Activar Maleta"></a>
                                      <?php } else { ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Maleta" data-url="../includes/maletas.editar.php?maletaid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar Maleta"></a>
                                        <a style="cursor:pointer;" onclick="madesactivar('<?= $row['ALMACEN_ID'] ?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer" title="Desactivar Maleta"></a>
                                      <?php } ?>
                                    </td>
                                    <td><b><?= $row['ALMACEN_FOLIO'] ?></b></td>
                                    <td>
                                      <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?= $row['PORCENTAJE'] ?>%;" id="progress"></div>
                                      </div>
                                    </td>
                                    <td><?= $row['PORCENTAJE'] ?>%</td>
                                    <td><?= $row['ALMACEN_NOMBRE'] ?></td>
                                    <td><?= $row['PADRE_ALMACEN_NOMBRE'] ?></td>
                                    <td><?= $row['TIPOMALETA_NOMBRE'] ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $row) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $row['ALMACEN_FOLIO'] ?> - <?= $row['ALMACEN_NOMBRE'] ?></span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Progreso (<?= $row['PORCENTAJE'] ?>%)</span>
                                    <span class="mobile-card-value" style="width: 50%;">
                                      <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?= $row['PORCENTAJE'] ?>%;" id="progress"></div>
                                      </div>
                                    </span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Almacén</span>
                                    <span class="mobile-card-value"><?= $row['PADRE_ALMACEN_NOMBRE'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Tipo</span>
                                    <span class="mobile-card-value"><?= $row['TIPOMALETA_NOMBRE'] ?></span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Maleta" data-url="../includes/maletas.info.php?maletaid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Detalle de Maleta"></a>
                                  <?php if ($ver_desactivados) { ?>
                                    <a style="cursor:pointer;" onclick="mactivar('<?= $row['ALMACEN_ID'] ?>');"><img src="../img/check.png" style="width:28px; height:auto; cursor:pointer" title="Activar Maleta"></a>
                                  <?php } else { ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Maleta" data-url="../includes/maletas.editar.php?maletaid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar Maleta"></a>
                                    <a style="cursor:pointer;" onclick="madesactivar('<?= $row['ALMACEN_ID'] ?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer" title="Desactivar Maleta"></a>
                                  <?php } ?>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        <?php
                        } else {
                          echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
                        } ?>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-lg-5">
                    <div class="card">
                      <div class="card-header">
                        <h4>Tipo Maletas &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Tipo Maleta" data-url="../includes/tipomaletas.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar tipo de Maleta"></a></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $restipomaletas = $maletas->gettipomaletas();
                        if ($restipomaletas <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th width="100px"></th>
                                  <th>División</th>
                                  <th>Tipo de Maleta</th>
                                <tr>
                              </thead>
                              <tbody>
                                <?php foreach ($restipomaletas as $rtm) { ?>
                                  <tr>
                                    <td>
                                      <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Tipo Maleta" data-url="../includes/tipomaletas.info.php?tipomaletaid=<?= base64_encode($rtm['TIPOMALETA_ID']) ?>" aria-selected="false"><img src="../img/info.png" title="Detalle de tipo de Maleta" style="width:20px; height:auto;"></a>
                                      <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Edición Tipo Maleta" data-url="../includes/tipomaletas.editar.php?tipomaletaid=<?= base64_encode($rtm['TIPOMALETA_ID']) ?>" aria-selected="false"><img src="../img/edit.png" title="Editar tipo de Maleta" style="width:20px; height:auto;"></a>
                                      <label for="excelUpload" style="cursor:pointer;" data-tipomaletaid="<?= $rtm['TIPOMALETA_ID'] ?>">
                                        <img src="../img/excel.png" title="Carga plantilla de Artículos" style="width:20px; height:auto;">
                                      </label>
                                      <input type="file" id="excelUpload" accept=".xls,.xlsx" style="display:none">
                                      <a style="cursor:pointer;" onclick="tmeliminar('<?= base64_encode($rtm['TIPOMALETA_ID']) ?>');"><img src="../img/eliminar.png" title="Eliminar tipo de Maleta" style="width:20px; height:auto;"></a>
                                    </td>
                                    <td><?= $rtm['DIVISION_NOMBRE'] ?></td>
                                    <td><?= $rtm['TIPOMALETA_NOMBRE'] ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($restipomaletas as $rtm) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $rtm['TIPOMALETA_NOMBRE'] ?></span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">División</span>
                                    <span class="mobile-card-value"><?= $rtm['DIVISION_NOMBRE'] ?></span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Tipo Maleta" data-url="../includes/tipomaletas.info.php?tipomaletaid=<?= base64_encode($rtm['TIPOMALETA_ID']) ?>" aria-selected="false"><img src="../img/info.png" title="Detalle de tipo de Maleta" style="width:28px; height:auto;"></a>
                                  <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Edición Tipo Maleta" data-url="../includes/tipomaletas.editar.php?tipomaletaid=<?= base64_encode($rtm['TIPOMALETA_ID']) ?>" aria-selected="false"><img src="../img/edit.png" title="Editar tipo de Maleta" style="width:28px; height:auto;"></a>
                                  <label for="excelUpload" style="cursor:pointer; margin-bottom:0;" data-tipomaletaid="<?= $rtm['TIPOMALETA_ID'] ?>">
                                    <img src="../img/excel.png" title="Carga plantilla de Artículos" style="width:28px; height:auto;">
                                  </label>
                                  <a style="cursor:pointer;" onclick="tmeliminar('<?= base64_encode($rtm['TIPOMALETA_ID']) ?>');"><img src="../img/eliminar.png" title="Eliminar tipo de Maleta" style="width:28px; height:auto;"></a>
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
                <!-- fila Divisiones -->
                <br>
                <br>
                <div class="row">
                  <div class="col-12 col-lg-5 mb-4 mb-lg-0">
                    <div class="card">
                      <div class="card-header">
                        <h4>Divisiones &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Nueva División" data-url="../includes/divisiones.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar División"></a></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $divisionesObj = new maletas();
                        $resdivisiones = $divisionesObj->getdivisiones();
                        if ($resdivisiones <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th width="40px"></th>
                                  <th>División</th>
                                  <th>Familia</th>
                                  <th>Descripción</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($resdivisiones as $div) { ?>
                                  <tr>
                                    <td width="70px">
                                      <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Editar División" data-url="../includes/divisiones.editar.php?divisionid=<?= base64_encode($div['DIVISION_ID']) ?>" aria-selected="false" title="Editar"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar"></a>
                                      <a style="cursor:pointer;" onclick="divEliminar('<?= $div['DIVISION_ID'] ?>');" title="Eliminar"><img src="../img/eliminar.png" style="width:20px; height:auto;"></a>
                                    </td>
                                    <td><strong><?= $div['DIVISION_NOMBRE'] ?></strong></td>
                                    <td><?= $div['FAMILIA_NOMBRE'] ? $div['FAMILIA_NOMBRE'] : '<span class="text-muted">N/A</span>' ?></td>
                                    <td style="font-size:0.85rem;"><?= $div['DIVISION_DESCRIPCION'] ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($resdivisiones as $div) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $div['DIVISION_NOMBRE'] ?></span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Familia</span>
                                    <span class="mobile-card-value"><?= $div['FAMILIA_NOMBRE'] ? $div['FAMILIA_NOMBRE'] : '<span class="text-muted">N/A</span>' ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Descripción</span>
                                    <span class="mobile-card-value" style="font-size:0.85rem; text-align:right;"><?= $div['DIVISION_DESCRIPCION'] ?></span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Editar División" data-url="../includes/divisiones.editar.php?divisionid=<?= base64_encode($div['DIVISION_ID']) ?>" aria-selected="false" title="Editar"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                  <a style="cursor:pointer;" onclick="divEliminar('<?= $div['DIVISION_ID'] ?>');" title="Eliminar"><img src="../img/eliminar.png" style="width:28px; height:auto;"></a>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        <?php
                        } else {
                          echo '<div class="alert alert-warning text-center">Sin divisiones registradas</div>';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-lg-5">
                    <div class="card">
                      <div class="card-header">
                        <h4>Familias&nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Familia" data-url="../includes/familias.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar Familia"></a></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $familiasObj = new maletas();
                        $resfamilias = $familiasObj->getfamilias();
                        if ($resfamilias <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th width="40px"></th>
                                  <th>ID</th>
                                  <th>Familia</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($resfamilias as $fam) { ?>
                                  <tr>
                                    <td width="70px">
                                      <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Editar Familia" data-url="../includes/familias.editar.php?familiaid=<?= base64_encode($fam['FAMILIA_ID']) ?>" aria-selected="false" title="Editar"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar"></a>
                                      <a style="cursor:pointer;" onclick="famEliminar('<?= $fam['FAMILIA_ID'] ?>');" title="Eliminar"><img src="../img/eliminar.png" style="width:20px; height:auto;"></a>
                                    </td>
                                    <td><?= $fam['FAMILIA_ID'] ?></td>
                                    <td><strong><?= $fam['FAMILIA_NOMBRE'] ?></strong></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($resfamilias as $fam) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $fam['FAMILIA_NOMBRE'] ?></span>
                                  <span class="mobile-card-badge">ID: <?= $fam['FAMILIA_ID'] ?></span>
                                </div>
                                <div class="mobile-card-actions" style="justify-content: flex-end;">
                                  <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Editar Familia" data-url="../includes/familias.editar.php?familiaid=<?= base64_encode($fam['FAMILIA_ID']) ?>" aria-selected="false" title="Editar"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                  <a style="cursor:pointer;" onclick="famEliminar('<?= $fam['FAMILIA_ID'] ?>');" title="Eliminar"><img src="../img/eliminar.png" style="width:28px; height:auto;"></a>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        <?php
                        } else {
                          echo '<div class="alert alert-warning text-center">Sin familias registradas</div>';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Finaliza contenido principal -->
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

<!-- Modal Bootstrap -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resultModalLabel">Revisión de Artículos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="confirmForm">
          <div id="resultsContainer" class="mb-3">
            <!-- Aquí se agregan los checkboxes dinámicamente -->
          </div>
          <button type="button" class="btn btn-success" id="confirmFormbtn">Guardar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Modal de Instrucciones -->
<div class="modal fade" id="modalFormatoExcel" tabindex="-1" role="dialog" aria-labelledby="modalFormatoExcelLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFormatoExcelLabel">Formato requerido para Excel</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger" role="alert">
          <strong>¡Advertencia!</strong> Al cargar una plantilla se borrarán todos los artículos definidos con anterioridad.
        </div>
        <p>Antes de subir el archivo, asegúrate de que tenga el siguiente formato:</p>
        <p>En la primera columna los siguientes nombres</p>
        <ul>
          <li><strong>Columna A:</strong> <code>CLAVE</code> (Alfanumérica)</li>
          <li><strong>Columna B:</strong> <code>ARTICULO</code> (Alfanumérico)</li>
          <li><strong>Columna C:</strong> <code>CANTIDAD</code> (Solo números enteros)</li>
        </ul>
        <p>Ejemplo:</p>
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
              <td>25</td>
            </tr>
          </tbody>
        </table>
        <br>
        <p>El archivo debe estar en formato <strong>.xlsx</strong></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="continuarCargaExcel">Continuar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>
<script>
  let tipomaletaidTemporal = null; // Aquí guardaremos el ID temporalmente

  document.querySelectorAll('label[for="excelUpload"]').forEach(label => {
    label.addEventListener('click', function(e) {
      e.preventDefault(); // Evita que abra el input de archivo inmediatamente

      // Guarda temporalmente el tipomaletaid del label que se hizo clic
      tipomaletaidTemporal = this.getAttribute('data-tipomaletaid');

      // Muestra el modal de formato
      $('#modalFormatoExcel').modal('show');

    });
  });

  // Cuando el usuario haga clic en "Continuar" dentro del modal
  document.getElementById('continuarCargaExcel').addEventListener('click', function() {
    $('#modalFormatoExcel').modal('hide');

    // Lanza la selección de archivo
    document.getElementById('excelUpload').click();
  });

  document.getElementById('excelUpload').addEventListener('change', function() {
    const file = this.files[0];
    const formData = new FormData();
    formData.append('file', file);
    formData.append('tipomaletaid', tipomaletaidTemporal); // Usa el tipomaletaid guardado

    // Mostrar loading al empezar carga
    $("#loading").show();

    fetch('../ajax/tipomaletas.uploadplantilla.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        const container = document.getElementById('resultsContainer');
        container.innerHTML = '';

        // Agrega el ID oculto al formulario (por si lo necesita)
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'tipomaletaid';
        hiddenInput.value = tipomaletaidTemporal;
        document.getElementById('confirmForm').appendChild(hiddenInput);

        let html = `
        <table class="table table-bordered table-sm" style="font-size: 13px">
          <thead class="table-light">
            <tr>
              <th style="width:40px"></th>
              <th>Clave</th>
              <th>Artículo</th>
              <th style="width:80px">Cantidad</th>
              <th style="width:150px">Estatus</th>
            </tr>
          </thead>
          <tbody>
      `;

        data.forEach(item => {
          let color = 'red';
          let status = '✖ Formato incorrecto';

          if (item.estado === 'encontrado') {
            color = 'green';
            status = '✔ Encontrado';
          } else if (item.estado === 'no_encontrado') {
            color = 'orange';
            status = '✖ No encontrado';
          }

          html += `
          <tr>
            <td class="text-center align-middle">
              <input type="checkbox" name="articulos[]" value='${JSON.stringify(item)}' checked ${item.estado !== 'encontrado' ? 'disabled' : ''}>
            </td>
            <td class="align-middle">${item.clave}</td>
            <td class="align-middle">${item.articulo}</td>
            <td class="text-center align-middle">${item.cantidad}</td>
            <td class="text-center align-middle" style="color:${color}">${status}</td>
          </tr>
        `;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;

        // Ocultar loading cuando termine carga y mostrar modal
        $("#loading").hide();
        $('#resultModal').modal('show');
      })
      .catch(error => {
        console.error('Error al cargar el archivo:', error);
        $("#loading").hide();
        Swal.fire({
          html: 'Error al cargar el archivo.',
          icon: "warning",
          customClass: {
            confirmButton: 'btn btn-success' // usa clases de Bootstrap
          }
        });
      });
  });

  document.getElementById('confirmFormbtn').addEventListener('click', function(e) {
    e.preventDefault();
    var formDatax = new FormData(document.getElementById("confirmForm"));

    $.ajax({
      url: '../ajax/tipomaletas.guardarplantilla.php',
      type: 'POST',
      data: formDatax,
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
            html: "Artículos cargados con éxito",
            icon: "success",
            customClass: {
              confirmButton: 'btn btn-success' // usa clases de Bootstrap
            }
          }).then(() => {
            $('#resultModal').modal('hide');
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
  });
</script>
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

  // Delegar clics dentro del modal para recargar el contenido AJAX
  $(document).on('click', '#modalglobal [data-url]', function(e) {
    e.preventDefault();

    const button = $(this);
    const url = button.data('url');
    const title = button.data('title') || 'Detalle';

    const modal = $('#modalglobal');

    modal.find('.modal-title').text(title);
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


  function divEliminar(divisionid) {
    Swal.fire({
      text: '¿Seguro que deseas eliminar la División?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/divisiones.eliminar.php',
          type: 'POST',
          data: {
            divisionid: divisionid
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "División eliminada con éxito",
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
            console.error('Error:', error);
          },
          complete: function() {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }

  function famEliminar(familiaid) {
    Swal.fire({
      text: '¿Seguro que deseas eliminar la Familia?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/familias.eliminar.php',
          type: 'POST',
          data: {
            familiaid: familiaid
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Familia eliminada con éxito",
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
            console.error('Error:', error);
          },
          complete: function() {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }

  function madesactivar(maletaid) {

    Swal.fire({
      text: '¿Seguro que deseas desactivar la maleta?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, desactivar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/maletas.eliminar.php',
          type: 'POST',
          data: {
            maletaid: maletaid
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Maleta desactivada con éxito",
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
            console.error('Error:', error);
          },
          complete: function() {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }

  function mactivar(maletaid) {

    Swal.fire({
      text: '¿Seguro que deseas activar la maleta?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, activar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/maletas.activar.php',
          type: 'POST',
          data: {
            maletaid: maletaid
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Maleta activada con éxito",
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
      } else {
        return false();
      }
    });
  }

  function tmeliminar(idtipomaleta) {
    Swal.fire({
      text: '¿Seguro que deseas eliminar Tipo de Maleta?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/tipomaletas.eliminar.php',
          type: 'POST',
          data: {
            idtipomaleta: idtipomaleta
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Tipo Maleta eliminado con éxito",
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
      } else {
        return false();
      }
    });
  }
</script>