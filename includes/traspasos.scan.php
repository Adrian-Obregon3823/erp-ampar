<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Escaneo RFID - Traspaso de Maletas</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    
  <div class="container my-4">
    <h4 class="text-center mb-4">Escaneo de Área - Maletas Balones</h4>

    <label><strong>Nivel de llenado de la maleta</strong></label>
<div class="progress" style="height: 25px;">
  <div class="progress-bar bg-warning" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
    65%
  </div>
  <div class="progress-bar bg-success" role="progressbar" style="width: 30%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">
    +30%
  </div>
</div>
<small class="text-muted">Con los traspasos llegaría al 95%</small>

<br><br>
    <div class="row">
      <!-- Maletas Registradas -->
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">Maletas en Inventario</div>
          <ul class="list-group list-group-flush" id="maletasInventario">
            <li class="list-group-item">Artículo A001 - Maleta Balones</li>
            <li class="list-group-item">Artículo B045 - Maleta Balones</li>
            <li class="list-group-item">Artículo C019 - Maleta Balones</li>
          </ul>
        </div>
      </div>

      <!-- Maletas Detectadas -->
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white">Maletas Detectadas</div>
          <ul class="list-group list-group-flush" id="maletasDetectadas">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Artículo A011
              <span class="badge badge-secondary">Almacén Central</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Artículo C019
              <span class="badge badge-secondary">Almacén Central</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Botón de acción -->
    <div class="text-center mt-4">
      <button class="btn btn-primary btn-lg" onclick="traspasar()">Traspasar Maletas Detectadas</button>
    </div>
  </div>

  <script>
    function traspasar() {
      alert("Traspaso ejecutado para las maletas detectadas.");
      // Aquí llamas tu lógica backend con AJAX o fetch
    }
  </script>
</body>
</html>
