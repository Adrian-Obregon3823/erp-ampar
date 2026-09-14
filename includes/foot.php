<!-- jQuery primero -->
<script src="../js/jquery-3.6.0.min.js"></script>
<!-- plugins:js -->
<script src="../vendors/js/vendor.bundle.base.js"></script>
<!-- endinject -->
<!-- Plugin js for this page -->
<script src="../vendors/chart.js/Chart.min.js"></script>
<script src="../vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="../vendors/progressbar.js/progressbar.min.js"></script>
<!-- End plugin js for this page -->
<!-- Plugin js for this page -->
<script src="../vendors/typeahead.js/typeahead.bundle.min.js"></script>
<script src="../vendors/select2/select2.min.js"></script>
<script src="../vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<!-- End plugin js for this page -->
<!-- inject:js -->
<script src="../js/off-canvas.js"></script>
<script src="../js/hoverable-collapse.js"></script>
<script src="../js/template.js"></script>
<script src="../js/settings.js"></script>
<script src="../js/todolist.js"></script>
<!-- endinject -->
<!-- Custom js for this page-->
<script src="../js/dashboard.js"></script>
<script src="../js/Chart.roundedBarCharts.js"></script>
<script src="../js/file-upload.js"></script>
<script src="../js/typeahead.js"></script>
<script src="../js/select2.js"></script>
<!-- End custom js for this page-->
<script src="../js/jquery-ui.js"></script>
<script src="../lib//bootstrap-4.5.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="../js/select2.min.js"></script>
<!-- sweetalert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Fix global: limpiar backdrop del modal al cerrarse -->
<script>
$(function() {
    // Si Bootstrap crea más de un backdrop, eliminar los extras
    $('#modalglobal').on('shown.bs.modal', function() {
        if ($('.modal-backdrop').length > 1) {
            $('.modal-backdrop').not(':last').remove();
        }
    });

    // Al cerrarse por cualquier razón: limpiar body y backdrops restantes
    $('#modalglobal').on('hidden.bs.modal', function() {
        $(this).find('.modal-body').html('');
        $(this).find('.modal-dialog').removeClass('modal-xl modal-lg');
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal-backdrop').remove();
    });

    // Fix global: Select2 no se puede usar dentro de modales Bootstrap
    // porque Bootstrap captura el foco y evita los clicks en el dropdown.
    // Esta línea desactiva ese comportamiento para TODA la página.
    if ($.fn && $.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype._enforceFocus = function() {};
    }
    $(document).off('focusin.modal');
});
</script>