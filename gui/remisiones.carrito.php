<?php
require '../vendor/autoload.php';
include_once("../includes/includes.php");

// Obtener el stockid desde GET
$stockid = isset($_GET['stockid']) ? intval(base64_decode($_GET['stockid'])) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito Remisión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root{ --primary:#0d6efd; --danger:#dc3545; --ok:#198754; }
        *{ box-sizing:border-box; }
        body{ font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 12px; margin:0; background:#f7f7f9; }
        img{ max-width:180px; height:auto; display:block; margin:0 auto 12px; }
        .card{ background:#fff; padding:14px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:12px; }
        h3,h4{ margin:0 0 8px; font-weight:700; }
        p{ margin:.25rem 0; line-height:1.35; }
        .btn{ width:100%; padding:12px 14px; border:0; border-radius:10px; font-weight:700; display:block; }
        .btn + .btn{ margin-top:8px; }
        .btn-primary{ background:var(--primary); color:#fff; }
        .btn-danger{ background:var(--danger); color:#fff; }
        .btn-ok{ background:var(--ok); color:#fff; }
        .hidden{ display:none !important; }
        .note{ font-size:.95rem; color:#555; }
        .msg{ padding:10px 12px; border-left:5px solid var(--primary); background:#eef6ff; border-radius:8px; }
        .msg.warn{ border-color:#f0ad4e; background:#fff7e6; }
        .msg.err{ border-color:#dc3545; background:#fdecec; }
        .kv{ display:flex; gap:6px; align-items:baseline; }
        .kv strong{ min-width:120px; font-size:.98rem; }
        @media (min-width:480px){
            .kv strong{ min-width:140px; }
        }
        iframe{ border:none; border-radius:8px; }
    </style>
</head>
<body>
<img src="../img/logo.png">

<div class="card" id="resultado">
    <h3>Validando artículo...</h3>
</div>

<div class="card hidden" id="infoArticulo">
    <h4 id="nombreArticulo"></h4>
    <p><strong>Evento:</strong> <span id="eventoNombre"></span></p>
    <p><strong>Maleta:</strong> <span id="maletaNombre"></span></p>
    <p><strong>Precio:</strong> $<span id="precioArticulo"></span></p>
    <p><strong>Folio Remisión:</strong> <span id="folioRemision"></span></p>
    <button class="btn btn-primary hidden" id="btnAgregarArticulo">Agregar a remisión</button>
    <button class="btn btn-danger hidden" id="btnVerRemision">Editar remisión</button>
</div>

<div class="card hidden" id="mensajeRemision" style="border-left: 5px solid #007bff;">
    <p id="textoMensaje" style="margin: 0;"></p>
</div>

<div class="card hidden" id="detalleRemision">
    <iframe id="iframeDetalle" src="" width="100%" height="600" style="border: none;"></iframe>
</div>

<script src="../js/jquery-3.6.0.min.js"></script>
<script>
const stockid = <?= $stockid ?>;

$(function(){
  const $resultado = $("#resultado");
  const $info = $("#infoArticulo");
  const $detalle = $("#detalleRemision");
  const $mensaje = $("#mensajeRemision");
  const $textoMensaje = $("#textoMensaje");

  function pintarInfo(data){
    $("#nombreArticulo").text(data.articulo_nombre);
    $("#eventoNombre").text(data.evento_nombre);
    $("#maletaNombre").text(data.maleta_nombre);
    $("#precioArticulo").text( Number(data.precio_total).toFixed(2) );
    $("#folioRemision").text(data.remisionfolio ?? '—');
  }

  function mostrarMensajeYDetalle(mensaje, remisionid){
    $resultado.hide();
    $info.addClass("hidden");
    $mensaje.removeClass("hidden");
    $textoMensaje.text(mensaje);

    const remid64 = btoa(remisionid);
    $("#iframeDetalle").attr("src", `../includes/remisiones.info.php?remisionid=${remid64}`);
    $detalle.removeClass("hidden");
  }

  // Cargar validación
  $.getJSON(`../ajax/remisiones.validar.articulo.qr.php?stockid=${stockid}`, function(data){
    console.log("VALIDAR:", data);

    if(!data.valido){
      if(data.mensaje === 'NOEVENTO'){
        $resultado.html(`<div class="msg err"><strong>Sin evento iniciado</strong><br>${data.detalle || 'No se encontró ningún evento iniciado que contenga este artículo.'}</div>`);
      }else if(data.mensaje === 'YAAGREGADO'){
        mostrarMensajeYDetalle("⚠️ El artículo ya está en la remisión activa.", data.remisionid);
      }else{
        $resultado.html(`<div class="msg err">Ocurrió un caso no contemplado. Revisa con IT.</div>`);
      }
      return;
    }

    // Caso válido = "AGREGAR"
    $resultado.addClass("hidden");
    $info.removeClass("hidden");
    pintarInfo(data);

    // Botón AGREGAR
    $("#btnAgregarArticulo").removeClass("hidden").off().on("click", function(){
      $.post("../ajax/remisiones.agregararticuloqr.php", {
        stockid: data.stockid,
        remisionid: data.remisionid,
        precio: data.precio,
        sucursalid: data.sucursalid
      }, function(resp){
        if(resp === ""){
          mostrarMensajeYDetalle("✅ Artículo agregado a la remisión.", data.remisionid);
        }else if(resp === "YA"){
          mostrarMensajeYDetalle("⚠️ El artículo ya se encontraba en la remisión.", data.remisionid);
        }else{
          alert("Error al agregar artículo: " + resp);
        }
      });
    });

    // Botón VER/FINALIZAR REMISIÓN
    $("#btnVerRemision").removeClass("hidden").off().on("click", function(){
      if(confirm("¿Finalizar esta remisión?")){
        $.post("../ajax/remisiones.finalizar.php", { remisionid: data.remisionid }, function(resp){
          if(resp === "OK"){
            alert("Remisión finalizada correctamente.");
            location.reload();
          }else{
            alert("Error al finalizar la remisión.");
          }
        });
      }
    });
  });
});
</script>

</body>
</html>