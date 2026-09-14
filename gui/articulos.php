<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include_once("../includes/head.php");?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
  <style>
    /* Asegura que la caja de Select2 tenga la misma altura que un input Bootstrap */
    .select2-container--default .select2-selection--single {
      height: calc(2.25rem + 2px);  /* = altura de .form-control estándar */
      padding: 0.375rem 0.75rem;    /* mismo padding */
      font-size: 1rem;
      line-height: 1.5;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
    }

    /* Ajusta el texto centrado verticalmente */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 1.5rem;          /* centra el texto */
      padding-left: 0;              /* quita offset raro */
      padding-right: 0;
    }

    /* Ajusta la flechita para que no quede descolocada */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 100%;
      top: 0;
      right: 0.75rem;
    }

    .price-badge-base{ background:#0d6efd; }
    .price-badge-cliente{ background:#6c757d; }
    .pointer{ cursor:pointer; }
  </style>
</head>
<body>
<div class="container-scroller">
  <?php include_once("../includes/header.php"); ?>
  <div class="container-fluid page-body-wrapper">
    <?php include_once ("../includes/menu.sidebar.php")?>
    <div class="main-panel">
      <div class="content-wrapper">
        <div class="row">
          <div class="col-sm-12">
            <div class="home-tab">
              <?php include_once("../includes/articulos.menu.php");?>
            </div>
            <div class="tab-content tab-content-basic">
              <!-- Inicia contenido principal -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h4 class="mb-0">Precios por Artículo</h4>
                      <button id="btnNuevoPrecio" class="btn btn-primary btn-sm" disabled
                              data-toggle="modal" data-target="#modalPrecio">
                        + Nuevo precio
                      </button>
                    </div>
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-8">
                          <label class="form-label">Buscar artículo (por clave o nombre)</label>
                          <select id="selArticulo" class="form-control" style="width:100%"></select>
                          <div class="form-text">Escribe parte de la clave o del nombre.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                          <div id="artInfo" class="w-100 small text-muted"></div>
                        </div>
                      </div>

                      <div class="d-flex flex-wrap gap-2 my-2 align-items-center">
                        <a href="../ajax/precios.catalogo_exportar_simple.php" class="btn btn-primary btn-sm mb-0">
                          Exportar catálogo de precios de venta (.xlsx)
                        </a>
                        <label class="btn btn-success btn-sm mb-0">
                          Importar catálogo (.xlsx)
                          <input id="fileExcelCatalogoSimple" type="file" accept=".xlsx" hidden>
                        </label>
                      </div>
                      <div id="importMsgCatalogo" class="small"></div>

                      <hr>

                      <div id="preciosWrap" style="display:none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="mb-0">Lista de precios</h5>
                          <div>
                            <span class="badge price-badge-base">Precio base (Cliente = NULL)</span>
                            <span class="badge price-badge-cliente">Precio por cliente</span>
                          </div>
                        </div>

                        <div class="table-responsive">
                          <table class="table table-sm table-striped align-middle">
                            <thead>
                              <tr>
                                <th style="width:22%">Cliente</th>
                                <th style="width:16%" class="text-end">Subtotal</th>
                                <th style="width:16%" class="text-end">IVA</th>
                                <th style="width:16%" class="text-end">Total</th>
                                <th style="width:16%" class="text-center">Tipo</th>
                                <th style="width:14%" class="text-center">Acciones</th>
                              </tr>
                            </thead>
                            <tbody id="tbodyPrecios"></tbody>
                          </table>
                        </div>
                      </div>

                      <div id="noData" class="alert alert-info mt-3" style="display:none">
                        Selecciona un artículo para ver/editar sus precios.
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
      <?php include_once("../includes/footer.php");?>
    </div>
  </div>
</div>

<?php include_once("../includes/foot.php");?>

<!-- Modal Agregar/Editar Precio -->
<div class="modal fade" id="modalPrecio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="frmPrecio">
        <div class="modal-header">
          <h5 class="modal-title">Precio</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="artprecio_id" name="artprecio_id">
          <input type="hidden" id="articulo_id" name="articulo_id">

          <div class="mb-3">
            <label class="form-label">Cliente / Hospital (dejar vacio para precio base)</label>
            <select id="cliente_id" name="cliente_id" class="form-control" style="width:100%"></select>
            <div class="form-text">Si lo dejas vacío, se guardará como <strong>precio base (NULL)</strong>.</div>
          </div>

          <div class="row g-2">
            <div class="col-4">
              <label class="form-label">Subtotal</label>
              <input type="number" step="0.01" min="0" class="form-control" id="subtotal" name="subtotal" required>
            </div>
            <div class="col-4">
              <label class="form-label">IVA</label>
              <input type="number" step="0.01" min="0" class="form-control" id="iva" name="iva" required>
              <div class="form-text"><a href="#" id="calcIva">Calcular IVA 16%</a></div>
            </div>
            <div class="col-4">
              <label class="form-label">Total</label>
              <input type="number" step="0.01" min="0" class="form-control" id="total" name="total" required>
              <div class="form-text"><a href="#" id="calcTotal">Subtotal + IVA</a></div>
            </div>
          </div>

          <div id="errMsg" class="text-danger small mt-2" style="display:none"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const $selArticulo = $('#selArticulo');
  const $btnNuevo = $('#btnNuevoPrecio');
  const $tbody = $('#tbodyPrecios');
  const $wrap = $('#preciosWrap');
  const $noData = $('#noData');
  const $artInfo = $('#artInfo');

  let currentArticulo = null; // {id, clave, nombre}

  // ---- Select2: Artículos (busca por clave/nombre) ----
  $selArticulo.select2({
    placeholder: 'Buscar artículo...',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
      url: '../ajax/precios.buscar_articulos.php',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({
        results: (data.items||[]).map(it => ({
          id: it.ARTICULO_ID,
          text: it.ARTICULO_CLAVE + ' — ' + it.ARTICULO_NOMBRE,
          raw: it
        }))
      }),
      cache: true
    }
  }).on('select2:select', function(e){
    const raw = e.params.data.raw;
    currentArticulo = {
      id: raw.ARTICULO_ID,
      clave: raw.ARTICULO_CLAVE,
      nombre: raw.ARTICULO_NOMBRE
    };
    $('#articulo_id').val(currentArticulo.id);
    $artInfo.html(`<strong>${currentArticulo.clave}</strong> — ${currentArticulo.nombre}`);
    $btnNuevo.prop('disabled', false);
    cargarPrecios();
  }).on('select2:clear', function(){
    currentArticulo = null;
    $('#articulo_id').val('');
    $tbody.empty();
    $wrap.hide(); $noData.show();
    $artInfo.empty();
    $btnNuevo.prop('disabled', true);
  });

  // ---- Clientes en el modal (Select2) ----
  $('#cliente_id').select2({
    dropdownParent: $('#modalPrecio'),
    placeholder: 'Selecciona un hospital/lugar (opcional)',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
      url: '../ajax/precios.buscar_hospitales.php',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({
        results: (data.items||[]).map(it => ({ id: it.CLIENTE_ID, text: it.NOMBRE }))
      }),
      cache: true
    }
  });

  // ---- Cargar precios del artículo seleccionado ----
  function cargarPrecios(){
    if(!currentArticulo) return;
    $.getJSON('../ajax/precios.obtener.php', { articulo_id: currentArticulo.id }, function(r){
      $tbody.empty();
      if(!r.ok){ $wrap.hide(); $noData.show(); return; }

      // Primero base (cliente NULL), luego clientes
      const base = r.items.filter(x => x.CLIENTE_ID === null);
      const otros = r.items.filter(x => x.CLIENTE_ID !== null);

      const fila = (p) => {
        const tipo = (p.CLIENTE_ID===null)
          ? '<span class="badge price-badge-base">Base</span>'
          : '<span class="badge price-badge-cliente">Cliente</span>';

        const clienteTxt = (p.CLIENTE_ID===null) ? '<em>(NULL)</em>' : (p.CLIENTE_NOMBRE||p.CLIENTE_ID);

        return `
          <tr>
            <td>${clienteTxt}</td>
            <td class="text-end">$${Number(p.SUBTOTAL).toFixed(2)}</td>
            <td class="text-end">$${Number(p.IVA).toFixed(2)}</td>
            <td class="text-end">$${Number(p.TOTAL).toFixed(2)}</td>
            <td class="text-center">${tipo}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-warning me-1 btn-edit" data-id="${p.ARTPRECIO_ID}">Editar</button>
              <button class="btn btn-sm btn-danger btn-del" data-id="${p.ARTPRECIO_ID}">Eliminar</button>
            </td>
          </tr>`;
      };

      base.forEach(p => $tbody.append(fila(p)));
      otros.forEach(p => $tbody.append(fila(p)));

      $noData.hide();
      $wrap.show();
    });
  }

  // ---- Nuevo precio (limpia modal) ----
  $('#btnNuevoPrecio').on('click', function(){
    if(!currentArticulo){ return; }
    $('#frmPrecio')[0].reset();
    $('#artprecio_id').val('');
    $('#articulo_id').val(currentArticulo.id);
    $('#cliente_id').val(null).trigger('change');
    $('#errMsg').hide().text('');
    $('.modal-title').text('Nuevo precio');
  });

  // ---- Editar precio (carga datos en modal) ----
  $(document).on('click', '.btn-edit', function(){
    const id = $(this).data('id');
    $.getJSON('../ajax/precios.obtener.php', { artprecio_id: id }, function(r){
      if(!r.ok || !r.item){ alert(r.msg||'No se pudo cargar.'); return; }
      const p = r.item;
      $('#artprecio_id').val(p.ARTPRECIO_ID);
      $('#articulo_id').val(p.ARTICULO_ID);
      // Cliente (puede ser null)
      if(p.CLIENTE_ID){
        const opt = new Option(p.CLIENTE_NOMBRE || ('ID '+p.CLIENTE_ID), p.CLIENTE_ID, true, true);
        $('#cliente_id').append(opt).trigger('change');
      } else {
        $('#cliente_id').val(null).trigger('change');
      }
      $('#subtotal').val(p.SUBTOTAL);
      $('#iva').val(p.IVA);
      $('#total').val(p.TOTAL);
      $('#errMsg').hide().text('');
      $('.modal-title').text('Editar precio');
      $('#modalPrecio').modal('show');
    });
  });

  // ---- Eliminar precio ----
  $(document).on('click', '.btn-del', function(){
    const id = $(this).data('id');
    
    Swal.fire({
      title: '¿Eliminar este precio?',
      text: "Esta acción no se puede deshacer.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('../ajax/precios.eliminar.php', { artprecio_id: id }, function(r){
          try{ r = (typeof r==='string') ? JSON.parse(r) : r; }catch(e){}
          if(!r.ok){ 
            Swal.fire('Error', r.msg||'No se pudo eliminar', 'error');
            return; 
          }
          
          Swal.fire({
            icon: 'success',
            title: '¡Eliminado!',
            text: 'El precio ha sido borrado.',
            timer: 1500,
            showConfirmButton: false
          });
          
          cargarPrecios();
        });
      }
    });
  });

  // ---- Guardar (crear/editar) ----
  $('#frmPrecio').on('submit', function(e){
    e.preventDefault();
    const fd = $(this).serialize();
    $('#errMsg').hide().text('');
    $.post('../ajax/precios.guardar.php', fd, function(r){
      try{ r = (typeof r==='string') ? JSON.parse(r) : r; }catch(e){}
      if(!r.ok){
        $('#errMsg').text(r.msg||'Error al guardar').show();
        return;
      }
      
      // Cerrar modal asegurando remover el backdrop
      $('#modalPrecio').removeClass("in");
      $(".modal-backdrop").remove();
      $('body').removeClass('modal-open');
      $('body').css('padding-right', '');
      $('#modalPrecio').hide();
      
      cargarPrecios();
      
      Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: 'El precio se guardó correctamente.',
        timer: 1500,
        showConfirmButton: false
      });
    });
  });

  // ---- Helpers IVA/Total ----
  $('#calcIva').on('click', function(e){
    e.preventDefault();
    const sub = parseFloat($('#subtotal').val()||'0');
    $('#iva').val((sub*0.16).toFixed(2));
  });
  $('#calcTotal').on('click', function(e){
    e.preventDefault();
    const sub = parseFloat($('#subtotal').val()||'0');
    const iva = parseFloat($('#iva').val()||'0');
    $('#total').val((sub+iva).toFixed(2));
  });

  $('#fileExcelCatalogoSimple').on('change', function(){
    const f = this.files[0];
    if(!f){ return; }
    const fd = new FormData();
    fd.append('file', f);

    $('#importMsgCatalogo').removeClass('text-success text-danger text-warning')
                          .addClass('text-muted')
                          .text('Procesando archivo simple...');

    $.ajax({
      url: '../ajax/precios.catalogo_importar_simple.php',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success: function(r){
        try{ r = (typeof r==='string') ? JSON.parse(r) : r; }catch(e){}
        
        let res = r;
        if(r && r.resultado) { res = r.resultado; } // si viene envuelto
        
        if(!res || (res.creados === undefined && res.actualizados === undefined)){
          $('#importMsgCatalogo').removeClass('text-muted text-success text-warning').addClass('text-danger')
                                .text('Error al importar o respuesta inválida.');
          return;
        }
        
        let detalle = [];
        if(res.creados) detalle.push(`creados: ${res.creados}`);
        if(res.actualizados) detalle.push(`actualizados: ${res.actualizados}`);
        
        let txtClass = 'text-success';
        if((res.errores||[]).length) {
            detalle.push(`con ${res.errores.length} advertencias`);
            txtClass = 'text-warning';
        }
        
        $('#importMsgCatalogo').removeClass('text-muted text-danger text-success text-warning').addClass(txtClass)
                              .html(`Importación lista (${detalle.join(', ')}).`);

        if (typeof cargarPrecios === 'function') { cargarPrecios(); }
        $('#fileExcelCatalogoSimple').val('');
        
        if ((res.errores||[]).length) {
            console.warn('Errores por fila:', res.errores);
            alert("Hubo advertencias al importar:\\n" + res.errores.join("\\n"));
        }
      },
      error: function(){
        $('#importMsgCatalogo').removeClass('text-muted text-success').addClass('text-danger')
                              .text('Error de red al importar.');
      }
    });
  });

  // ---- Forzar cierre de modal con botones ----
  $(document).on('click', '#modalPrecio .close', function(e) {
    console.log('Click en botón X (close)');
    console.log('Elemento:', this);
    console.log('Event:', e);
    $('#modalPrecio').modal('hide');
  });
  
  $(document).on('click', '#modalPrecio .btn-light[data-dismiss="modal"]', function(e) {
    console.log('Click en botón Cancelar');
    console.log('Elemento:', this);
    console.log('Event:', e);
    $('#modalPrecio').modal('hide');
  });
  
  // Debug: verificar si los botones existen después de abrir modal
  $('#modalPrecio').on('shown.bs.modal', function() {
    console.log('Modal abierto');
    console.log('Botones .close encontrados:', $('#modalPrecio .close').length);
    console.log('Botones cancelar encontrados:', $('#modalPrecio .btn-light[data-dismiss="modal"]').length);
    console.log('HTML del header:', $('#modalPrecio .modal-header').html());
  });
  
  // Debug: verificar click en cualquier parte del modal
  $(document).on('click', '#modalPrecio button', function(e) {
    console.log('Click en algún button dentro del modal:', $(this).attr('class'), $(this).text());
  });

  // Estado inicial
  $noData.show();
})();
</script>
</body>
</html>
