<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$db = new FirebirdConnection(true);
$sqlFamilias = "SELECT GRUPO_LINEA_ID, NOMBRE FROM GRUPOS_LINEAS ORDER BY NOMBRE";
$familias = $db->query($sqlFamilias);

$sqlCategorias = "SELECT LINEA_ARTICULO_ID, NOMBRE, GRUPO_LINEA_ID FROM LINEAS_ARTICULOS WHERE COALESCE(OCULTO, 'N') = 'N' ORDER BY NOMBRE";
$categorias = $db->query($sqlCategorias);

$sqlArticulosCat = "SELECT A.ARTICULO_ID, A.NOMBRE, X.CLAVE_ARTICULO AS REFERENCIA 
                    FROM ARTICULOS A 
                    LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = A.ARTICULO_ID
                    WHERE A.UNIDAD_VENTA LIKE '%UNIDAD DE SERVICIO%' AND A.ESTATUS = 'A' 
                    ORDER BY A.NOMBRE";
$articulosCat = $db->query($sqlArticulosCat);

$sqlTiposEvento = "SELECT TIPOEVENTO_ID, TIPOEVENTO_NOMBRE FROM AMPAR_CAT_TIPOEVENTO ORDER BY TIPOEVENTO_NOMBRE";
$tiposEvento = $db->query($sqlTiposEvento);

$sqlAlmacenes = "SELECT ALMACEN_ID, ALMACEN_NOMBRE, ALMACEN_FOLIO FROM AMPAR_HIS_ALMACEN WHERE DELETED_AT IS NULL AND ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2) ORDER BY ALMACEN_NOMBRE";
$almacenes = $db->query($sqlAlmacenes);


