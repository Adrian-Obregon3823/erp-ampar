(function() {
  var config = window.invDashboardConfig || {};
  var initialPayload = config.initialPayload || {};
  var deferInitialLoad = config.deferInitialLoad === true;
  var dataEndpointCandidates = Array.isArray(config.dataEndpointCandidates) && config.dataEndpointCandidates.length
    ? config.dataEndpointCandidates
    : ['../ajax/dashboard.inventario.data.php', 'ajax/dashboard.inventario.data.php', '/ajax/dashboard.inventario.data.php'];

  var etapas = ['6m', '3m', '2m', '1s', 'caducado'];
  var etapaLabels = {
    '6m': '6 meses',
    '3m': '3 meses',
    '2m': '2 meses',
    '1s': '1 semana',
    'caducado': 'Caducado'
  };
  var monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  var donutChart = null;
  var trendChart = null;
  var requestSerial = 0;
  var activeController = null;
  var filterForm = document.getElementById('invFilterForm');
  var selectAlmacen = document.getElementById('almacen_id');
  var selectPeriodo = document.getElementById('periodo');
  var ajaxStatus = document.getElementById('invAjaxStatus');
  var pageLoading = document.getElementById('loading');
  var printButton = document.getElementById('invPrintBtn');
  var exportButton = document.getElementById('invExportBtn');
  var currentPayload = initialPayload;
  var printPanelState = {};
  var firstDataLoadCompleted = false;

  var nf0 = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 });
  var nf1 = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  var nf2 = new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  function toNumber(value) {
    var parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function titleCase(value) {
    var text = String(value == null ? '' : value).trim().toLowerCase();
    return text.replace(/\b([a-zA-Z\u00c0-\u017f])/g, function(char) {
      return char.toUpperCase();
    });
  }

  function getDaysClass(days, useNearRange) {
    if (useNearRange) {
      if (days <= 7) {
        return 'days-danger';
      }
      if (days <= 30) {
        return 'days-warn';
      }
      return 'days-ok';
    }

    if (days <= 0) {
      return 'days-danger';
    }
    if (days <= 7) {
      return 'days-warn';
    }
    return 'days-ok';
  }

  function buildInventoryRow(item) {
    var nombreOriginal = item.NOMBRE || '';
    var nombre = titleCase(nombreOriginal);
    var referencia = item.CLAVE_ARTICULO || '';
    var folio = item.FOLIO || '';
    var lote = item.LOTE || '';
    var almacen = item.ALMACEN_NOMBRE || '';
    var caducidad = item.CADUCIDAD || '';
    var dias = parseInt(item.DIAS_RESTANTES || 0, 10);
    var daysClass = getDaysClass(dias, false);
    var almacenTipo = parseInt(item.ALMACEN_TIPOALMACEN || 0, 10);
    var stockId = parseInt(item.STOCK_ID || 0, 10);
    var metaParts = [];
    var actionBtn = '';

    if (lote) {
      metaParts.push('<span>Lote: ' + escapeHtml(lote) + '</span>');
    }
    if (folio) {
      metaParts.push('<span>Folio: ' + escapeHtml(folio) + '</span>');
    }
    if (almacen) {
      metaParts.push('<span>Almacen: ' + escapeHtml(almacen) + '</span>');
    }

    return [
      '<div class="inv-data-row">',
        '<div class="inv-data-left">',
          '<div class="inv-data-title" title="' + escapeHtml(nombreOriginal) + '">',
            escapeHtml(nombre),
            '<small>Ref: ' + escapeHtml(referencia) + '</small>',
          '</div>',
          '<div class="inv-data-meta">' + metaParts.join('') + '</div>',
          actionBtn,
        '</div>',
        '<div class="inv-data-right">',
          '<div class="inv-data-stack">',
            '<div class="inv-data-label">Caducidad</div>',
            '<div class="inv-data-value">' + escapeHtml(caducidad) + '</div>',
          '</div>',
          '<div class="inv-data-stack">',
            '<div class="inv-data-label">Dias</div>',
            '<div class="inv-data-value"><span class="days-badge ' + daysClass + '">' + escapeHtml(dias) + '</span></div>',
          '</div>',
        '</div>',
      '</div>'
    ].join('');
  }

  function buildNestedInventoryHtml(items, isGlobal) {
    var rawHtml = [];
    if (!items || items.length === 0) return [];
    
    var tree = {};
    items.forEach(function(item) {
      var tipo = parseInt(item.ALMACEN_TIPOALMACEN || 0, 10);
      var parentName = item.ALMACEN_NOMBRE || 'Desconocido';
      var isMaleta = (tipo === 3);
      var maletaName = isMaleta ? parentName : null;
      
      if (isMaleta) {
        parentName = item.PADRE_ALMACEN_NOMBRE || 'Almacen (Sin Asignar)';
      }
      
      if (!tree[parentName]) {
        tree[parentName] = { articulos: [], maletas: {} };
      }
      
      if (isMaleta) {
        if (!tree[parentName].maletas[maletaName]) {
          tree[parentName].maletas[maletaName] = [];
        }
        tree[parentName].maletas[maletaName].push(item);
      } else {
        tree[parentName].articulos.push(item);
      }
    });

    var keys = Object.keys(tree).sort();
    
    keys.forEach(function(almacenName) {
      var node = tree[almacenName];
      var totalAlmacen = node.articulos.length;
      var maletaKeys = Object.keys(node.maletas).sort();
      maletaKeys.forEach(function(mk) { totalAlmacen += node.maletas[mk].length; });
      
      if (isGlobal || keys.length > 1) {
         rawHtml.push('<div class="inv-data-group-almacen mb-2">');
         rawHtml.push('<div class="inv-data-row" style="cursor:pointer;" onclick="var b=this.nextElementSibling; b.style.display=(b.style.display===\'none\'?\'grid\':\'none\');">');
         rawHtml.push('  <div class="inv-data-left">');
         rawHtml.push('    <div class="inv-data-title">Almacen ' + escapeHtml(almacenName) + '</div>');
         rawHtml.push('    <div class="inv-data-meta"><span>Clic para expandir / contraer</span></div>');
         rawHtml.push('  </div>');
         rawHtml.push('  <div class="inv-data-right">');
         rawHtml.push('    <div class="inv-data-stack"><div class="inv-data-label">Total Art.</div><div class="inv-data-value"><span class="days-badge days-ok">' + totalAlmacen + '</span></div></div>');
         rawHtml.push('  </div>');
         rawHtml.push('</div>');
         rawHtml.push('<div class="inv-data-group-body" style="display:none;">');
      } else {
         rawHtml.push('<div class="inv-data-group-body" style="display:grid; gap:10px;">');
      }
      
      node.articulos.forEach(function(item) {
         rawHtml.push(buildInventoryRow(item));
      });
      
      maletaKeys.forEach(function(maletaName) {
         var malArticulos = node.maletas[maletaName];
         rawHtml.push('<div class="inv-data-group-maleta mt-2">');
         rawHtml.push('<div class="inv-data-row" style="cursor:pointer; border-color:#cbd5e1; background:#f8fafc;" onclick="var b=this.nextElementSibling; b.style.display=(b.style.display===\'none\'?\'grid\':\'none\');">');
         rawHtml.push('  <div class="inv-data-left">');
         rawHtml.push('    <div class="inv-data-title" style="color:#1e40af;">Maleta ' + escapeHtml(maletaName) + '</div>');
         rawHtml.push('    <div class="inv-data-meta"><span>Clic para expandir / contraer</span></div>');
         rawHtml.push('  </div>');
         rawHtml.push('  <div class="inv-data-right">');
         rawHtml.push('    <div class="inv-data-stack"><div class="inv-data-label">Total Art.</div><div class="inv-data-value"><span class="days-badge" style="background:#e0e7ff; color:#312e81;">' + malArticulos.length + '</span></div></div>');
         rawHtml.push('  </div>');
         rawHtml.push('</div>');
         rawHtml.push('<div class="inv-data-group-body-maleta" style="display:none;">');
         
         malArticulos.forEach(function(item) {
             rawHtml.push(buildInventoryRow(item));
         });
         
         rawHtml.push('</div>');
         rawHtml.push('</div>');
      });
      
      rawHtml.push('</div>');
      if (isGlobal || keys.length > 1) {
          rawHtml.push('</div>');
      }
    });

    return rawHtml;
  }

  function buildTopCaducidadRow(item) {
    var nombreOriginal = item.ARTICULO_NOMBRE || '';
    var nombre = titleCase(nombreOriginal);
    var referencia = item.SKU || '';
    var cantidad = toNumber(item.CANTIDAD || 0);
    var almacen = item.ALMACEN_NOMBRE || '';
    var dias = parseInt(item.DIAS_RESTANTES || 0, 10);
    var daysClass = getDaysClass(dias, true);
    
    var stockIdsAllRaw = item.STOCK_IDS || '';
    var stockIdsAllArr = stockIdsAllRaw ? stockIdsAllRaw.split(',') : [];
    var stockIdsRaw = item.STOCK_IDS_ACCIONABLES || '';
    var stockIdsArr = stockIdsRaw ? stockIdsRaw.split(',') : [];
    var puedeAccion = Boolean(item.PUEDE_ACCION) && stockIdsArr.length > 0;

    var stockIdsAccionablesMap = {};
    stockIdsArr.forEach(function(s) {
      var parts = s.split('|');
      var sId = parseInt(parts[0], 10);
      if (sId > 0) {
        stockIdsAccionablesMap[sId] = true;
      }
    });

    var cleanStockIds = [];
    var stockItems = [];
    var actionableStockIds = [];

    stockIdsAllArr.forEach(function(s) {
       var parts = s.split('|');
       var sId = parseInt(parts[0], 10);
       var alName = parts[1] || 'Almacén Principal';
       var folio = parts[2] || 'S/F';
       var lote = parts[3] || 'S/L';
       if (sId > 0) {
           cleanStockIds.push(sId);
           stockItems.push({ id: sId, almacen: alName, folio: folio, lote: lote, puedeAccion: !!stockIdsAccionablesMap[sId] });
          if (stockIdsAccionablesMap[sId]) {
           actionableStockIds.push(sId);
          }
       }
    });
    
     var bulkIdsStr = actionableStockIds.join(',');
    var debeExpandir = stockIdsAllArr.length > 1;

    var actionBtnAll = '';
      if (puedeAccion && actionableStockIds.length > 0) {
      if (actionableStockIds.length === 1) {
        var jsFn = 'window.iniciarAccionInteligente(' + actionableStockIds[0] + ', this)';
         actionBtnAll = '<div style="margin-top:8px;"><button class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:11px; font-weight:bold;" onclick="' + jsFn + '; event.stopPropagation();">&#9889; Iniciar Acción</button></div>';
      } else {
         var jsFn = 'window.iniciarAccionInteligenteBulk(\'' + bulkIdsStr + '\', this)';
         actionBtnAll = '<div style="margin-top:8px;"><button class="btn btn-sm btn-primary py-1 px-2" style="font-size:11px; font-weight:bold; box-shadow: 0 2px 4px rgba(0,0,0,0.15); color: #ffffff !important;" onclick="' + jsFn + '; event.stopPropagation();">&#9889; Iniciar Acción</button></div>';
      }
    }

    if (!debeExpandir) {
       return [
         '<div class="inv-data-row" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e1e7f0; margin-bottom: 10px;">',
           '<div class="inv-data-left">',
             '<div class="inv-data-title" title="' + escapeHtml(nombreOriginal) + '">',
               escapeHtml(nombre),
               '<small>Ref: ' + escapeHtml(referencia) + '</small>',
             '</div>',
             (almacen ? '<div class="inv-data-meta"><span>Almacen: ' + escapeHtml(almacen) + '</span></div>' : ''),
             actionBtnAll,
           '</div>',
           '<div class="inv-data-right">',
             '<div class="inv-data-stack" style="justify-content: center; margin-right: 15px;">',
               '<span style="font-size: 11px; height: 22px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; border-radius: 12px; font-weight: bold; line-height: 1; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-sizing: border-box;">' + escapeHtml(nf0.format(cantidad)) + ' Unidad</span>',
             '</div>',
             '<div class="inv-data-stack" style="justify-content: center;">',
               '<span class="days-badge ' + daysClass + '" style="font-size: 11px; height: 22px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; border-radius: 12px; font-weight: bold; line-height: 1; box-sizing: border-box;">' + escapeHtml(dias) + ' DÍAS</span>',
             '</div>',
           '</div>',
         '</div>'
       ].join('');
    }

    var groupHtml = [
      '<div class="inv-accordion-group" style="margin-bottom: 15px;" onclick="this.classList.toggle(\'active\'); var body = this.querySelector(\'.inv-accordion-body\'); if (body) { body.style.display = body.style.display === \'none\' ? \'block\' : \'none\'; }">',
        '<div class="inv-data-row" style="cursor: pointer; border: 1px solid #cbd5e1; box-shadow: 0 3px 6px rgba(0,0,0,0.06); margin-bottom: 0; background: #ffffff;">',
          '<div class="inv-data-left">',
            '<div class="inv-data-title" title="' + escapeHtml(nombreOriginal) + '">',
              escapeHtml(nombre),
              '<small>Ref: ' + escapeHtml(referencia) + '</small>',
            '</div>',
            (almacen ? '<div class="inv-data-meta"><span>Almacen: ' + escapeHtml(almacen) + '</span></div>' : ''),
            actionBtnAll,
          '</div>',
          '<div class="inv-data-right">',
            '<div class="inv-data-stack" style="justify-content: center; margin-right: 15px;">',
              '<span style="font-size: 11px; height: 22px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; border-radius: 12px; font-weight: bold; line-height: 1; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-sizing: border-box;">' + escapeHtml(nf0.format(cantidad)) + ' Unidades</span>',
            '</div>',
            '<div class="inv-data-stack" style="justify-content: center;">',
              '<span class="days-badge ' + daysClass + '" style="font-size: 11px; height: 22px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; border-radius: 12px; font-weight: bold; line-height: 1; box-sizing: border-box;">' + escapeHtml(dias) + ' DÍAS</span>',
            '</div>',
          '</div>',
        '</div>',
        '<div class="inv-accordion-body" style="display:none; margin-left: 36px; border-left: 2px solid #cbd5e1; padding-left: 26px; margin-top: 10px; padding-bottom: 2px;">'
    ];

    stockItems.forEach(function(s) {
      if (!s.id) return;
      var actionBtnIndiv = '';
      if (s.puedeAccion) {
        actionBtnIndiv = '<button class="btn py-0 px-2" style="font-size:11.5px; font-weight:bold; background: #f1f5f9; border: none; text-decoration: none !important; color: #1d4ed8; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0 16px; border-radius: 20px; outline: none; height: 28px; box-sizing: border-box;" onmouseover="this.style.backgroundColor=\'#e2e8f0\'; this.style.color=\'#1e3a8a\';" onmouseout="this.style.backgroundColor=\'#f1f5f9\'; this.style.color=\'#1d4ed8\';" onclick="window.iniciarAccionInteligente(' + s.id + ', this); event.stopPropagation();"><span style="font-size: 13px; display: flex; align-items: center; margin-top: -1px; margin-left: 2px;">&#9889;</span> <span>Acción Indiv.</span></button>';
      }
      
      groupHtml.push(
        '<div style="margin-bottom: 8px; padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; box-shadow: 0 1px 2px rgba(0,0,0,0.02); cursor: default; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#bae6fd\'; this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.06)\';" onmouseout="this.style.borderColor=\'#e2e8f0\'; this.style.boxShadow=\'0 1px 2px rgba(0,0,0,0.02)\';" onclick="event.stopPropagation();">',
          '<div style="display: flex; flex-direction: column; justify-content: center;">',
            '<span style="font-size: 13.5px; color: #1e293b; font-weight: bold; margin-bottom: 2px;">' + escapeHtml(s.almacen) + '</span>',
            '<span style="font-size: 11px; color: #94a3b8;">Lote: <span style="color: #64748b;">' + escapeHtml(s.lote) + '</span> &nbsp;&bull;&nbsp; Folio: <span style="color: #64748b;">' + escapeHtml(s.folio) + '</span></span>',
          '</div>',
          '<div>' + actionBtnIndiv + '</div>',
        '</div>'
      );
    });

    groupHtml.push('</div></div>');
    return groupHtml.join('');
  }

  function renderListById(containerId, rowsHtml, emptyMessage) {
    var container = document.getElementById(containerId);
    if (!container) {
      return;
    }

    if (rowsHtml.length) {
      container.className = 'inv-data-list';
      container.innerHTML = rowsHtml.join('');
    } else {
      container.className = 'inv-data-empty';
      container.textContent = emptyMessage;
    }
  }

  function updateKpis(payload) {
    var kpis = payload.kpis || {};

    var elTasa = document.getElementById('kpiTasaRotacion');
    if (elTasa) {
      elTasa.textContent = nf2.format(toNumber(kpis.tasaRotacion)) + 'x';
    }

    var elDiasInventario = document.getElementById('kpiDiasInventario');
    if (elDiasInventario) {
      elDiasInventario.textContent = nf1.format(toNumber(kpis.diasInventario));
    }

    var elPrecision = document.getElementById('kpiPrecisionRegistro');
    if (elPrecision) {
      elPrecision.textContent = nf1.format(toNumber(kpis.precisionRegistro)) + '%';
    }

    var elPrecisionSub = document.getElementById('kpiPrecisionSub');
    if (elPrecisionSub) {
      elPrecisionSub.textContent = 'Fisico: ' + nf0.format(toNumber(kpis.stockFisico)) + ' | Registrado: ' + nf0.format(toNumber(kpis.stockRegistrado));
    }

    var elObsoleto = document.getElementById('kpiObsoleto');
    if (elObsoleto) {
      elObsoleto.textContent = nf0.format(toNumber(kpis.unidadesObsoletas));
    }

    var elObsoletoSub = document.getElementById('kpiObsoletoSub');
    if (elObsoletoSub) {
      var periodoLabel = toNumber(payload.periodoDias || 90);
      elObsoletoSub.textContent = 'Unidades sin salida en ' + nf0.format(periodoLabel) + ' dias. Articulos: ' + nf0.format(toNumber(kpis.articulosObsoletos)) + '.';
    }

    var elRotura = document.getElementById('kpiRoturaStock');
    if (elRotura) {
      elRotura.textContent = nf1.format(toNumber(kpis.roturaStock)) + '%';
    }

    var elRoturaSub = document.getElementById('kpiRoturaSub');
    if (elRoturaSub) {
      elRoturaSub.textContent = 'Solicitudes sin stock: ' + nf0.format(toNumber(kpis.solicitudesSinStock)) + ' de ' + nf0.format(toNumber(kpis.solicitudesPeriodo)) + '.';
    }

    var elStock = document.getElementById('kpiStock');
    if (elStock) {
      elStock.textContent = nf0.format(toNumber(kpis.stockRegistrado));
    }

    var elStockSub = document.getElementById('kpiStockSub');
    if (elStockSub) {
      elStockSub.textContent = 'Fisico: ' + nf0.format(toNumber(kpis.stockFisico)) + ' | Transito: ' + nf0.format(toNumber(kpis.stockTransito)) + '. Articulos: ' + nf0.format(toNumber(kpis.totalArticulos)) + '.';
    }
  }

  function updateTopCaducidad(payload) {
    var periodo = toNumber(payload.periodoDias || 0);
    var totalArticulos = toNumber(payload.topCaducidadArticulos || 0);
    var totalUnidades = toNumber(payload.topCaducidadUnidadesDia || 0);
    var lista = Array.isArray(payload.topCaducidad) ? payload.topCaducidad : [];

    var chipVentana = document.getElementById('chipVentana');
    if (chipVentana) {
      chipVentana.textContent = 'Ventana: proximos ' + nf0.format(periodo) + ' dias';
    }

    var chipArticulos = document.getElementById('chipArticulos');
    if (chipArticulos) {
      chipArticulos.textContent = 'Articulos: ' + nf0.format(totalArticulos);
    }

    var chipUnidades = document.getElementById('chipUnidades');
    if (chipUnidades) {
      chipUnidades.textContent = 'Unidades que vencen ese dia: ' + nf0.format(totalUnidades);
    }

    var rows = lista.map(buildTopCaducidadRow);
    renderListById('topCaducidadList', rows, 'Sin registros en ventana de 6 meses');
  }

  function updateFlujo(payload) {
    var flujo = payload.flujo || {};
    var articulosPorCaducidad = payload.articulosPorCaducidad || {};
    var total = 0;

    etapas.forEach(function(key) {
      total += toNumber(flujo[key]);
    });

    etapas.forEach(function(key) {
      var count = toNumber(flujo[key]);
      var pct = total > 0 ? ((count / total) * 100) : 0;
      var countEl = document.getElementById('flowCount-' + key);
      var progressEl = document.getElementById('flowProgress-' + key);
      
      var isGlobal = payload.almacenId === 'global';
      var rows = Array.isArray(articulosPorCaducidad[key]) ? buildNestedInventoryHtml(articulosPorCaducidad[key], isGlobal) : [];

      if (countEl) {
        countEl.textContent = nf0.format(count);
      }

      if (progressEl) {
        progressEl.style.width = pct.toFixed(1) + '%';
      }

      renderListById('panelList-' + key, rows, 'No hay articulos en esta etapa.');
    });
  }

  function donutCenterPlugin() {
    return {
      id: 'donutCenterText',
      afterDraw: function(chart) {
        var ctx = chart.ctx;
        var meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data || !meta.data.length) {
          return;
        }

        var data = chart.data.datasets[0].data || [];
        var healthy = Number(data[0] || 0);
        var total = data.reduce(function(sum, value) { return sum + Number(value || 0); }, 0);
        var percent = total > 0 ? Math.round((healthy / total) * 100) : 0;
        var center = meta.data[0];
        var x = center.x;
        var y = center.y;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#0f172a';
        ctx.font = '700 24px sans-serif';
        ctx.fillText(percent + '%', x, y - 8);
        ctx.fillStyle = '#475569';
        ctx.font = '600 11px sans-serif';
        ctx.fillText('art. sanos', x, y + 14);
        ctx.restore();
      }
    };
  }

  function initCharts(chartData) {
    if (typeof Chart === 'undefined') {
      return;
    }

    var donutData = chartData.donutSalud || {};
    var donutCanvas = document.getElementById('donutSaludInventario');
    var trendCanvas = document.getElementById('chartEntradasSalidas');

    if (donutCanvas) {
      donutChart = new Chart(donutCanvas, {
        type: 'doughnut',
        data: {
          labels: ['Art. con movimiento', 'Art. sin movimiento'],
          datasets: [{
            data: [toNumber(donutData.stockSano), toNumber(donutData.obsoleto)],
            backgroundColor: ['#22c55e', '#fce444'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          cutout: '78%'
        },
        plugins: [donutCenterPlugin()]
      });
    }

    if (trendCanvas) {
      trendChart = new Chart(trendCanvas, {
        type: 'line',
        data: {
          labels: chartData.labels || [],
          datasets: [
            {
              label: 'Entradas',
              data: chartData.entradas || [],
              borderColor: '#1d4ed8',
              backgroundColor: 'rgba(37, 99, 235, 0.12)',
              borderWidth: 2,
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 5,
              pointBackgroundColor: '#1d4ed8',
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2,
              fill: true
            },
            {
              label: 'Salidas',
              data: chartData.salidas || [],
              borderColor: '#ef4444',
              backgroundColor: 'rgba(239, 68, 68, 0.10)',
              borderWidth: 2,
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 5,
              pointBackgroundColor: '#ef4444',
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          interaction: { mode: 'index', intersect: false },
          scales: {
            x: {
              grid: { display: false }
            },
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(148, 163, 184, 0.18)' }
            }
          }
        }
      });
    }
  }

  function updateCharts(chartData) {
    if (!chartData) {
      return;
    }

    if (donutChart) {
      donutChart.data.datasets[0].data = [
        toNumber(chartData.donutSalud && chartData.donutSalud.stockSano),
        toNumber(chartData.donutSalud && chartData.donutSalud.obsoleto)
      ];
      donutChart.update();
    }

    if (trendChart) {
      trendChart.data.labels = chartData.labels || [];
      trendChart.data.datasets[0].data = chartData.entradas || [];
      trendChart.data.datasets[1].data = chartData.salidas || [];
      trendChart.update();
    }
  }

  function applyPayload(payload) {
    currentPayload = payload || {};
    updateKpis(payload);
    updateTopCaducidad(payload);
    updateFlujo(payload);
    updateCharts(payload.chartData || {});
    setSkeletonState(false);
    firstDataLoadCompleted = true;
  }

  function setSkeletonState(isLoading) {
    var metricIds = [
      'kpiTasaRotacion',
      'kpiDiasInventario',
      'kpiPrecisionRegistro',
      'kpiObsoleto',
      'kpiRoturaStock',
      'kpiStock'
    ];

    metricIds.forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) {
        return;
      }
      el.classList.toggle('inv-skeleton-text', isLoading);
    });

    var chartCards = document.querySelectorAll('.inv-analytics-card');
    chartCards.forEach(function(card) {
      card.classList.toggle('inv-skeleton-chart', isLoading);
    });
  }

  function getMonthIndexFromDateText(value) {
    var text = String(value == null ? '' : value).trim();
    var match = null;

    if (!text) {
      return -1;
    }

    match = text.match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})/);
    if (match) {
      return Math.max(0, Math.min(11, parseInt(match[2], 10) - 1));
    }

    match = text.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/);
    if (match) {
      return Math.max(0, Math.min(11, parseInt(match[2], 10) - 1));
    }

    var parsed = Date.parse(text);
    if (!Number.isNaN(parsed)) {
      return new Date(parsed).getMonth();
    }

    return -1;
  }

  function getMonthLabelFromDateText(value) {
    var monthIndex = getMonthIndexFromDateText(value);
    return monthIndex >= 0 ? monthNames[monthIndex] : '';
  }

  function printDashboard() {
    enterPrintMode();
    window.requestAnimationFrame(function() {
      window.print();
    });
  }

  function enterPrintMode() {
    if (document.body.classList.contains('inv-print-flow')) {
      return;
    }

    document.body.classList.add('inv-print-flow');
    printPanelState = {};

    etapas.forEach(function(key) {
      var panel = document.getElementById('panel-' + key);
      if (!panel) {
        return;
      }

      printPanelState[key] = panel.style.display;
      panel.style.display = 'block';
    });
  }

  function exitPrintMode() {
    document.body.classList.remove('inv-print-flow');

    etapas.forEach(function(key) {
      var panel = document.getElementById('panel-' + key);
      if (!panel) {
        return;
      }

      if (Object.prototype.hasOwnProperty.call(printPanelState, key)) {
        panel.style.display = printPanelState[key];
      }
    });

    printPanelState = {};
  }

  function csvEscape(value) {
    var text = String(value == null ? '' : value);
    return '"' + text.replace(/"/g, '""') + '"';
  }

  function buildExportCsv(payload) {
    var lines = [];
    var kpis = payload.kpis || {};
    var articulosPorCaducidad = payload.articulosPorCaducidad || {};
    var totalPorEtapa = payload.flujo || {};
    var flowRows = [];

    etapas.forEach(function(key) {
      var items = Array.isArray(articulosPorCaducidad[key]) ? articulosPorCaducidad[key] : [];
      items.forEach(function(item) {
        flowRows.push([
          'Material por caducar',
          etapaLabels[key] || key,
          getMonthLabelFromDateText(item.CADUCIDAD || ''),
          item.CLAVE_ARTICULO || '',
          item.NOMBRE || '',
          item.LOTE || '',
          item.CADUCIDAD || '',
          nf0.format(toNumber(item.DIAS_RESTANTES)),
          item.ALMACEN_NOMBRE || ''
        ]);
      });
    });

    lines.push('Seccion,Indicador,Valor');
    lines.push(['KPIs', 'Tasa de rotacion', nf2.format(toNumber(kpis.tasaRotacion)) + 'x'].map(csvEscape).join(','));
    lines.push(['KPIs', 'Dias de inventario', nf1.format(toNumber(kpis.diasInventario))].map(csvEscape).join(','));
    lines.push(['KPIs', 'Precision de registro', nf1.format(toNumber(kpis.precisionRegistro)) + '%'].map(csvEscape).join(','));
    lines.push(['KPIs', 'Inventario obsoleto', nf0.format(toNumber(kpis.unidadesObsoletas))].map(csvEscape).join(','));
    lines.push(['KPIs', 'Indice de rotura de stock', nf1.format(toNumber(kpis.roturaStock)) + '%'].map(csvEscape).join(','));
    lines.push('');
    lines.push('Material por caducar,Etapa,Mes caducidad,Referencia,Articulo,Lote,Caducidad,Dias restantes,Almacen');

    flowRows.forEach(function(row) {
      lines.push(row.map(csvEscape).join(','));
    });

    lines.push('');
    lines.push('Flujo de proceso,Etapa,Cantidad');
    etapas.forEach(function(key) {
      lines.push([
        'Flujo de proceso',
        etapaLabels[key] || key,
        nf0.format(toNumber(totalPorEtapa[key]))
      ].map(csvEscape).join(','));
    });

    return lines.join('\r\n');
  }

  function exportDashboardCsv() {
    var csv = buildExportCsv(currentPayload || {});
    var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    var url = window.URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'dashboard-inventario.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }

  function setFiltersBusy(isBusy) {
    if (selectAlmacen) {
      selectAlmacen.disabled = isBusy;
    }
    if (selectPeriodo) {
      selectPeriodo.disabled = isBusy;
    }
    if (filterForm) {
      filterForm.style.opacity = isBusy ? '0.7' : '1';
    }
  }

  function setAjaxStatus(message, type) {
    if (!ajaxStatus) {
      return;
    }

    ajaxStatus.classList.remove('d-none', 'inv-status-loading', 'inv-status-error');

    if (!message) {
      ajaxStatus.textContent = '';
      ajaxStatus.classList.add('d-none');
      return;
    }

    ajaxStatus.textContent = message;
    if (type === 'error') {
      ajaxStatus.classList.add('inv-status-error');
    } else if (type === 'loading') {
      ajaxStatus.classList.add('inv-status-loading');
    }
  }

  function hidePageLoading() {
    if (!pageLoading) {
      return;
    }

    pageLoading.style.display = 'none';
  }

  function syncUrlWithFilters() {
    if (!selectAlmacen || !selectPeriodo || !window.history || !window.history.replaceState) {
      return;
    }

    var url = new URL(window.location.href);
    url.searchParams.set('almacen_id', selectAlmacen.value || 'global');
    url.searchParams.set('periodo', selectPeriodo.value || '90');
    window.history.replaceState({}, '', url.toString());
  }

  function fetchDashboardData() {
    if (!selectAlmacen || !selectPeriodo) {
      return;
    }

    requestSerial += 1;
    var currentRequest = requestSerial;
    var params = new URLSearchParams();
    params.set('almacen_id', selectAlmacen.value || 'global');
    params.set('periodo', selectPeriodo.value || '90');
    params.set('_ts', String(Date.now()));

    setFiltersBusy(true);
    setAjaxStatus('Actualizando datos del dashboard...', 'loading');

    if (activeController) {
      activeController.abort();
    }
    activeController = new AbortController();

    var tryEndpoint = function(index) {
      if (index >= dataEndpointCandidates.length) {
        return Promise.reject(new Error('No se encontro un endpoint AJAX valido para el dashboard.'));
      }

      var endpoint = dataEndpointCandidates[index] + '?' + params.toString();
      return fetch(endpoint, {
        signal: activeController.signal,
        cache: 'no-store',
        headers: {
          'Cache-Control': 'no-cache',
          'Pragma': 'no-cache',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Error HTTP ' + response.status + ' en ' + endpoint);
          }
          var contentType = response.headers.get('content-type') || '';
          if (contentType.indexOf('application/json') === -1) {
            throw new Error('Respuesta no JSON en ' + endpoint);
          }
          return response.json();
        })
        .catch(function(error) {
          if (error && error.name === 'AbortError') {
            throw error;
          }
          return tryEndpoint(index + 1);
        });
    };

    tryEndpoint(0)
      .then(function(payload) {
        if (currentRequest !== requestSerial) {
          return;
        }
        if (!payload || payload.ok === false) {
          var message = payload && payload.message ? payload.message : 'No fue posible cargar los datos del dashboard.';
          throw new Error(message);
        }
        applyPayload(payload || {});
        syncUrlWithFilters();
        setAjaxStatus('', '');
      })
      .catch(function(error) {
        if (error && error.name === 'AbortError') {
          return;
        }
        if (!firstDataLoadCompleted) {
          setSkeletonState(false);
        }
        var detail = error && error.message ? (' Detalle: ' + error.message) : '';
        setAjaxStatus('No se pudieron actualizar los datos del dashboard.' + detail, 'error');
        console.error('No se pudo actualizar el dashboard por AJAX.', error);
      })
      .finally(function() {
        if (currentRequest === requestSerial) {
          setFiltersBusy(false);
        }
      });
  }

  if (typeof Chart !== 'undefined') {
    initCharts(initialPayload.chartData || {});
  }

  if (document.readyState === 'complete') {
    hidePageLoading();
  } else {
    window.addEventListener('load', function() {
      hidePageLoading();
    });
  }

  if (filterForm) {
    filterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      fetchDashboardData();
    });
  }

  if (selectAlmacen) {
    selectAlmacen.addEventListener('change', function() {
      fetchDashboardData();
    });
  }

  if (selectPeriodo) {
    selectPeriodo.addEventListener('change', function() {
      fetchDashboardData();
    });
  }

  if (deferInitialLoad) {
    setSkeletonState(true);
    if (document.readyState === 'complete') {
      fetchDashboardData();
    } else {
      window.addEventListener('load', function() {
        fetchDashboardData();
      }, { once: true });
    }
  }

  if (printButton) {
    printButton.addEventListener('click', function() {
      printDashboard();
    });
  }

  window.addEventListener('beforeprint', enterPrintMode);
  window.addEventListener('afterprint', exitPrintMode);

  if (exportButton) {
    exportButton.addEventListener('click', function() {
      exportDashboardCsv();
    });
  }

  etapas.forEach(function(etapa) {
    var btn = document.getElementById('flowBtn-' + etapa);
    var panel = document.getElementById('panel-' + etapa);

    if (btn && panel) {
      btn.addEventListener('click', function() {
        if (panel.style.display === 'none') {
          panel.style.display = 'block';
          panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
          panel.style.display = 'none';
        }
      });
    }
  });

  window.iniciarAccionInteligente = function(stockId, btnEl) {
    var originalHtml = btnEl.innerHTML;
    btnEl.innerHTML = 'Evaluando...';
    btnEl.disabled = true;
    
    var formData = new FormData();
    formData.append('action', 'evaluar');
    formData.append('stock_id', stockId);
    
    fetch('../ajax/dashboard.accion.php', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      btnEl.innerHTML = originalHtml;
      btnEl.disabled = false;
      
      if (!data.ok) {
         if (window.Swal) Swal.fire('Aviso', data.message, 'info');
         else alert(data.message);
         return;
      }
      
      var textHtml = data.message;
      if (window.Swal) {
         Swal.fire({
            title: 'Acción Inteligente',
            html: textHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear traspaso',
            cancelButtonText: 'Cancelar'
         }).then(function(res) {
            if (res.isConfirmed) {
               ejecutarAccionInteligente(stockId, data.suggestion, data.data.target_maleta_id, data.data.swap_stock_id);
            }
         });
      } else {
         if (confirm(textHtml.replace(/<[^>]+>/g, ' '))) {
            ejecutarAccionInteligente(stockId, data.suggestion, data.data.target_maleta_id, data.data.swap_stock_id);
         }
      }
    })
    .catch(function(err) {
      btnEl.innerHTML = originalHtml;
      btnEl.disabled = false;
      console.error(err);
      alert('Error de conexión.');
    });
  };

  function ejecutarAccionInteligente(stockId, suggestion, targetMaletaId, swapStockId) {
     var formData = new FormData();
     formData.append('action', 'ejecutar');
     formData.append('stock_id', stockId);
     formData.append('suggestion', suggestion);
     formData.append('target_maleta_id', targetMaletaId);
     if (swapStockId) formData.append('swap_stock_id', swapStockId);
     
     if (typeof Swal !== 'undefined') {
       Swal.fire({
          title: 'Procesando...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
       });
     }
     
     fetch('../ajax/dashboard.accion.php', {
        method: 'POST',
        body: formData
     })
     .then(function(r) { return r.json(); })
     .then(function(data) {
        if (!data.ok) {
           if (window.Swal) Swal.fire('Error', data.message, 'error');
           else alert(data.message);
           return;
        }
        if (window.Swal) Swal.fire('Éxito', data.message, 'success');
        else alert(data.message);
        
        fetchDashboardData();
     })
     .catch(function(err) {
        if (window.Swal) Swal.close();
        console.error(err);
        alert('Error de conexión al ejecutar.');
     });
  }

  window.iniciarAccionInteligenteBulk = function(stockIdsStr, btnEl) {
    var originalHtml = btnEl.innerHTML;
    btnEl.innerHTML = 'Evaluando Lote...';
    btnEl.disabled = true;
    
    var formData = new FormData();
    formData.append('action', 'evaluar_bulk');
    formData.append('stock_ids', stockIdsStr);
    
    fetch('../ajax/dashboard.accion.php', {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      btnEl.innerHTML = originalHtml;
      btnEl.disabled = false;
      
      if (!data.ok) {
         if (window.Swal) Swal.fire('Aviso', data.message, 'info');
         else alert(data.message);
         return;
      }
      
      var textHtml = data.message;
      if (window.Swal) {
         Swal.fire({
            title: 'Acción Múltiple Inteligente',
            html: textHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear todos los traspasos',
            cancelButtonText: 'Cancelar'
         }).then(function(res) {
            if (res.isConfirmed) {
               ejecutarAccionInteligenteBulk(stockIdsStr, data.data);
            }
         });
      } else {
         if (confirm(textHtml.replace(/<[^>]+>/g, ' '))) {
            ejecutarAccionInteligenteBulk(stockIdsStr, data.data);
         }
      }
    })
    .catch(function(err) {
      btnEl.innerHTML = originalHtml;
      btnEl.disabled = false;
      console.error(err);
      alert('Error de conexión.');
    });
  };

  function ejecutarAccionInteligenteBulk(stockIdsStr, suggestionDataRaw) {
     var formData = new FormData();
     formData.append('action', 'ejecutar_bulk');
     formData.append('stock_ids', stockIdsStr);
     formData.append('suggestion_data', JSON.stringify(suggestionDataRaw));
     
     if (typeof Swal !== 'undefined') {
       Swal.fire({
          title: 'Procesando Lote...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
       });
     }
     
     fetch('../ajax/dashboard.accion.php', {
        method: 'POST',
        body: formData
     })
     .then(function(r) { return r.json(); })
     .then(function(data) {
        if (!data.ok) {
           if (window.Swal) Swal.fire('Error', data.message, 'error');
           else alert(data.message);
           return;
        }
        if (window.Swal) Swal.fire('Éxito', data.message, 'success');
        else alert(data.message);
        
        fetchDashboardData();
     })
     .catch(function(err) {
        if (window.Swal) Swal.close();
        console.error(err);
        alert('Error de conexión al ejecutar lote.');
     });
  }

})();
