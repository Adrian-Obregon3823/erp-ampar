<footer class="footer">
  <div class="d-sm-flex justify-content-center justify-content-sm-between">
    <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
      Copyright © 2025. Todos los derechos reservados.
    </span>
    <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
      Desarrollado por <a href="http://www.evotek.com.mx/" target="_blank"><img src="../img/byevotek.png" height="40px"></a>
    </span>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interceptar clics en botones de PDF de remisiones para preguntar si muestran precios
    if (typeof $ !== 'undefined') {
        $(document).on('click', 'a[href*="gdocs/remision"]', function(e) {
            if($(this).attr('href').indexOf('formato.php') !== -1) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Mostrar precios?',
                        text: '¿Deseas que el PDF de la remisión incluya los precios unitarios y totales?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, mostrar',
                        cancelButtonText: 'No, ocultar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(url, '_blank');
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            var sep = url.indexOf('?') !== -1 ? '&' : '?';
                            window.open(url + sep + 'noprecios=1', '_blank');
                        }
                    });
                } else {
                    if (confirm('¿Quieres que el PDF de la remisión incluya los precios unitarios y totales?\n\n[Aceptar] = Sí, mostrar\n[Cancelar] = No, ocultar')) {
                        window.open(url, '_blank');
                    } else {
                        var sep = url.indexOf('?') !== -1 ? '&' : '?';
                        window.open(url + sep + 'noprecios=1', '_blank');
                    }
                }
            }
        });
    }
});
</script>