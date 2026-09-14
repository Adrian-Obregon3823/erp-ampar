<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <?php include_once("../includes/head.php"); ?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
  <style>
    /* Fix paginacion DataTables texto blanco en fondo blanco */
    .page-item.active .page-link {
      background-color: #1f3bb3 !important;
      color: #ffffff !important;
      border-color: #1f3bb3 !important;
    }

    /* Estilo premium para Select2 */
    .select2-container--default .select2-selection--single {
      height: 42px !important;
      border: 1px solid #e9ecef;
      border-radius: 8px;
      background-color: #fff;
      transition: all 0.2s ease;
      padding: 0 !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
      border-color: #1f3bb3;
      box-shadow: 0 0 0 0.2rem rgba(31, 59, 179, 0.1);
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 40px !important;
      padding-left: 15px !important;
      padding-right: 35px !important;
      color: #495057;
      font-weight: 500;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 40px !important;
      right: 10px;
    }

    .select2-container--default .select2-selection--single .select2-selection__clear {
      height: 40px !important;
      align-items: center;
      display: flex;
    }

    .filter-container {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
      border: 1px solid #edf2f9;
      margin-bottom: 25px;
    }

    .filter-label {
      font-size: 1rem;
      font-weight: 700;
      color: black;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
    }

    .filter-label i {
      margin-right: 6px;
      font-size: 1.1rem;
    }

    .badge-activo {
      background-color: #28a745;
    }

    .badge-inactivo {
      background-color: #dc3545;
    }

    .editable-nombre,
    .editable-referencia,
    .sin-referencia {
      cursor: pointer;
      border-bottom: 1px dashed #ccc;
    }

    .editable-nombre:hover,
    .editable-referencia:hover,
    .sin-referencia:hover {
      background-color: #f8f9fa;
    }

    @media (max-width: 767px) {
      #tablaArticulos thead {
        display: none;
      }
      #tablaArticulos tbody tr {
        display: block;
        margin-bottom: 1rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 10px;
        border: 1px solid #dee2e6;
      }
      #tablaArticulos tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 5px;
        border-top: none;
        border-bottom: 1px solid #eee;
        text-align: right;
      }
      #tablaArticulos tbody td:last-child {
        border-bottom: none;
      }
      #tablaArticulos tbody td::before {
        content: attr(data-label);
        font-weight: bold;
        text-align: left;
        margin-right: 15px;
        color: #495057;
      }
      #tablaArticulos tbody td.dataTables_empty {
        display: block;
        text-align: center;
      }
      #tablaArticulos tbody td.dataTables_empty::before {
        display: none;
      }
      /* Prevenir scroll horizontal por paginación y anchos mínimos */
      .dataTables_wrapper .pagination {
        flex-wrap: wrap;
        justify-content: center;
      }
      .dataTables_wrapper .page-item {
        margin-bottom: 5px;
      }
      #tablaArticulos {
        min-width: 0 !important;
      }
      .table-responsive {
        overflow-x: visible !important;
      }
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
                <?php include_once("../includes/articulos.menu.php"); ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center text-center text-md-left">
                        <h4 class="mb-3 mb-md-0">Listado de Artículos</h4>
                        <div>
                          <button id="btnDesactivarMulti" class="btn btn-sm btn-danger mb-2 mb-md-0 mr-md-2" style="display:none;">
                            <i class="mdi mdi-delete-sweep"></i> Desactivar Seleccionados (<span id="countSeleccionados">0</span>)
                          </button>
                          <button id="btnNuevoArticulo" class="btn btn-sm btn-primary mb-2 mb-md-0 mr-md-2">
                            <i class="mdi mdi-plus"></i> Nuevo Artículo
                          </button>
                          <button id="btnMostrarInactivos" class="btn btn-sm btn-outline-secondary mb-2 mb-md-0">
                            <i class="mdi mdi-eye"></i> Mostrar Inactivos
                          </button>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="filter-container">
                          <div class="row align-items-end">
                            <div class="col-12 col-md-3 mb-3 mb-md-0">
                              <label class="filter-label">
                                Familia
                              </label>
                              <select id="filtroFamilia" class="form-control" style="width:100%">
                                <option value="">Todas las familias</option>
                              </select>
                            </div>
                            <div class="col-12 col-md-3 mb-3 mb-md-0">
                              <label class="filter-label">
                                División
                              </label>
                              <select id="filtroDivision" class="form-control" style="width:100%">
                                <option value="">Todas las divisiones</option>
                              </select>
                            </div>
                            <div class="col-12 col-md-3 mb-3 mb-md-0">
                              <label class="filter-label">
                                Categoría
                              </label>
                              <select id="filtroCategoria" class="form-control" style="width:100%">
                                <option value="">Todas las categorías</option>
                              </select>
                            </div>
                            <div class="col-12 col-md-3 mt-2 mt-md-0">
                              <button id="btnLimpiarFiltros" class="btn btn-outline-primary w-100" style="border-radius: 8px;">
                                Limpiar
                              </button>
                            </div>
                          </div>
                        </div>

                        <div class="table-responsive">
                          <table id="tablaArticulos" class="table table-striped table-hover" style="width:100%">
                            <thead>
                              <tr>
                                <th style="min-width:40px" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th style="min-width:60px">ID</th>
                                <th style="min-width:120px">Referencia</th>
                                <th style="min-width:250px">Nombre</th>
                                <th style="min-width:100px">Clave SAT</th>
                                <th style="min-width:150px">Familia</th>
                                <th style="min-width:150px">División</th>
                                <th style="min-width:150px">Categoría</th>
                                <th style="min-width:100px">Estado</th>
                                <th style="min-width:120px">Acciones</th>
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

  <!-- Modal Nuevo Artículo -->
  <div class="modal fade" id="modalNuevoArticulo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="frmNuevoArticulo">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Nuevo Artículo</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
            
            <h6 class="text-primary mt-2">1. Datos Generales</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nombre del Artículo <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="nombre" required maxlength="255">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Clave (Referencia)</label>
                  <input type="text" class="form-control" name="clave" maxlength="50">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Familia</label>
                  <select id="nuevo_familia" class="form-control select2-modal" style="width:100%"></select>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">División</label>
                  <select id="nuevo_division" class="form-control select2-modal" style="width:100%"></select>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Categoría <span class="text-danger">*</span></label>
                  <select id="nuevo_categoria" name="categoria_id" class="form-control select2-modal" style="width:100%" required></select>
                </div>
            </div>

            <hr>
            <h6 class="text-primary mt-2">2. Unidades y Cantidades</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                  <label class="form-label">Unidad de medida</label>
                  <select class="form-control" name="unidad_venta" id="nuevo_unidad_venta"></select>
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">Unidad de compra</label>
                  <input type="text" class="form-control" name="unidad_compra" id="nuevo_unidad_compra">
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">Contenido</label>
                  <input type="number" step="0.01" class="form-control" name="contenido_unidad_compra" value="1">
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">Clave SAT</label>
                  <input type="text" class="form-control" name="clave_sat" maxlength="20">
                </div>
            </div>

            <hr>
            <h6 class="text-primary mt-2">3. Configuración y Logística</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="custom-control custom-checkbox mt-4">
                        <input type="checkbox" class="custom-control-input" id="es_almacenable" name="es_almacenable" value="S" checked>
                        <label class="custom-control-label" for="es_almacenable">Almacenable</label>
                    </div>
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="es_juego" name="es_juego" value="S">
                        <label class="custom-control-label" for="es_juego">Juego (Kit)</label>
                    </div>
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="es_peso_variable" name="es_peso_variable" value="S">
                        <label class="custom-control-label" for="es_peso_variable">Pesar en la báscula</label>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Peso unitario</label>
                    <input type="number" step="0.01" class="form-control" name="peso_unitario" value="0.00">
                    <label class="form-label mt-2">Pedimentos</label>
                    <select class="form-control" name="pedimentos">
                        <option value="N">No requiere</option>
                        <option value="S">Siempre importado</option>
                    </select>
                    <label class="form-label mt-2">Arancel (%)</label>
                    <input type="number" step="0.01" class="form-control" name="pctje_arancel" value="0.00">
                </div>
                <div class="col-md-4 mb-3 border rounded p-2">
                    <label class="form-label font-weight-bold">Seguimiento de las unidades</label>
                    <div class="custom-control custom-radio">
                      <input type="radio" id="seg_normal" name="seguimiento" class="custom-control-input" value="N" checked>
                      <label class="custom-control-label" for="seg_normal">Normal</label>
                    </div>
                    <div class="custom-control custom-radio">
                      <input type="radio" id="seg_lotes" name="seguimiento" class="custom-control-input" value="L">
                      <label class="custom-control-label" for="seg_lotes">Lotes</label>
                    </div>
                    <div class="custom-control custom-radio">
                      <input type="radio" id="seg_serie" name="seguimiento" class="custom-control-input" value="S">
                      <label class="custom-control-label" for="seg_serie">Números de serie</label>
                    </div>
                    <label class="form-label mt-2">Días de garantía:</label>
                    <input type="number" class="form-control form-control-sm w-50 d-inline-block" name="dias_garantia" value="0">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Estatus</label>
                    <select class="form-control" name="estatus">
                        <option value="A">Activo</option>
                        <option value="S">Suspendido</option>
                    </select>
                </div>
            </div>

            <hr>
            <h6 class="text-success mt-2">4. Precios de Venta (Subtotal)</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Precio Base de Venta (Subtotal sin IVA)</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="0.01" class="form-control" name="precio_base_venta" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <div id="contenedorPreciosVenta">
                <!-- Filas dinámicas de precios por cliente -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-success mt-2" id="btnAddPrecioVenta"><i class="mdi mdi-plus"></i> Añadir precio por cliente</button>

            <hr>
            <h6 class="text-info mt-3">5. Precios de Compra (Subtotal)</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Precio Base de Compra (Costo Base)</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="0.01" class="form-control" name="precio_base_compra" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div id="contenedorPreciosCompra">
                <!-- Filas dinámicas de precios por proveedor -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-info mt-2" id="btnAddPrecioCompra"><i class="mdi mdi-plus"></i> Añadir precio por proveedor</button>


            <div id="nuevoErrMsg" class="text-danger small mt-3" style="display:none"></div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Registrar Artículo</button>
            <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Editar Nombre -->
  <div class="modal fade" id="modalEditarNombre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="frmEditarNombre">
          <div class="modal-header">
            <h5 class="modal-title">Editar Nombre del Artículo</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_articulo_id" name="articulo_id">

            <div class="mb-3">
              <label class="form-label">Clave (Referencia):</label>
              <input type="text" class="form-control" id="edit_clave" name="clave" maxlength="50">
              <div class="form-text">Puedes dejarla vacía si el artículo no tiene clave.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Nombre del Artículo:</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required maxlength="255">
            </div>

            <div class="mb-3">
              <label class="form-label">Familia:</label>
              <select id="edit_familia" name="familia_id" class="form-control" style="width:100%"></select>
            </div>

            <div class="mb-3">
              <label class="form-label">División:</label>
              <select id="edit_division" name="division_id" class="form-control" style="width:100%"></select>
            </div>

            <div class="mb-3">
              <label class="form-label">Categoría:</label>
              <select id="edit_categoria" name="categoria_id" class="form-control" style="width:100%" required></select>
            </div>

            <div id="editErrMsg" class="text-danger small" style="display:none"></div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    (function() {
      let tabla;
      let mostrarInactivos = false;

      // Inicializar DataTable
      function inicializarTabla() {
        if (tabla) {
          tabla.destroy();
        }

        tabla = $('#tablaArticulos').DataTable({
          processing: true,
          serverSide: false,
          ajax: {
            url: '../ajax/articulos.listar.php',
            data: function(d) {
              d.mostrar_inactivos = mostrarInactivos ? 1 : 0;
              d.grupo_linea_id = $('#filtroFamilia').val();
              d.division_id = $('#filtroDivision').val();
              d.categoria_id = $('#filtroCategoria').val();
            },
            dataSrc: function(json) {
              if (!json.ok) {
                console.error('Error al cargar artículos:', json.msg);
                return [];
              }
              return json.items || [];
            }
          },
          columns: [
            {
              data: null,
              orderable: false,
              searchable: false,
              className: 'text-center',
              render: function(data, type, row) {
                if (row.ESTATUS === 'A') {
                  return `<input type="checkbox" class="chk-articulo" value="${row.ARTICULO_ID}">`;
                }
                return '';
              }
            },
            {
              data: 'ARTICULO_ID',
              className: 'text-center'
            },
            {
              data: 'CLAVE_ARTICULO',
              render: function(data, type, row) {
                if (data) {
                  return `<span class="editable-referencia" data-id="${row.ARTICULO_ID}" title="Click para editar referencia">${data}</span>`;
                } else {
                  return `<span class="sin-referencia" data-id="${row.ARTICULO_ID}" title="Click para añadir referencia">+ Añadir ref.</span>`;
                }
              }
            },
            {
              data: 'NOMBRE',
              render: function(data, type, row) {
                return `<span class="editable-nombre" data-id="${row.ARTICULO_ID}" title="Click para editar">${data || ''}</span>`;
              }
            },
            {
              data: 'CLAVE_SAT',
              render: function(data) {
                return data ? `<span class="badge badge-light border text-dark">${data}</span>` : '<em class="text-muted">N/A</em>';
              }
            },
            {
              data: 'FAMILIA',
              render: function(data) {
                return data || '<em class="text-muted">—</em>';
              }
            },
            {
              data: 'DIVISION',
              render: function(data) {
                return data || '<em class="text-muted">—</em>';
              }
            },
            {
              data: 'CATEGORIA',
              render: function(data) {
                return data || '<em class="text-muted">—</em>';
              }
            },
            {
              data: 'ESTATUS',
              className: 'text-center',
              render: function(data) {
                if (data === 'A') {
                  return '<span class="badge badge-activo">Activo</span>';
                } else {
                  return '<span class="badge badge-inactivo">Inactivo</span>';
                }
              }
            },
            {
              data: null,
              className: 'text-center',
              orderable: false,
              render: function(data, type, row) {
                const isActivo = row.ESTATUS === 'A';
                const btnClass = isActivo ? 'btn-danger' : 'btn-success';
                const btnIcon = isActivo ? 'mdi-close-circle' : 'mdi-check-circle';
                const btnText = isActivo ? 'Desactivar' : 'Activar';

                return `
              <button class="btn btn-sm ${btnClass} btn-toggle-status" 
                      style="white-space: nowrap;"
                      data-id="${row.ARTICULO_ID}" 
                      data-status="${row.ESTATUS}"
                      data-nombre="${row.NOMBRE}">
                <i class="mdi ${btnIcon}"></i> ${btnText}
              </button>
            `;
              }
            }
          ],
          createdRow: function(row, data, dataIndex) {
            $('td', row).eq(0).attr('data-label', 'Seleccionar');
            $('td', row).eq(1).attr('data-label', 'ID');
            $('td', row).eq(2).attr('data-label', 'Referencia');
            $('td', row).eq(3).attr('data-label', 'Nombre');
            $('td', row).eq(4).attr('data-label', 'Clave SAT');
            $('td', row).eq(5).attr('data-label', 'Familia');
            $('td', row).eq(6).attr('data-label', 'División');
            $('td', row).eq(7).attr('data-label', 'Categoría');
            $('td', row).eq(8).attr('data-label', 'Estado');
            $('td', row).eq(9).attr('data-label', 'Acciones');
          },
          order: [
            [1, 'desc']
          ],
          language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
          },
          pageLength: 25,
          lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "Todos"]
          ]
        });
      }

      // Alternar mostrar inactivos
      $('#btnMostrarInactivos').on('click', function() {
        mostrarInactivos = !mostrarInactivos;
        $(this).toggleClass('btn-outline-secondary btn-secondary');

        if (mostrarInactivos) {
          $(this).html('<i class="mdi mdi-eye-off"></i> Ocultar Inactivos');
        } else {
          $(this).html('<i class="mdi mdi-eye"></i> Mostrar Inactivos');
        }

        inicializarTabla();
      });

      // Función reutilizable para abrir el modal de edición
      function abrirModalEditar(id, filaData, enfocarCampo) {
        $('#edit_articulo_id').val(id);
        $('#edit_clave').val(filaData.CLAVE_ARTICULO || '');
        $('#edit_nombre').val(filaData.NOMBRE || '');

        // Configurar Familia
        if (filaData.GRUPO_LINEA_ID) {
          let optionFamilia = new Option(filaData.FAMILIA, filaData.GRUPO_LINEA_ID, true, true);
          $('#edit_familia').empty().append(optionFamilia).trigger('change');
        } else {
          $('#edit_familia').val(null).trigger('change');
        }

        // Configurar División
        if (filaData.DIVISION_ID) {
          let optionDivision = new Option(filaData.DIVISION, filaData.DIVISION_ID, true, true);
          $('#edit_division').empty().append(optionDivision).trigger('change');
        } else {
          $('#edit_division').val(null).trigger('change');
        }

        // Configurar Categoría
        if (filaData.LINEA_ARTICULO_ID) {
          let optionCategoria = new Option(filaData.CATEGORIA, filaData.LINEA_ARTICULO_ID, true, true);
          $('#edit_categoria').empty().append(optionCategoria).trigger('change');
        } else {
          $('#edit_categoria').val(null).trigger('change');
        }

        $('#editErrMsg').hide().text('');

        $('#modalEditarNombre').modal('show');

        // Enfocar el campo indicado al abrir
        if (enfocarCampo) {
          $('#modalEditarNombre').one('shown.bs.modal', function() {
            $(enfocarCampo).focus().select();
          });
        }
      }

      // Editar nombre (click en el nombre)
      $(document).on('click', '.editable-nombre', function() {
        const id = $(this).data('id');
        const fila = tabla.row($(this).closest('tr')).data();
        abrirModalEditar(id, fila, '#edit_nombre');
      });

      // Editar referencia (click en la referencia)
      $(document).on('click', '.editable-referencia, .sin-referencia', function() {
        const id = $(this).data('id');
        const fila = tabla.row($(this).closest('tr')).data();
        abrirModalEditar(id, fila, '#edit_clave');
      });

      // Guardar cambios de nombre
      $('#frmEditarNombre').on('submit', function(e) {
        e.preventDefault();
        const fd = $(this).serialize();
        $('#editErrMsg').hide().text('');

        $.post('../ajax/articulos.editar.php', fd, function(r) {
          try {
            r = (typeof r === 'string') ? JSON.parse(r) : r;
          } catch (e) {}

          if (!r.ok) {
            $('#editErrMsg').text(r.msg || 'Error al guardar').show();
            return;
          }

          $('#modalEditarNombre').modal('hide');
          tabla.ajax.reload(null, false);

          Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: 'Los datos se actualizaron correctamente.',
            timer: 2000,
            showConfirmButton: false
          });
        }).fail(function(jqXHR) {
          let errorMsg = 'Error en el servidor al intentar guardar.';
          if (jqXHR.responseJSON && jqXHR.responseJSON.msg) {
              errorMsg = jqXHR.responseJSON.msg;
          }
          $('#editErrMsg').text(errorMsg).show();
        });
      });

      // Cambiar estatus (activar/desactivar)
      $(document).on('click', '.btn-toggle-status', function() {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const nombre = $(this).data('nombre');
        const nuevoStatus = currentStatus === 'A' ? 'B' : 'A';
        const accion = nuevoStatus === 'B' ? 'desactivar' : 'activar';

        Swal.fire({
          title: '¿Estás seguro?',
          html: `¿Deseas <strong>${accion}</strong> el artículo:<br><em>"${nombre}"</em>?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: nuevoStatus === 'B' ? '#dc3545' : '#28a745',
          cancelButtonColor: '#6c757d',
          confirmButtonText: `Sí, ${accion}`,
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            $.post('../ajax/articulos.cambiarestatus.php', {
              articulo_id: id,
              estatus: nuevoStatus
            }, function(r) {
              try {
                r = (typeof r === 'string') ? JSON.parse(r) : r;
              } catch (e) {}

              if (!r.ok) {
                Swal.fire('Error', r.msg || 'No se pudo cambiar el estado', 'error');
                return;
              }

              tabla.ajax.reload(null, false);

              Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: `El artículo fue ${accion === 'desactivar' ? 'desactivado' : 'activado'} correctamente.`,
                timer: 2000,
                showConfirmButton: false
              });
            });
          }
        });
      });

      // ---- Lógica de Checkboxes Multi Selección con Paginación ----
      let selectedIds = new Set();

      function actualizarBotonMulti() {
        const count = selectedIds.size;
        $('#countSeleccionados').text(count);
        if (count > 0) {
          $('#btnDesactivarMulti').fadeIn(200);
        } else {
          $('#btnDesactivarMulti').fadeOut(200);
          $('#checkAll').prop('checked', false);
        }
      }

      $(document).on('change', '.chk-articulo', function() {
        const id = $(this).val();
        if ($(this).is(':checked')) {
          selectedIds.add(id);
        } else {
          selectedIds.delete(id);
        }
        actualizarBotonMulti();
        
        const totalRows = $('.chk-articulo').length;
        const totalChecked = $('.chk-articulo:checked').length;
        $('#checkAll').prop('checked', totalRows > 0 && totalRows === totalChecked);
      });

      $('#checkAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.chk-articulo').each(function() {
          $(this).prop('checked', isChecked);
          const id = $(this).val();
          if (isChecked) {
            selectedIds.add(id);
          } else {
            selectedIds.delete(id);
          }
        });
        actualizarBotonMulti();
      });

      // Restaurar estado visual de los checkboxes al cambiar de página
      $('#tablaArticulos').on('draw.dt', function() {
        let allCheckedOnPage = true;
        let hasRows = false;
        $('.chk-articulo').each(function() {
          hasRows = true;
          const id = $(this).val();
          if (selectedIds.has(id)) {
            $(this).prop('checked', true);
          } else {
            $(this).prop('checked', false);
            allCheckedOnPage = false;
          }
        });
        
        $('#checkAll').prop('checked', hasRows && allCheckedOnPage);
        actualizarBotonMulti();
      });

      $('#btnDesactivarMulti').on('click', function() {
        const ids = Array.from(selectedIds);
        if (ids.length === 0) return;

        Swal.fire({
          title: '¿Estás seguro?',
          text: `¿Deseas desactivar ${ids.length} artículos seleccionados?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Sí, desactivarlos',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            $.post('../ajax/articulos.desactivar_multi.php', { ids: ids }, function(r) {
              try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}

              if (!r.ok) {
                Swal.fire('Error', r.msg || 'No se pudo procesar la solicitud', 'error');
                return;
              }

              selectedIds.clear();
              tabla.ajax.reload(null, false);
              
              Swal.fire({
                icon: 'success',
                title: '¡Desactivados!',
                text: r.msg,
                timer: 2000,
                showConfirmButton: false
              });
            });
          }
        });
      });

      // ---- Debug y forzar cierre de modal ----
      $(document).on('click', '#modalEditarNombre .close', function(e) {
        console.log('Click en botón X (close) del modal editar');
        console.log('Elemento:', this);
        $('#modalEditarNombre').modal('hide');
      });

      $(document).on('click', '#modalEditarNombre button[data-dismiss="modal"]', function(e) {
        console.log('Click en botón Cancelar del modal editar');
        console.log('Elemento:', this);
        $('#modalEditarNombre').modal('hide');
      });

      // Debug: verificar si los botones existen después de abrir modal
      $('#modalEditarNombre').on('shown.bs.modal', function() {
        console.log('Modal editar abierto');
        console.log('Botones .close encontrados:', $('#modalEditarNombre .close').length);
        console.log('Botones cancelar encontrados:', $('#modalEditarNombre button[data-dismiss="modal"]').length);
      });

      // Debug: verificar click en cualquier botón del modal
      $(document).on('click', '#modalEditarNombre button', function(e) {
        console.log('Click en button del modal editar:', $(this).attr('class'), $(this).attr('type'), $(this).text().trim());
      });

      // Inicializar Select2 para filtros
      $('#filtroFamilia').select2({
        placeholder: 'Todas las familias',
        allowClear: true,
        ajax: {
          url: '../ajax/get.familias.php',
          dataType: 'json',
          delay: 250,
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return {
                  id: item.ID,
                  text: item.NOMBRE
                };
              })
            };
          },
          cache: true
        }
      }).on('change', function() {
        // Recargar divisiones y categorías al cambiar familia
        $('#filtroDivision').val(null).trigger('change');
        $('#filtroCategoria').val(null).trigger('change');
        tabla.ajax.reload();
      });

      $('#filtroDivision').select2({
        placeholder: 'Todas las divisiones',
        allowClear: true,
        ajax: {
          url: '../ajax/get.divisiones.by.familia.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              familiaid: $('#filtroFamilia').val()
            };
          },
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return {
                  id: item.ID,
                  text: item.NOMBRE
                };
              })
            };
          },
          cache: true
        }
      }).on('change', function() {
        $('#filtroCategoria').val(null).trigger('change');
        tabla.ajax.reload();
      });

      $('#filtroCategoria').select2({
        placeholder: 'Todas las categorías',
        allowClear: true,
        ajax: {
          url: '../ajax/get.lineaarticulos.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term,
              grupo_linea_id: $('#filtroFamilia').val(),
              division_id: $('#filtroDivision').val()
            };
          },
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return {
                  id: item.ID,
                  text: item.NOMBRE
                };
              })
            };
          },
          cache: true
        }
      }).on('change', function() {
        tabla.ajax.reload();
      });

      $('#btnLimpiarFiltros').on('click', function() {
        $('#filtroFamilia').val(null).trigger('change');
        $('#filtroDivision').val(null).trigger('change');
        $('#filtroCategoria').val(null).trigger('change');
      });

      // Inicializar Select2 para modal editar
      $('#edit_familia').select2({
        dropdownParent: $('#modalEditarNombre'),
        placeholder: 'Selecciona una familia',
        allowClear: true,
        ajax: {
          url: '../ajax/get.familias.php',
          dataType: 'json',
          delay: 250,
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return { id: item.ID, text: item.NOMBRE };
              })
            };
          },
          cache: true
        }
      }).on('change', function() {
        $('#edit_division').val(null).trigger('change');
        $('#edit_categoria').val(null).trigger('change');
      });

      $('#edit_division').select2({
        dropdownParent: $('#modalEditarNombre'),
        placeholder: 'Selecciona una división',
        allowClear: true,
        ajax: {
          url: '../ajax/get.divisiones.by.familia.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              familiaid: $('#edit_familia').val()
            };
          },
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return { id: item.ID, text: item.NOMBRE };
              })
            };
          },
          cache: true
        }
      }).on('change', function() {
        $('#edit_categoria').val(null).trigger('change');
      });

      $('#edit_categoria').select2({
        dropdownParent: $('#modalEditarNombre'),
        placeholder: 'Selecciona una categoría',
        allowClear: true,
        ajax: {
          url: '../ajax/get.lineaarticulos.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term,
              grupo_linea_id: $('#edit_familia').val(),
              division_id: $('#edit_division').val()
            };
          },
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return { id: item.ID, text: item.NOMBRE };
              })
            };
          },
          cache: true
        }
      });

      // --- LOGICA NUEVO ARTICULO ---
      $('#btnNuevoArticulo').on('click', function() {
        $('#frmNuevoArticulo')[0].reset();
        $('#nuevo_familia').val(null).trigger('change');
        $('#nuevo_division').val(null).trigger('change');
        $('#nuevo_categoria').val(null).trigger('change');
        $('#contenedorPreciosVenta').empty();
        $('#contenedorPreciosCompra').empty();
        $('#nuevoErrMsg').hide().text('');
        $('#modalNuevoArticulo').modal('show');
      });

      // Select2 Categorías para Nuevo
      $('#nuevo_familia').select2({
        dropdownParent: $('#modalNuevoArticulo'),
        placeholder: 'Selecciona una familia',
        allowClear: true,
        ajax: {
          url: '../ajax/get.familias.php',
          dataType: 'json',
          delay: 250,
          processResults: function(data) {
            return { results: data.map(function(item) { return { id: item.ID, text: item.NOMBRE }; }) };
          },
          cache: true
        }
      }).on('change', function() {
        $('#nuevo_division').val(null).trigger('change');
        $('#nuevo_categoria').val(null).trigger('change');
      });

      $('#nuevo_division').select2({
        dropdownParent: $('#modalNuevoArticulo'),
        placeholder: 'Selecciona una división',
        allowClear: true,
        ajax: {
          url: '../ajax/get.divisiones.by.familia.php',
          dataType: 'json',
          delay: 250,
          data: function(params) { return { familiaid: $('#nuevo_familia').val() }; },
          processResults: function(data) {
            return { results: data.map(function(item) { return { id: item.ID, text: item.NOMBRE }; }) };
          },
          cache: true
        }
      }).on('change', function() {
        $('#nuevo_categoria').val(null).trigger('change');
      });

      $('#nuevo_categoria').select2({
        dropdownParent: $('#modalNuevoArticulo'),
        placeholder: 'Selecciona una categoría',
        allowClear: true,
        ajax: {
          url: '../ajax/get.lineaarticulos.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term,
              grupo_linea_id: $('#nuevo_familia').val(),
              division_id: $('#nuevo_division').val()
            };
          },
          processResults: function(data) {
            return { results: data.map(function(item) { return { id: item.ID, text: item.NOMBRE }; }) };
          },
          cache: true
        }
      });

      // Get unidades de medida
      $.getJSON('../ajax/get.unidadmedida.php', function(data) {
        let options = '<option value=""></option>';
        if (data && data.length) {
          $.each(data, function(index, item) {
            options += '<option value="' + item.NOMBRE + '">' + item.NOMBRE + '</option>';
          });
        }
        $('#nuevo_unidad_venta').html(options);
      });

      // Sync unidad de compra
      $('#nuevo_unidad_venta').on('change', function() {
        $('#nuevo_unidad_compra').val($(this).val());
      });

      // Añadir fila de Cliente
      let cvIdx = 0;
      $('#btnAddPrecioVenta').on('click', function() {
        cvIdx++;
        let html = `
          <div class="row align-items-end mb-2 fila-pv" id="fila-pv-${cvIdx}">
            <div class="col-md-6">
                <label class="form-label" style="font-size: 0.85rem;">Cliente Especial</label>
                <select name="clientes[${cvIdx}][id]" class="form-control select2-clientes" style="width:100%"></select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size: 0.85rem;">Precio (Subtotal)</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                    <input type="number" step="0.01" class="form-control" name="clientes[${cvIdx}][precio]" placeholder="0.00" required>
                </div>
            </div>
            <div class="col-md-2 text-right">
                <button type="button" class="btn btn-sm btn-danger btn-eliminar-fila"><i class="mdi mdi-delete"></i></button>
            </div>
          </div>
        `;
        $('#contenedorPreciosVenta').append(html);
        
        $(`#fila-pv-${cvIdx} .select2-clientes`).select2({
          dropdownParent: $('#modalNuevoArticulo'),
          placeholder: 'Buscar cliente...',
          ajax: {
            url: '../ajax/precios.buscar_clientes.php',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term || '*' }; },
            processResults: function(data) {
              let res = data.items ? data.items.map(function(i){ return {id: i.CLIENTE_ID, text: i.NOMBRE}; }) : [];
              return { results: res };
            }
          }
        });
      });

      // Añadir fila de Proveedor
      let cpIdx = 0;
      $('#btnAddPrecioCompra').on('click', function() {
        cpIdx++;
        let html = `
          <div class="row align-items-end mb-2 fila-pc" id="fila-pc-${cpIdx}">
            <div class="col-md-6">
                <label class="form-label" style="font-size: 0.85rem;">Proveedor</label>
                <select name="proveedores[${cpIdx}][id]" class="form-control select2-proveedores" style="width:100%"></select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size: 0.85rem;">Costo (Subtotal)</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                    <input type="number" step="0.01" class="form-control" name="proveedores[${cpIdx}][precio]" placeholder="0.00" required>
                </div>
            </div>
            <div class="col-md-2 text-right">
                <button type="button" class="btn btn-sm btn-danger btn-eliminar-fila"><i class="mdi mdi-delete"></i></button>
            </div>
          </div>
        `;
        $('#contenedorPreciosCompra').append(html);
        
        $(`#fila-pc-${cpIdx} .select2-proveedores`).select2({
          dropdownParent: $('#modalNuevoArticulo'),
          placeholder: 'Buscar proveedor...',
          ajax: {
            url: '../ajax/preciosc.buscar_proveedores.php',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term || '*' }; },
            processResults: function(data) {
              let res = data.items ? data.items.map(function(i){ return {id: i.PROVEEDOR_ID, text: i.NOMBRE}; }) : [];
              return { results: res };
            }
          }
        });
      });

      $(document).on('click', '.btn-eliminar-fila', function() {
          $(this).closest('.row').remove();
      });

      // Enviar Formulario Nuevo
      $('#frmNuevoArticulo').on('submit', function(e) {
        e.preventDefault();
        const fd = $(this).serialize();
        $('#nuevoErrMsg').hide().text('');

        $.post('../ajax/articulos.nuevo.php', fd, function(r) {
          try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
          if (!r.ok) {
            $('#nuevoErrMsg').text(r.msg || 'Error al guardar').show();
            return;
          }
          $('#modalNuevoArticulo').modal('hide');
          tabla.ajax.reload(null, false);
          Swal.fire({
            icon: 'success',
            title: '¡Registrado!',
            text: 'El nuevo artículo ha sido creado exitosamente.',
            timer: 2000,
            showConfirmButton: false
          });
        }).fail(function(jqXHR) {
          let errorMsg = 'Error en el servidor.';
          if (jqXHR.responseJSON && jqXHR.responseJSON.msg) errorMsg = jqXHR.responseJSON.msg;
          $('#nuevoErrMsg').text(errorMsg).show();
        });
      });

      // Inicializar
      console.log('Inicializando articulos_listado.php');
      inicializarTabla();
    })();
  </script>
</body>

</html>