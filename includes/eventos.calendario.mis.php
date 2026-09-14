<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php $usersesion = $_SESSION['ampar']['usuario']; ?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <?php include_once("../includes/head.php");?>
        <style>
            body {
                padding: 20px;
                background: #f8f9fa;
            }
            #calendar {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            }
            /* Quitar estilos de link para días y números */
            .fc-daygrid-day-top a,
            .fc-daygrid-day-number a {
                color: #212529 !important; /* color texto normal (bootstrap body text color) */
                text-decoration: none !important; /* quita subrayado */
                cursor: default !important; /* cursor normal */
            }
            /* Cursor manita sobre eventos para indicar que son clickeables */
            .fc-event {
                cursor: pointer !important;
            }
            .fc-event:hover {
                background-color: #2a6ad1 !important; /* color azul más oscuro al pasar mouse */
                border-color: #204a8a !important;
            }
            /* Encabezados de días (Lun, Mar, Mié, etc.) */
            .fc-col-header-cell {
                background-color: black !important;
                color: white !important;
                font-weight: bold !important;
            }

            /* Quitar subrayado si por algún motivo se aplica a los links del encabezado */
            .fc-col-header-cell a {
                color: white !important;
                text-decoration: none !important;
            }
            .fc-event {
                color: black !important;        /* letra negra */
                border: none !important;        
                border-radius: 3px !important; 
                font-weight: bold;
                font-size: 0.6rem !important;  /* letra más pequeña */
                padding: 2px 4px;
                line-height: 1rem;              /* controla altura de línea */
                white-space: nowrap;             /* evita que se divida en varias líneas */
                overflow: hidden;                /* corta lo que se salga del cuadro */
                text-overflow: ellipsis;         /* pone "..." si se corta */
                min-height: 18px;                /* altura mínima para que siga siendo clickeable */
                display: flex;
                align-items: center;
            }

            /* Responsive FullCalendar Header */
            @media (max-width: 767px) {
                .fc .fc-toolbar.fc-header-toolbar {
                    flex-direction: column;
                    gap: 12px;
                    margin-bottom: 1em !important;
                }
                .fc .fc-toolbar-chunk {
                    display: flex;
                    justify-content: center;
                    width: 100%;
                }
                .fc .fc-toolbar-title {
                    font-size: 1.4em !important;
                    font-weight: 600;
                    color: #2c3e50;
                }
                .fc .fc-button {
                    padding: 0.4em 0.8em !important;
                    font-size: 0.9em !important;
                }
                .fc-col-header-cell-cushion, .fc-daygrid-day-number {
                    font-size: 0.85em;
                }
            }
        </style>
        
    </head>
    <body>

        <div id="calendar"></div>

        <!-- Modal Bootstrap -->
        <div class="modal fade" id="eventoModal" tabindex="-1" aria-labelledby="eventoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventoModalLabel">Detalles del Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Aquí se cargará el contenido vía AJAX -->
                <div id="eventoDetalle" style="min-height: 150px;">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                    <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div>Cargando detalles...</div>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
            </div>
        </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var modal = new bootstrap.Modal(document.getElementById('eventoModal'));
                var detalleContainer = document.getElementById('eventoDetalle');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: '../ajax/eventos.calendario.php?usuarioid=<?=base64_encode($usersesion['USUARIO_ID'])?>', // tu JSON
                    selectable: true,
                    eventClick: function(info) {
                        var eventId = btoa(info.event.id);
                        modal.show();
                        detalleContainer.innerHTML = `<div class="text-center py-5">
                            <div class="spinner-border" role="status"></div>
                            <div>Cargando detalles...</div>
                        </div>`;
                        $.ajax({
                            url: '../includes/eventos.info.php',
                            method: 'GET',
                            data: { eventoid: eventId },
                            success: function(data) { detalleContainer.innerHTML = data; },
                            error: function() { detalleContainer.innerHTML = '<p class="text-danger">No se pudo cargar el detalle del evento.</p>'; }
                        });
                    }
                });

                calendar.render();
            });
        </script>
    </body>
</html>