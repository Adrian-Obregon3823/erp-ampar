<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
$maletas = new maletas();
$res = $maletas->getinfomaleta(base64_decode($_GET['maletaid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Editar Maleta</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-maleta">
                <input type="hidden" class="form-control" id="maeditmaletaid" name="maeditmaletaid" value="<?=$res[0]['ALMACEN_ID']?>">
                <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="maedittipoalmacen">Tipo de Almacén <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="maedittipoalmacen" name="maedittipoalmacen" value="<?=$res[0]['PADRE_TIPOALMACEN_NOMBRE']?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="maeditalmacen">Almacén <span class="text-danger">*</span></label>
                        <input type="hidden" class="form-control" id="maeditsucalmacen" name="maeditsucalmacen" value="<?=$res[0]['ALMACEN_ALMACEN_MS']?>">
                        <input type="text" class="form-control" id="maeditalmacennombre" name="maeditalmacennombre" value="<?=$res[0]['PADRE_ALMACEN_NOMBRE']?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="maeditfamilia">Familia <span class="text-danger">*</span></label>
                        <select class="form-control" id="maeditfamilia" name="maeditfamilia"></select>
                    </div>
                    <div class="form-group">
                        <label for="maeditdivision">División <span class="text-danger">*</span></label>
                        <select class="form-control" id="maeditdivision" name="maeditdivision"></select>
                    </div>
                    <div class="form-group">
                        <label for="maedittipomaleta">Tipo de Maleta <span class="text-danger">*</span></label>
                        <select class="form-control" id="maedittipomaleta" name="maedittipomaleta"></select>
                    </div>
                    <div class="form-group">
                        <label for="maeditmaletanom">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="maeditmaletanom" name="maeditmaletanom" placeholder="Nombre de Maleta" maxlength="50" value="<?=$res[0]['ALMACEN_NOMBRE']?>">
                    </div>
                    <div class="form-group">
                        <label for="maeditmaletades">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="maeditmaletades" name="maeditmaletades" placeholder="Descripción de Maleta" maxlength="250"><?=$res[0]['ALMACEN_DESCRIPCION']?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="maeditstatus">Status</label>
                                <select class="form-control" id="maeditstatus" name="maeditstatus">
                                    <option value="17">Activo</option>
                                    <option value="18">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success" onclick="editarmaleta();">Modificar</button>
                </div>
                </div>
            </form>  
        </div>
    </div>
</div>
<script>

    $(document).ready(function(){

        $("#maeditstatus").val('<?=$res[0]['ALMACEN_STATUS']?>');

        // Get Familias y setear valor
        var itemsmaeditfamilia = "<option value='0'></option>";
        $.getJSON("../ajax/get.familias.php", function(data){
            $.each(data, function(index, item){
                var selected = (String(item.ID) === '<?=$res[0]['DIVISION_FAMILIAID']?>') ? ' selected' : '';
                itemsmaeditfamilia += "<option value='"+item.ID+"'"+selected+">"+item.NOMBRE+"</option>";
            });
            $("#maeditfamilia").html(itemsmaeditfamilia);
        });

        // Get Divisiones y setear valor
        var itemsmaeditdiv = "<option value='0'></option>";
        $.getJSON("../ajax/get.divisiones.php?familiaid=<?=$res[0]['DIVISION_FAMILIAID']?>", function(data){
            if (data && data[0] && data[0].ID !== "") {
                $.each(data, function(index, item){
                    var selected = (String(item.ID) === '<?=$res[0]['TIPOMALETA_DIVISIONID']?>') ? ' selected' : '';
                    itemsmaeditdiv += "<option value='"+item.ID+"'"+selected+">"+item.NOMBRE+"</option>";
                });
                $("#maeditdivision").html(itemsmaeditdiv).prop("disabled", false);
            } else {
                $("#maeditdivision").html("<option value='0'></option>").prop("disabled", true);
            }
        });

        // Get Tipo Maleta y setear valor
        var itemsmaedittipo = "<option value='0'></option>";
        $.getJSON("../ajax/get.catalogotipomaleta.php?divisionid=<?=$res[0]['TIPOMALETA_DIVISIONID']?>", function(data){
            let tipoMaletasValidas = data.filter(m => m.ID && m.NOMBRE);
            if (tipoMaletasValidas.length > 0) {
                $.each(data, function(index, item){
                    var selected = (String(item.ID) === '<?=$res[0]['ALMACEN_TIPOMALETAID']?>') ? ' selected' : '';
                    itemsmaedittipo += "<option value='"+item.ID+"'"+selected+">"+item.NOMBRE+"</option>";
                });
                $("#maedittipomaleta").html(itemsmaedittipo).prop("disabled", false);
            } else {
                $("#maedittipomaleta").html("<option value='0'></option>").prop("disabled", true);
            }
        });

    });

    $("#maeditfamilia").change(function(){
        var familiaid = $(this).val();
        var itemsmadivision = "<option value='0'></option>";
        if (familiaid == 0 || !familiaid) {
            $("#maeditdivision").html("<option value='0'></option>").prop("disabled", true);
            $("#maedittipomaleta").html("<option value='0'></option>").prop("disabled", true);
            return;
        }
        $.getJSON("../ajax/get.divisiones.php?familiaid=" + familiaid, function(data){
            if (data && data[0] && data[0].ID !== "") {
                $.each(data, function(index, item){
                    itemsmadivision += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                });
                $("#maeditdivision").html(itemsmadivision).prop("disabled", false);
            } else {
                $("#maeditdivision").html("<option value='0'>Sin divisiones</option>").prop("disabled", true);
            }
            $("#maedittipomaleta").html("<option value='0'></option>").prop("disabled", true);
        });
    });

    $("#maeditdivision").change(function(){
        var divisionid = $(this).val();
        var itemsmatipomaletas = "<option value='0'></option>";
        if (divisionid == 0 || !divisionid) {
            $("#maedittipomaleta").html("<option value='0'></option>").prop("disabled", true);
            return;
        }
        $.getJSON("../ajax/get.catalogotipomaleta.php?divisionid=" + divisionid, function(data){
            let tipoMaletasValidas = data.filter(m => m.ID && m.NOMBRE);
            if (tipoMaletasValidas.length > 0) {
                $.each(data, function(index, item){
                    itemsmatipomaletas += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                });
                $("#maedittipomaleta").html(itemsmatipomaletas).prop("disabled", false);
            } else {
                $("#maedittipomaleta").html("<option value='0'>Sin tipos de maleta</option>").prop("disabled", true);
            }
        });
    });

    function editarmaleta(){
        Swal.fire({
            text: '¿Seguro que deseas modificar la información de la maleta?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, modificar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#maeditmaletaid").val() == ""){
                    Swal.fire({
                        html: "ID de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#maeditmaletaid').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                }else if ($("#maeditsucalmacen").val() == ""){
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#maeditsucalmacen').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                }else if ($("#maeditfamilia").val() == 0 || !$("#maeditfamilia").val()){
                    Swal.fire({
                        html: "Familia es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#maeditfamilia').focus();
                        }
                    });
                }else if ($("#maeditdivision").val() == 0 || !$("#maeditdivision").val()){
                    Swal.fire({
                        html: "División es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#maeditdivision').focus();
                        }
                    });
                }else if ($("#maedittipomaleta").val() == 0 || !$("#maedittipomaleta").val()){
                    Swal.fire({
                        html: "Tipo de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#maedittipomaleta').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                }else if ($("#maeditmaletanom").val() == ""){
                    Swal.fire({
                        html: "Nombre de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#maeditmaletanom').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                }else if ($("#maeditmaletades").val() == ""){
                    Swal.fire({
                        html: "Descripción de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#maeditmaletades').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                }else{
                    var formData = new FormData(document.getElementById("form-editar-maleta"));
                    $.ajax({
                        url: '../ajax/maletas.editar.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'html',
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $("#loading").show();
                        },
                        success: function(response) {
                            if (response.trim() === ""){
                                Swal.fire({
                                    html: "Maleta modificada con éxito",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            }else{
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud:', error);
                        },
                        complete: function(data) {
                            $("#loading").hide();
                        }
                    });
                }
            }else{
                return false;
            }
        });
    }

</script>