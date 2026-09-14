document.addEventListener("DOMContentLoaded", function() {
    let ventasChart = null;

    const formatter = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    });

    function loadVentasData() {
        const almacenId = $('#ventas_almacen_id').val();
        const periodo = $('#ventas_periodo').val();
        
        // Mostrar estado de carga en la tabla de artículos
        $('#table-ventas-top-articulos').html('<tr><td colspan="2" class="text-muted text-center py-4">Cargando datos...</td></tr>');
        
        $.ajax({
            url: '../ajax/dashboard.ventas.data.php',
            type: 'GET',
            dataType: 'json',
            data: { almacen_id: almacenId, periodo: periodo },
            success: function(response) {
                if (response.status === 'success') {
                    // Actualizar KPIs
                    $('#kpi-ventas-total').text(formatter.format(response.kpis.totalIngresos));
                    $('#kpi-ventas-count').text(response.kpis.totalRemisiones);
                    $('#kpi-ventas-promedio').text(formatter.format(response.kpis.ticketPromedio));
                    
                    // Actualizar Gráfica
                    renderVentasChart(response.chart.labels, response.chart.data);
                    
                    // Actualizar Tabla Top Artículos
                    let tableHtml = '';
                    if (response.topArticulos && response.topArticulos.length > 0) {
                        response.topArticulos.forEach(art => {
                            tableHtml += `
                                <tr>
                                    <td class="font-weight-bold" style="font-size: 0.85rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${art.nombre}">${art.nombre}</td>
                                    <td class="text-right text-success font-weight-bold">${formatter.format(art.ingreso)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        tableHtml = '<tr><td colspan="2" class="text-muted text-center py-4">No hay datos en este periodo</td></tr>';
                    }
                    $('#table-ventas-top-articulos').html(tableHtml);
                } else {
                    console.error('Error cargando dashboard de ventas:', response.message);
                }
            },
            error: function(err) {
                console.error('Error de conexión AJAX', err);
            }
        });
    }

    function renderVentasChart(labels, data) {
        const ctx = document.getElementById('chart-ventas-tendencia');
        if (!ctx) return;
        
        if (ventasChart) {
            ventasChart.destroy();
        }
        
        ventasChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos',
                    data: data,
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgba(23, 162, 184, 1)',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatter.format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatter.format(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Inicializar datos cuando se abre la pestaña de ventas
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#ventas') {
            loadVentasData();
        }
    });

    $('#ventas-tab').on('click', function() {
        setTimeout(loadVentasData, 200);
    });

    // Detectar cambios en los selectores del dashboard de ventas
    $('#ventas_almacen_id, #ventas_periodo').on('change', function() {
        loadVentasData();
    });

    // Si ya está activo en la carga inicial
    if ($('#ventas-tab').hasClass('active')) {
        loadVentasData();
    }
});
