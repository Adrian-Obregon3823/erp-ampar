<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Eventos con Slider y Gráficas</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    .dashboard-row {
      display: flex;
      gap: 20px;
      align-items: flex-start;
      padding: 20px;
      flex-wrap: wrap;
    }

    .dashboard-item {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 10px;
      width: 300px;
    }

    .carousel-inner {
      text-align: center;
    }

    .carousel-item {
      padding: 15px;
    }

    .event-card {
      background: #f0f0f0;
      border-radius: 10px;
      padding: 15px;
    }

    .event-card h6 {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="dashboard-row">
  <!-- Gráfica Autorizados -->
  <div class="dashboard-item">
    <canvas id="graficaAutorizados" height="200"></canvas>
  </div>

  <!-- Gráfica No Autorizados -->
  

  <!-- Slider tipo carrusel -->
  <div class="dashboard-item">
  <div id="eventosCarousel" class="carousel slide" data-bs-interval="false">
  <div class="carousel-inner" id="carouselEventos">
        <!-- Eventos se insertan desde JS -->
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#eventosCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#eventosCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span>
      </button>
    </div>
  </div>

  <div class="dashboard-item">
                    <div class="row flex-grow">
                        <!-- Top Vendedores -->
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h4 class="card-title card-title-dash">Especialistas</h4>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="wrapper d-flex align-items-center justify-content-between py-2 border-bottom">
                                                    <div class="d-flex">
                                                        <img class="img-sm rounded-10" src="../images/faces/face1.jpg" alt="profile">
                                                        <div class="wrapper ms-3">
                                                            <p class="ms-1 mb-1 fw-bold">Especialista A</p>
                                                            <small class="text-muted mb-0">162543</small>
                                                        </div>
                                                    </div>
                                                    <div class="text-muted text-small">
                                                        09:00 am
                                                    </div>
                                                </div>
                                                <div class="wrapper d-flex align-items-center justify-content-between py-2 border-bottom">
                                                    <div class="d-flex">
                                                        <img class="img-sm rounded-10" src="../images/faces/face2.jpg" alt="profile">
                                                        <div class="wrapper ms-3">
                                                            <p class="ms-1 mb-1 fw-bold">Especialista B</p>
                                                            <small class="text-muted mb-0">162543</small>
                                                        </div>
                                                    </div>
                                                    <div class="text-muted text-small">
                                                    9:00 am
                                                    </div>
                                                </div>
                                                <div class="wrapper d-flex align-items-center justify-content-between py-2 border-bottom">
                                                    <div class="d-flex">
                                                        <img class="img-sm rounded-10" src="../images/faces/face3.jpg" alt="profile">
                                                        <div class="wrapper ms-3">
                                                            <p class="ms-1 mb-1 fw-bold">Especialista C</p>
                                                            <small class="text-muted mb-0">162543</small>
                                                        </div>
                                                    </div>
                                                    <div class="text-muted text-small">
                                                        10:00 am
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
</div>
</div>


<!-- Modal -->
<div class="modal fade" id="modalEventos" tabindex="-1" aria-labelledby="modalEventosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEventosLabel">Detalles de eventos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul id="listaEventos" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<script>
  const eventos = [
    { titulo: "Evento A", fecha: "10:00 am", autorizado: true },
    { titulo: "Evento B", fecha: "10:30 am", autorizado: false },
    { titulo: "Evento C", fecha: "02:00 pm", autorizado: true },
    { titulo: "Evento D", fecha: "02:00 pm", autorizado: false },
    { titulo: "Evento E", fecha: "06:00 pm", autorizado: true }
  ];

  // Gráficas
  const autorizados = eventos.filter(e => e.autorizado).length;
  const noAutorizados = eventos.filter(e => !e.autorizado).length;

  const ctx1 = document.getElementById("graficaAutorizados").getContext("2d");
  new Chart(ctx1, {
    type: "doughnut",
    data: {
      labels: ["Autorizados", "No autorizados"],
      datasets: [{
        data: [autorizados, noAutorizados],
        backgroundColor: ["#4CAF50", "#FF9800"]
      }]
    },
    options: {
      onClick: (e, elements) => {
        if (elements.length > 0) {
          const index = elements[0].index;
          const filtro = index === 0;
          mostrarEventosFiltrados(filtro);
        }
      }
    }
  });

  /*const ctx2 = document.getElementById("graficaNoAutorizados").getContext("2d");
  new Chart(ctx2, {
    type: "bar",
    data: {
      labels: ["Autorizados", "No autorizados"],
      datasets: [{
        label: "Eventos",
        data: [autorizados, noAutorizados],
        backgroundColor: ["#4CAF50", "#FF9800"]
      }]
    },
    options: {
      onClick: (e, elements) => {
        if (elements.length > 0) {
          const index = elements[0].index;
          const filtro = index === 0;
          mostrarEventosFiltrados(filtro);
        }
      },
      plugins: { legend: { display: false } }
    }
  });*/

  // Modal
  function mostrarEventosFiltrados(autorizado) {
    const lista = eventos.filter(e => e.autorizado === autorizado);
    const $listaEventos = $("#listaEventos").empty();
    lista.forEach(e => {
      $listaEventos.append(`<li class="list-group-item"><strong>${e.titulo}</strong> - ${e.fecha}</li>`);
    });
    const label = autorizado ? "Eventos Autorizados" : "Eventos No Autorizados";
    $("#modalEventosLabel").text(label);
    new bootstrap.Modal(document.getElementById("modalEventos")).show();
  }

  // Cargar eventos en carrusel
const $carousel = $("#carouselEventos");
eventos.forEach((e, i) => {
  $carousel.append(`
    <div class="carousel-item ${i === 0 ? 'active' : ''}">
      <div class="event-card">
        <h6>${e.titulo}</h6>
        <p>
          <strong>Hora:</strong> 
          <a href="#" class="text-decoration-underline text-primary evento-fecha" data-index="${i}">
            ${e.fecha}
          </a>
        </p>
        <span class="badge ${e.autorizado ? 'bg-success' : 'bg-warning'}">
          ${e.autorizado ? 'Autorizado' : 'No Autorizado'}
        </span>
      </div>
    </div>
  `);
});

// Evento click en fecha del evento
$(document).on("click", ".evento-fecha", function (e) {
  e.preventDefault();
  const index = $(this).data("index");
  const evento = eventos[index];
  $("#modalEventosLabel").text(evento.titulo);
  $("#listaEventos").html(`
    <li class="list-group-item"><strong>Fecha:</strong> ${evento.fecha}</li>
    <li class="list-group-item"><strong>Estatus:</strong> ${evento.autorizado ? 'Autorizado' : 'No Autorizado'}</li>
  `);
  new bootstrap.Modal(document.getElementById("modalEventos")).show();
});

</script>
</body>
</html>