$db->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <?php include_once("../includes/head.php"); ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
  <style>
    /* Estilos Premium para Equipo Capital */
    .select2-container--default .select2-selection--multiple {
      border: 1px solid #cbd5e1 !important;
      border-radius: 8px !important;
      min-height: 45px !important;
      height: auto !important;
      padding: 2px 4px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
      border-color: #4f46e5 !important;
      box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 5px;
      padding: 2px !important;
      margin: 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background-color: #f1f5f9 !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 6px !important;
      padding: 4px 8px !important;
      margin: 0 !important;
      color: #334155 !important;
      display: flex;
      align-items: center;
      font-size: 0.85rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: #64748b !important;
      margin-right: 6px !important;
      border: none !important;
      position: relative !important;
    }
    .module-header {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: white;
      border-radius: 12px 12px 0 0;
      padding: 20px 25px;
    }

    .module-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .badge-premium-disponible {
      background-color: #ecfdf5 !important;
      color: #065f46 !important;
      border: 1px solid #a7f3d0;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
    }

    .badge-premium-ocupado {
      background-color: #fffbeb !important;
      color: #92400e !important;
      border: 1px solid #fde68a;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
    }

    .btn-action-icon {
      width: 32px;
      height: 32px;
      padding: 0;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid transparent;
    }
    
    .btn-action-edit {
      color: #4f46e5;
      background-color: #e0e7ff;
      border-color: #c7d2fe;
    }
    .btn-action-edit:hover {
      background-color: #4f46e5;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }

    .btn-action-components {
      color: #0284c7;
      background-color: #e0f2fe;
      border-color: #bae6fd;
    }
    .btn-action-components:hover {
      background-color: #0284c7;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.2);
    }

    .btn-action-delete {
      color: #e11d48;
      background-color: #ffe4e6;
      border-color: #fecdd3;
    }
    .btn-action-delete:hover {
      background-color: #e11d48;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.2);
    }

    .badge-premium-evento {
      background-color: #f3e8ff;
      color: #7e22ce;
      border: 1px solid #e9d5ff;
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.75rem;
      letter-spacing: 0.02em;
    }

    .folio-tag {
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
      background-color: #f8fafc;
      border: 1px solid #cbd5e1;
      color: #334155;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      letter-spacing: 0.02em;
      white-space: nowrap;
      display: inline-block;
    }

    .table-container {
      padding: 25px;
    }

    #tablaEquipoCapital thead th {
      background-color: #f8fafc;
      border-bottom: 2px solid #cbd5e1;
      color: #475569;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.05em;
      padding: 14px 18px !important;
    }

    #tablaEquipoCapital tbody td {
      padding: 16px 18px !important;
      vertical-align: middle !important;
      color: #334155;
      border-bottom: 1px solid #e2e8f0;
      font-size: 0.875rem;
    }

    .id-column {
      color: #94a3b8;
      font-weight: 500;
    }

    .table tbody tr {
      transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
      background-color: #f8fafc !important;
    }

    /* Customizing DataTables search & pagination */
    .dataTables_wrapper .dataTables_filter input {
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 6px 12px;
      outline: none;
      transition: all 0.2s ease;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .dataTables_wrapper .dataTables_length select {
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 6px 12px;
    }

    /* Modal Styling */
    .modal-content-premium {
      border: none;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header-premium {
      background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
      color: white;
      border-bottom: none;
      padding: 20px 25px;
    }

    .modal-header-premium .close {
      color: white;
      opacity: 0.8;
      text-shadow: none;
      font-size: 1.5rem;
      transition: opacity 0.2s ease;
    }

    .modal-header-premium .close:hover {
      opacity: 1;
    }

    .modal-body-premium {
      padding: 30px 25px;
      background-color: #f8fafc;
    }

    .modal-footer-premium {
      border-top: 1px solid #f1f5f9;
      padding: 20px 25px;
      background-color: #ffffff;
    }

    .form-group-premium label {
      font-weight: 600;
      color: #334155;
      margin-bottom: 8px;
    }

    .form-control-premium {
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 12px 16px;
      height: auto;
      transition: all 0.2s ease;
    }

    .form-control-premium:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <!-- Breadcrumbs/Title tab -->
                <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                  <div>
                    <h2 class="text-dark font-weight-bold">Equipo Capital</h2>
                    <p class="text-muted mb-0">Gestión de artículos registrados como unidades de servicio.</p>
                  </div>
                </div>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card module-card">
                      <div class="module-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 font-weight-bold"><i class="mdi mdi-cube-outline mr-2"></i>Artículos de Equipo Capital</h4>
                        <div>
                          <button id="btnNuevoEqCapital" class="btn btn-sm btn-info text-white font-weight-bold mr-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                            <i class="mdi mdi-plus"></i> Registrar Nuevo
                          </button>
                          <button id="btnMostrarInactivos" class="btn btn-sm btn-outline-light font-weight-bold" style="border: 1px solid rgba(255,255,255,0.5);">
                            <i class="mdi mdi-eye"></i> Mostrar Inactivos
                          </button>
                        </div>
                      </div>
                      <div class="table-container pb-0">
                        <div class="row mb-3 p-3 bg-light rounded" style="border: 1px solid #e2e8f0; margin: 0 5px;">
                          <div class="col-md-5 mb-2 mb-md-0">
                            <label class="font-weight-bold text-muted small text-uppercase">Familia</label>
                            <select class="form-control" id="filtro_familia">
                              <option value="">Todas las familias</option>
                              <?php foreach ($familias as $fam): ?>
                                <option value="<?= htmlspecialchars($fam['GRUPO_LINEA_ID']) ?>"><?= htmlspecialchars($fam['NOMBRE']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-5 mb-2 mb-md-0">
                            <label class="font-weight-bold text-muted small text-uppercase">Categoría</label>
                            <select class="form-control" id="filtro_categoria">
                              <option value="">Todas las categorías</option>
                              <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['LINEA_ARTICULO_ID']) ?>" data-familia="<?= htmlspecialchars($cat['GRUPO_LINEA_ID']) ?>"><?= htmlspecialchars($cat['NOMBRE']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="btn_limpiar_filtros" class="btn btn-light btn-block font-weight-bold" style="border: 1px solid #cbd5e1; height: 38px;">Limpiar</button>
                          </div>
                        </div>
                      </div>
                      <div class="table-container pt-0">
                        <div class="table-responsive">
                          <table id="tablaEquipoCapital" class="table table-striped table-hover" style="width:100%">
                            <thead>
                              <tr>
                                <th style="width:5%">ID</th>
                                <th style="width:10%">Folio</th>
                                <th style="width:10%">Referencia / Serie</th>
                                <th style="width:10%">Marca</th>
                                <th style="width:20%">Referencia / Desc</th>
                                <th style="width:15%">Ubicación</th>
                                <th style="width:15%">Almacén</th>
                                <th style="width:10%">Tipo Evento</th>
                                <th style="width:10%">Disponibilidad</th>
                                <th style="width:10%">Familia</th>
                                <th style="width:10%">Categoría</th>
                                <th style="width:5%" class="text-center">Acciones</th>
                              </tr>
                            </thead>
                            <tbody></tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Finaliza contenido principal -->
              </div>
            </div>
          </div>
        </div>
        <?php include_once("../includes/footer.php"); ?>
      </div>
    </div>
  </div>

  <?php include_once("../includes/foot.php"); ?>

  <!-- Modal Editar Artículo -->
  <div class="modal fade" id="modalEditarEqCapital" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content modal-content-premium">
        <form id="frmEditarEqCapital">
          <div class="modal-header modal-header-premium">
            <h5 class="modal-title font-weight-bold" id="modalEqCapitalTitulo"><i class="mdi mdi-pencil mr-2"></i>Editar Equipo Capital</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" id="btnCerrarModalX">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body modal-body-premium">
            <input type="hidden" id="edit_equipocapital_id" name="equipocapital_id" value="0">

            <div class="form-group form-group-premium mb-4" id="grp_display_id">
              <label for="display_articulo_id">ID del Registro</label>
              <input type="text" class="form-control form-control-premium" id="display_articulo_id" disabled style="background-color: #f1f5f9; cursor: not-allowed;">
            </div>

            <div class="form-group form-group-premium mb-4" id="grp_articulo">
              <label for="edit_articulo_id">Artículo (Catálogo)</label>
              <select class="form-control form-control-premium" id="edit_articulo_id" name="articulo_id" required style="width: 100%;">
                <option value="">Seleccione un artículo...</option>
                <?php foreach ($articulosCat as $art): ?>
                  <?php $ref = !empty($art['REFERENCIA']) ? '[' . $art['REFERENCIA'] . '] - ' : ''; ?>
                  <option value="<?= htmlspecialchars($art['ARTICULO_ID']) ?>"><?= htmlspecialchars($ref . $art['NOMBRE']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group form-group-premium mb-4">
              <label for="edit_referencia">Referencia / Serie</label>
              <input type="text" class="form-control form-control-premium" id="edit_referencia" name="referencia" maxlength="150" placeholder="Ingrese referencia o serie">
            </div>

            <div class="form-group form-group-premium mb-4">
              <label for="edit_marca">Marca</label>
              <input type="text" class="form-control form-control-premium" id="edit_marca" name="marca" maxlength="100" placeholder="Ej: Sony, HP">
            </div>

            <div class="form-group form-group-premium mb-4">
              <label for="edit_ubicacion">Ubicación</label>
              <input type="text" class="form-control form-control-premium" id="edit_ubicacion" name="ubicacion" maxlength="150" placeholder="Ej: Estante A-1, Almacén">
            </div>

            <div class="form-group form-group-premium mb-4">
              <label for="edit_almacen_id">Almacén (Asignación)</label>
              <select class="form-control form-control-premium" id="edit_almacen_id" name="almacen_id">
                <option value="">Sin asignar</option>
                <?php foreach ($almacenes as $alm): ?>
                  <option value="<?= htmlspecialchars($alm['ALMACEN_ID']) ?>"><?= htmlspecialchars($alm['ALMACEN_FOLIO']) ?> - <?= htmlspecialchars($alm['ALMACEN_NOMBRE']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group form-group-premium mb-4">
              <label for="edit_tipoevento_id">Tipo de Evento</label>
              <select class="form-control form-control-premium" id="edit_tipoevento_id" name="tipoevento_id[]" multiple="multiple" style="width: 100%;">
                <?php foreach ($tiposEvento as $te): ?>
                  <option value="<?= htmlspecialchars($te['TIPOEVENTO_ID']) ?>"><?= htmlspecialchars($te['TIPOEVENTO_NOMBRE']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div id="editErrMsg" class="text-danger small mt-2" style="display:none"></div>
          </div>
          <div class="modal-footer modal-footer-premium">
            <button type="submit" class="btn btn-primary font-weight-bold" style="border-radius: 10px; padding: 12px 24px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border: none;">Guardar</button>
            <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal" style="border-radius: 10px; padding: 12px 24px; border: 1px solid #cbd5e1;" id="btnCerrarModalCancel">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Componentes Adicionales -->
  <div class="modal fade" id="modalComponentesEqCapital" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content modal-content-premium">
        <div class="modal-header modal-header-premium" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
          <h5 class="modal-title font-weight-bold"><i class="mdi mdi-buffer mr-2"></i>Componentes de: <span id="comp_articulo_nombre_titulo"></span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" id="btnCerrarCompModalX">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body modal-body-premium" style="background-color: #f8fafc; padding: 25px;">
          <input type="hidden" id="comp_equipocapital_id">
          
          <!-- Formulario para Agregar/Editar Componente -->
          <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 12px; background-color: #ffffff; border: 1px solid #e2e8f0;">
            <h6 class="font-weight-bold text-dark mb-3" id="comp_form_titulo"><i class="mdi mdi-plus-circle-outline mr-1 text-info"></i>Agregar Componente Adicional</h6>
            <form id="frmGuardarComponente">
              <input type="hidden" id="comp_componente_id" name="componente_id" value="0">
              
              <div class="row align-items-end mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                  <div class="form-group mb-0">
                    <label for="comp_nombre" class="small font-weight-bold text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Nombre del Componente</label>
                    <input type="text" class="form-control form-control-premium form-control-sm" style="border-radius: 8px; padding: 10px;" id="comp_nombre" name="nombre" required maxlength="150" placeholder="Ej: Cable de corriente, Maletín, etc.">
                  </div>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                  <div class="form-group mb-0">
                    <label for="comp_cantidad" class="small font-weight-bold text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Cantidad</label>
                    <input type="number" class="form-control form-control-premium form-control-sm" style="border-radius: 8px; padding: 10px;" id="comp_cantidad" name="cantidad" required min="1" value="1">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-0">
                    <label for="comp_marca" class="small font-weight-bold text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Marca (Opcional)</label>
                    <input type="text" class="form-control form-control-premium form-control-sm" style="border-radius: 8px; padding: 10px;" id="comp_marca" name="marca" maxlength="100" placeholder="Ej: Sony, HP">
                  </div>
                </div>
              </div>
              
              <div class="row align-items-end">
                <div class="col-md-5 mb-2 mb-md-0">
                  <div class="form-group mb-0">
                    <label for="comp_serie" class="small font-weight-bold text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Número de Serie (Opcional)</label>
                    <input type="text" class="form-control form-control-premium form-control-sm" style="border-radius: 8px; padding: 10px;" id="comp_serie" name="serie" maxlength="100" placeholder="Ej: SN12345678">
                  </div>
                </div>
                <div class="col-md-5 mb-2 mb-md-0">
                  <div class="form-group mb-0">
                    <label for="comp_referencia" class="small font-weight-bold text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Referencia (Opcional)</label>
                    <input type="text" class="form-control form-control-premium form-control-sm" style="border-radius: 8px; padding: 10px;" id="comp_referencia" name="referencia" maxlength="100" placeholder="Ej: REF-ABC">
                  </div>
                </div>
                <div class="col-md-2">
                  <button type="submit" id="btnGuardarCompSubmit" class="btn btn-info btn-sm btn-block font-weight-bold text-white" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                    <i class="mdi mdi-content-save"></i> Guardar
                  </button>
                </div>
              </div>
              <div id="compFormError" class="text-danger small mt-2" style="display:none"></div>
            </form>
            <div id="compEditCancelArea" class="mt-2 text-right" style="display:none">
              <button type="button" id="btnCancelarCompEdit" class="btn btn-xs btn-link text-muted" style="font-size: 0.8rem; text-decoration: none;"><i class="mdi mdi-close"></i> Cancelar Edición</button>
            </div>
          </div>

          <!-- Tabla de Componentes Registrados -->
          <h6 class="font-weight-bold text-dark mb-2"><i class="mdi mdi-format-list-bulleted mr-1 text-primary"></i>Componentes Registrados</h6>
          <div class="table-responsive bg-white rounded shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
            <table class="table table-striped table-hover mb-0" id="tablaComponentes">
              <thead>
                <tr>
                  <th style="width: 30%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Nombre</th>
                  <th style="width: 10%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;" class="text-center">Cantidad</th>
                  <th style="width: 15%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Marca</th>
                  <th style="width: 15%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">N° Serie</th>
                  <th style="width: 15%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Referencia</th>
                  <th style="width: 15%; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;" class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaComponentesBody">
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">Cargando componentes...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer modal-footer-premium">
          <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal" style="border-radius: 10px; padding: 12px 24px; border: 1px solid #cbd5e1;" id="btnCerrarCompModalCancel">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function() {
      $('#edit_tipoevento_id').select2({
        dropdownParent: $('#modalEditarEqCapital'),
        placeholder: 'Seleccione uno o varios...',
        allowClear: true
      });
      
      $('#edit_articulo_id').select2({
        dropdownParent: $('#modalEditarEqCapital'),
        placeholder: 'Seleccione un artículo...',
        allowClear: true,
        width: '100%'
      });
      
      let tabla;
      let mostrarInactivos = false;

      // Inicializar DataTable
      function inicializarTabla() {
        if (tabla) {
          tabla.destroy();
        }

        tabla = $('#tablaEquipoCapital').DataTable({
          processing: true,
          serverSide: false,
          ajax: {
            url: '../ajax/equipocapital.listar.php',
            type: 'POST',
            data: function(d) {
              d.familia_id = $('#filtro_familia').val();
              d.categoria_id = $('#filtro_categoria').val();
              d.mostrar_inactivos = mostrarInactivos ? 1 : 0;
            },
            dataSrc: function(json) {
              if (!json.ok) {
                console.error('Error al cargar equipo capital:', json.msg);
                return [];
              }
              return json.items || [];
            }
          },
          columns: [
            {
              data: 'EQUIPOCAPITAL_ID',
              className: 'align-middle id-column text-center'
            },
            {
              data: 'FOLIO',
              className: 'align-middle text-center',
              render: function(data) {
                return data ? `<span class="folio-tag">${escapeHtml(data)}</span>` : '<span class="text-muted italic" style="font-style: italic;">Sin folio</span>';
              }
            },
            {
              data: 'REFERENCIA',
              className: 'align-middle',
              render: function(data) {
                return data ? `<strong>${escapeHtml(data)}</strong>` : '<span class="text-muted italic" style="font-style: italic;">Sin referencia</span>';
              }
            },
            {
              data: 'MARCA',
              className: 'align-middle',
              render: function(data) {
                return data ? `<span class="text-dark font-weight-bold">${escapeHtml(data)}</span>` : '<span class="text-muted italic" style="font-style: italic;">Sin marca</span>';
              }
            },
            {
              data: 'NOMBRE',
              className: 'align-middle font-weight-bold text-dark',
              render: function(data, type, row) {
                let ref = row.CATALOG_REFERENCIA ? `[${escapeHtml(row.CATALOG_REFERENCIA)}] - ` : '';
                return ref + escapeHtml(data);
              }
            },
            {
              data: 'UBICACION',
              className: 'align-middle',
              render: function(data) {
                return data ? `<span><i class="mdi mdi-map-marker mr-1 text-muted"></i>${escapeHtml(data)}</span>` : '<span class="text-muted italic" style="font-style: italic;">Sin ubicación</span>';
              }
            },
            {
              data: 'ALMACEN_NOMBRE',
              className: 'align-middle',
              render: function(data) {
                return data ? `<span><i class="mdi mdi-store mr-1 text-muted"></i>${escapeHtml(data)}</span>` : '<span class="text-muted italic" style="font-style: italic;">Sin asignar</span>';
              }
            },
            {
              data: 'TIPOEVENTO_NOMBRE',
              className: 'align-middle',
              render: function(data) {
                if (!data) return '<span class="text-muted italic" style="font-style: italic;">Ninguno</span>';
                let types = data.split(',');
                let badges = types.map(t => `<span class="badge badge-premium-evento mr-1">${escapeHtml(t.trim())}</span>`);
                return badges.join('');
              }
            },
            {
              data: 'OCUPADO',
              className: 'align-middle text-center',
              render: function(data, type, row) {
                if (data === 'S') {
                  return '<span class="badge badge-premium-ocupado" title="Asignado a ' + row.CANTIDAD_EVENTOS_ACTIVOS + ' evento(s) activo(s)"><i class="mdi mdi-lock mr-1"></i>Ocupado (' + row.CANTIDAD_EVENTOS_ACTIVOS + ')</span>';
                } else {
                  return '<span class="badge badge-premium-disponible"><i class="mdi mdi-lock-open mr-1"></i>Disponible</span>';
                }
              }
            },
            {
              data: 'FAMILIA_NOMBRE',
              className: 'align-middle',
              render: function(data) {
                return data ? escapeHtml(data) : '<span class="text-muted italic" style="font-style: italic;">Sin asignar</span>';
              }
            },
            {
              data: 'CATEGORIA_NOMBRE',
              className: 'align-middle',
              render: function(data) {
                return data ? escapeHtml(data) : '<span class="text-muted italic" style="font-style: italic;">Sin asignar</span>';
              }
            },
            {
              data: null,
              className: 'align-middle text-center',
              orderable: false,
              render: function(data, type, row) {
                return `
                  <div class="d-flex justify-content-center align-items-center">
                    <button class="btn btn-action-icon btn-action-edit btn-edit-eq mr-2" 
                            title="Editar" data-toggle="tooltip" data-placement="top"
                            data-id="${row.EQUIPOCAPITAL_ID}" 
                            data-articulo-id="${row.ARTICULO_ID}"
                            data-nombre="${escapeHtml(row.NOMBRE)}"
                            data-referencia="${escapeHtml(row.REFERENCIA || '')}"
                            data-marca="${escapeHtml(row.MARCA || '')}"
                            data-ubicacion="${escapeHtml(row.UBICACION || '')}"
                            data-almacen="${row.ALMACEN_ID || ''}"
                            data-tipoevento="${row.TIPOEVENTO_ID || ''}">
                      <i class="mdi mdi-pencil"></i>
                    </button>
                    <button class="btn btn-action-icon btn-action-components btn-components-eq mr-2" 
                            title="Componentes" data-toggle="tooltip" data-placement="top"
                            data-id="${row.EQUIPOCAPITAL_ID}" 
                            data-nombre="${escapeHtml(row.NOMBRE)}">
                      <i class="mdi mdi-buffer"></i>
                    </button>
                    <button class="btn btn-action-icon btn-action-edit btn-print-eq mr-2" 
                            title="Imprimir Etiqueta" data-toggle="tooltip" data-placement="top"
                            data-folio="${row.FOLIO}" style="color: #0f766e; background-color: #ccfbf1; border-color: #99f6e4;">
                      <i class="mdi mdi-printer"></i>
                    </button>
                    <button class="btn btn-action-icon btn-action-delete btn-delete-eq" 
                            title="Eliminar" data-toggle="tooltip" data-placement="top"
                            data-id="${row.EQUIPOCAPITAL_ID}" 
                            data-nombre="${escapeHtml(row.NOMBRE)}">
                      <i class="mdi mdi-delete"></i>
                    </button>
                  </div>
                `;
              }
            }
          ],
          order: [
            [0, 'desc']
          ],
          language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
          },
          pageLength: 25,
          lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "Todos"]
          ],
          drawCallback: function(settings) {
            $('[data-toggle="tooltip"]').tooltip();
          }
        });
      }

      // Escape HTML helper
      function escapeHtml(str) {
        if (!str) return '';
        return str
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }

      // Filtros superiores
      $('#filtro_familia').on('change', function() {
        const familiaId = $(this).val();
        $('#filtro_categoria option').each(function() {
          const $opt = $(this);
          if ($opt.val() === "") {
            $opt.show();
            return;
          }
          if (familiaId === "" || $opt.data('familia') == familiaId) {
            $opt.show();
          } else {
            $opt.hide();
          }
        });
        $('#filtro_categoria').val('');
        tabla.ajax.reload();
      });

      $('#filtro_categoria').on('change', function() {
        tabla.ajax.reload();
      });

      $('#btn_limpiar_filtros').on('click', function() {
        $('#filtro_familia').val('').trigger('change');
      });

      $('#btnMostrarInactivos').on('click', function() {
        mostrarInactivos = !mostrarInactivos;
        
        if (mostrarInactivos) {
          $(this).removeClass('btn-outline-light').addClass('btn-light text-dark')
                 .html('<i class="mdi mdi-eye-off"></i> Ocultar Inactivos');
        } else {
          $(this).removeClass('btn-light text-dark').addClass('btn-outline-light')
                 .html('<i class="mdi mdi-eye"></i> Mostrar Inactivos');
        }

        tabla.ajax.reload();
      });

      // Botón Registrar Nuevo
      $('#btnNuevoEqCapital').on('click', function() {
        $('#edit_equipocapital_id').val('0');
        $('#display_articulo_id').val('');
        $('#grp_display_id').hide();
        $('#edit_articulo_id').val('').prop('disabled', false).trigger('change');
        $('#edit_referencia').val('');
        $('#edit_marca').val('');
        $('#edit_ubicacion').val('');
        $('#edit_almacen_id').val('');
        $('#edit_tipoevento_id').val(null).trigger('change');
        
        $('#modalEqCapitalTitulo').html('<i class="mdi mdi-plus-circle mr-2"></i>Registrar Equipo Capital');
        $('#editErrMsg').hide().text('');
        $('#modalEditarEqCapital').modal('show');
      });

      // 14. Eventos para botones
    $('#equiposTable tbody, #tablaEquipoCapital tbody').on('click', '.btn-edit-eq', function() {
      const btn = $(this);
      const data = {
        EQUIPOCAPITAL_ID: btn.data('id'),
        ARTICULO_ID: btn.data('articulo-id'),
        NOMBRE: btn.data('nombre'),
        REFERENCIA: btn.data('referencia'),
        MARCA: btn.data('marca'),
        UBICACION: btn.data('ubicacion'),
        ALMACEN_ID: btn.data('almacen'),
        TIPOEVENTO_ID: btn.data('tipoevento')
      };
      editarEquipo(data);
    });

    $('#equiposTable tbody, #tablaEquipoCapital tbody').on('click', '.btn-components-eq', function() {
      const equipoId = $(this).data('id');
      const nombre = $(this).data('nombre');
      gestionarComponentes(equipoId, nombre);
    });

    $('#equiposTable tbody, #tablaEquipoCapital tbody').on('click', '.btn-delete-eq', function() {
      const equipoId = $(this).data('id');
      const nombre = $(this).data('nombre');
      eliminarEquipo(equipoId, nombre);
    });

    $('#equiposTable tbody, #tablaEquipoCapital tbody').on('click', '.btn-print-eq', function() {
      const folio = $(this).data('folio');
      if (!folio) {
        Swal.fire('Error', 'No hay folio asociado a este equipo', 'error');
        return;
      }
      
      // Llamar al microservicio de impresión local (ej: http://localhost:8090/imprimir-equipo)
      // Ajustar la IP a la IP correcta si el backend de PHP está en otra PC, o si es la misma máquina.
      // Suponemos que corre en el mismo servidor donde se ve la interfaz o en una IP local conocida
      const printerApiUrl = 'http://localhost:8090/imprimir-equipo';
      
      Swal.fire({
        title: 'Imprimiendo...',
        text: `Enviando etiqueta para folio ${folio} a la impresora...`,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
          
          fetch(printerApiUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ folio: folio })
          })
          .then(response => {
            if (!response.ok) {
              return response.json().then(err => { throw new Error(err.detail || 'Error en la impresión'); });
            }
            return response.json();
          })
          .then(data => {
            Swal.fire('¡Éxito!', `Etiqueta para ${folio} impresa correctamente`, 'success');
          })
          .catch(error => {
            Swal.fire('Error', error.message || 'No se pudo conectar con el servicio de impresión en localhost:8090', 'error');
          });
        }
      });
    });

      // Abrir modal de edición
      function editarEquipo(data) {
        const id = data.EQUIPOCAPITAL_ID;
        const articuloId = data.ARTICULO_ID;
        let tipoEventoId = String(data.TIPOEVENTO_ID).split(',');
        if (tipoEventoId.length === 1 && (tipoEventoId[0] === "null" || tipoEventoId[0] === "")) tipoEventoId = null;

        $('#edit_equipocapital_id').val(id);
        $('#display_articulo_id').val(id);
        $('#grp_display_id').show();
        $('#edit_articulo_id').val(articuloId).prop('disabled', true).trigger('change');
        $('#edit_referencia').val(data.REFERENCIA);
        $('#edit_marca').val(data.MARCA);
        $('#edit_ubicacion').val(data.UBICACION);
        $('#edit_almacen_id').val(data.ALMACEN_ID);
        $('#edit_tipoevento_id').val(tipoEventoId).trigger('change');

        $('#modalEqCapitalTitulo').html('<i class="mdi mdi-pencil mr-2"></i>Editar Equipo Capital');
        $('#editErrMsg').hide().text('');
        $('#modalEditarEqCapital').modal('show');
      }

      // Enviar formulario de edición/registro
      $('#frmEditarEqCapital').on('submit', function(e) {
        e.preventDefault();
        
        // Habilitar temporalmente select para serializar su valor
        $('#edit_articulo_id').prop('disabled', false);
        const fd = $(this).serialize();
        // Volver a deshabilitar si estamos editando
        if ($('#edit_equipocapital_id').val() !== '0') {
          $('#edit_articulo_id').prop('disabled', true);
        }

        $('#editErrMsg').hide().text('');

        $.post('../ajax/equipocapital.editar.php', fd, function(r) {
          try {
            r = (typeof r === 'string') ? JSON.parse(r) : r;
          } catch (e) {}

          if (!r.ok) {
            $('#editErrMsg').text(r.msg || 'Error al guardar').show();
            return;
          }

          $('#modalEditarEqCapital').modal('hide');
          tabla.ajax.reload(null, false);

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '¡Guardado!',
              text: r.msg || 'Guardado correctamente.',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(r.msg || 'Guardado correctamente.');
          }
        }).fail(function(xhr) {
          let errorMsg = 'Error en el servidor al guardar.';
          try {
            const resp = JSON.parse(xhr.responseText);
            if (resp && resp.msg) {
              errorMsg = resp.msg;
            }
          } catch(e) {}
          $('#editErrMsg').text(errorMsg).show();
        });
      });

      // Eliminar Equipo Capital
      $(document).on('click', '.btn-delete-eq', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        const realizarEliminacion = function() {
          $.post('../ajax/equipocapital.eliminar.php', { equipocapital_id: id }, function(r) {
            try {
              r = (typeof r === 'string') ? JSON.parse(r) : r;
            } catch (e) {}

            if (!r.ok) {
              if (typeof Swal !== 'undefined') {
                Swal.fire('Error', r.msg || 'No se pudo eliminar', 'error');
              } else {
                alert(r.msg || 'No se pudo eliminar');
              }
              return;
            }

            tabla.ajax.reload(null, false);

            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'Equipo capital eliminado correctamente.',
                timer: 1500,
                showConfirmButton: false
              });
            }
          }).fail(function() {
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', 'Error de red al eliminar', 'error');
            } else {
              alert('Error de red al eliminar');
            }
          });
        };

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar el equipo capital "${nombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
          }).then((result) => {
            if (result.isConfirmed) {
              realizarEliminacion();
            }
          });
        } else {
          if (confirm(`¿Deseas eliminar el equipo capital "${nombre}"?`)) {
            realizarEliminacion();
          }
        }
      });

      // Forzar cierre de modal
      $('#btnCerrarModalX, #btnCerrarModalCancel').on('click', function() {
        $('#modalEditarEqCapital').modal('hide');
      });

      // --- Lógica de Componentes Adicionales ---

      // Cargar lista de componentes
      function cargarComponentes(equipocapitalId) {
        $('#tablaComponentesBody').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="mdi mdi-spin mdi-loading mr-1"></i>Cargando componentes...</td></tr>');
        
        $.ajax({
          url: '../ajax/equipocapital.componentes.listar.php',
          type: 'POST',
          data: { equipocapital_id: equipocapitalId },
          dataType: 'json',
          success: function(r) {
            if (!r.ok) {
              $('#tablaComponentesBody').html(`<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(r.msg || 'Error al cargar')}</td></tr>`);
              return;
            }

            const items = r.items || [];
            if (items.length === 0) {
              $('#tablaComponentesBody').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="mdi mdi-information-outline mr-1"></i>No hay componentes registrados para este equipo.</td></tr>');
              return;
            }

            let html = '';
            items.forEach(function(item) {
              html += `
                <tr data-id="${item.COMPONENTE_ID}" 
                    data-nombre="${escapeHtml(item.NOMBRE)}" 
                    data-cantidad="${item.CANTIDAD}"
                    data-marca="${escapeHtml(item.MARCA || '')}"
                    data-serie="${escapeHtml(item.SERIE || '')}"
                    data-referencia="${escapeHtml(item.REFERENCIA || '')}">
                  <td class="align-middle" style="padding: 12px 15px;">${escapeHtml(item.NOMBRE)}</td>
                  <td class="align-middle text-center font-weight-bold" style="padding: 12px 15px;">${item.CANTIDAD}</td>
                  <td class="align-middle" style="padding: 12px 15px;">${item.MARCA ? escapeHtml(item.MARCA) : '<span class="text-muted italic" style="font-style: italic;">Sin marca</span>'}</td>
                  <td class="align-middle" style="padding: 12px 15px;">${item.SERIE ? escapeHtml(item.SERIE) : '<span class="text-muted italic" style="font-style: italic;">Sin serie</span>'}</td>
                  <td class="align-middle" style="padding: 12px 15px;">${item.REFERENCIA ? escapeHtml(item.REFERENCIA) : '<span class="text-muted italic" style="font-style: italic;">Sin ref</span>'}</td>
                  <td class="align-middle text-center" style="padding: 12px 15px;">
                    <button class="btn btn-xs btn-outline-primary btn-edit-component mr-1" title="Editar Componente" style="padding: 4px 8px; border-radius: 6px;">
                      <i class="mdi mdi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger btn-delete-component" title="Eliminar Componente" style="padding: 4px 8px; border-radius: 6px;">
                      <i class="mdi mdi-delete"></i>
                    </button>
                  </td>
                </tr>
              `;
            });
            $('#tablaComponentesBody').html(html);
          },
          error: function() {
            $('#tablaComponentesBody').html('<tr><td colspan="6" class="text-center text-danger py-4">Error de conexión al cargar componentes.</td></tr>');
          }
        });
      }

      // Limpiar y resetear el formulario de componente
      function resetFormularioComponente() {
        $('#comp_componente_id').val('0');
        $('#comp_nombre').val('');
        $('#comp_cantidad').val('1');
        $('#comp_marca').val('');
        $('#comp_serie').val('');
        $('#comp_referencia').val('');
        $('#compFormError').hide().text('');
        $('#comp_form_titulo').html('<i class="mdi mdi-plus-circle-outline mr-1 text-info"></i>Agregar Componente Adicional');
        $('#compEditCancelArea').hide();
      }

      // Abrir modal de componentes
      $(document).on('click', '.btn-components-eq', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#comp_equipocapital_id').val(id);
        $('#comp_articulo_nombre_titulo').text(nombre);
        
        resetFormularioComponente();
        cargarComponentes(id);

        $('#modalComponentesEqCapital').modal('show');
      });

      // Cancelar edición de componente
      $('#btnCancelarCompEdit').on('click', function() {
        resetFormularioComponente();
      });

      // Submit de guardar/editar componente
      $('#frmGuardarComponente').on('submit', function(e) {
        e.preventDefault();
        $('#compFormError').hide().text('');

        const equipocapitalId = $('#comp_equipocapital_id').val();
        const fd = $(this).serialize() + '&equipocapital_id=' + equipocapitalId;

        $.ajax({
          url: '../ajax/equipocapital.componentes.guardar.php',
          type: 'POST',
          data: fd,
          dataType: 'json',
          success: function(r) {
            if (!r.ok) {
              $('#compFormError').text(r.msg || 'Error al guardar el componente').show();
              return;
            }

            resetFormularioComponente();
            cargarComponentes(equipocapitalId);

            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: '¡Guardado!',
                text: r.msg,
                timer: 1500,
                showConfirmButton: false
              });
            }
          },
          error: function() {
            $('#compFormError').text('Error de servidor al guardar componente').show();
          }
        });
      });

      // Click en botón editar componente (carga en formulario)
      $(document).on('click', '.btn-edit-component', function() {
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const nombre = $tr.data('nombre');
        const cantidad = $tr.data('cantidad');
        const marca = $tr.data('marca');
        const serie = $tr.data('serie');
        const referencia = $tr.data('referencia');

        $('#comp_componente_id').val(id);
        $('#comp_nombre').val(nombre).focus();
        $('#comp_cantidad').val(cantidad);
        $('#comp_marca').val(marca);
        $('#comp_serie').val(serie);
        $('#comp_referencia').val(referencia);
        
        $('#comp_form_titulo').html('<i class="mdi mdi-pencil-circle-outline mr-1 text-warning"></i>Editar Componente');
        $('#compEditCancelArea').show();
        $('#compFormError').hide().text('');
      });

      // Click en botón eliminar componente
      $(document).on('click', '.btn-delete-component', function() {
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const nombre = $tr.data('nombre');
        const equipocapitalId = $('#comp_equipocapital_id').val();

        const realizarEliminacion = function() {
          $.ajax({
            url: '../ajax/equipocapital.componentes.eliminar.php',
            type: 'POST',
            data: { componente_id: id, equipocapital_id: equipocapitalId },
            dataType: 'json',
            success: function(r) {
              if (!r.ok) {
                if (typeof Swal !== 'undefined') {
                  Swal.fire('Error', r.msg || 'No se pudo eliminar', 'error');
                } else {
                  alert(r.msg || 'No se pudo eliminar');
                }
                return;
              }

              cargarComponentes(equipocapitalId);

              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'success',
                  title: 'Eliminado',
                  text: 'Componente eliminado correctamente.',
                  timer: 1500,
                  showConfirmButton: false
                });
              }
            },
            error: function() {
              if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Error de red al eliminar', 'error');
              } else {
                alert('Error de red al eliminar');
              }
            }
          });
        };

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar el componente "${nombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
          }).then((result) => {
            if (result.isConfirmed) {
              realizarEliminacion();
            }
          });
        } else {
          if (confirm(`¿Deseas eliminar el componente "${nombre}"?`)) {
            realizarEliminacion();
          }
        }
      });

      // Forzar cierre de modal de componentes
      $('#btnCerrarCompModalX, #btnCerrarCompModalCancel').on('click', function() {
        $('#modalComponentesEqCapital').modal('hide');
      });

      // Inicializar
      inicializarTabla();
    })();
  </script>
</body>

</html>
