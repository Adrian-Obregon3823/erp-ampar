<?php include_once("../includes/head.php");?>
<div class="col-12">
    <div class="card">        
        <div class="card-body">
        <form class="forms-sample">
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                    <label for="articulonom">Nombre de Artículo</label>
                    <input type="text" class="form-control" id="articulonom" name="articulonom" placeholder="Nombre de Artículo">
                    </div>
                    <div class="form-group">
                    <label for="exampleInputEmail1">Línea del artículo</label>
                    <select class="form-control" id="linea" name="linea"></select>
                    </div>
                    <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="umedida">Unidad de Medida</label>
                            <select class="form-control" id="umedida" name="umedida"></select>
                        </div>
                        <div class="col-6">
                            <label for="ucompra">Unidad de Compra</label>
                            <input type="text" class="form-control" id="ucompra" name="ucompra">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <label for="contenido">Contenido</label>
                            <select class="form-control" id="contenido" name="contenido" value="1"></select>
                        </div>
                        <div class="col-4">
                            <label for="cvesat">Clave SAT</label>
                            <input type="text" class="form-control" id="cvesat" name="cvesat">
                        </div>
                    </div>
                    </div>
                    <button type="button" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>  
        </div> 
    </div>
</div>
<?php include_once("../includes/foot.php");?>
<script>

    $(document).ready(function(){

        //Get Linea de Artículos
        var items1="";
        $.getJSON("../ajax/get.lineaarticulos.php",function(data){
            items1+="<option value=''></option>";
            $.each(data,function(index,item){
                items1+="<option value='"+item.ID+"'>"+item.NOMBRE+"</option>";
            });
            $("#linea").html(items1);
        });

        //Get unidad de medida
        var items2="";
        $.getJSON("../ajax/get.unidadmedida.php",function(data){
            items2+="<option value=''></option>";
            $.each(data,function(index,item){
                items2+="<option value='"+item.NOMBRE+"'>"+item.NOMBRE+"</option>";
            });
            $("#umedida").html(items2);
        });

    });

    $("#umedida").change(function(){
        $("#ucompra").val($("#umedida").val());
    });

    function guardar(){
        if (confirm('Seguro que deseas guardar el almacén?')){
            if ($("#almacennom").val() == ""){
                alert ("Nombre de almacén es un campo obligatorio");
                $("#almacennom").focus();
            }else if ($("#almacenabreviadonom").val() == ""){
                alert ("Nombre Abreviado de almacén es un campo obligatorio");
                $("#almacenabreviadonom").focus();
            }else{
                var formData = new FormData(document.getElementById("form-nuevo-almacen"));
                $.ajax({
                    url: '../ajax/almacenes.nuevo.php',
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
                            alert ("Almacén guardado con éxito");
                        }else{
                            alert (response);
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
    }

</script>