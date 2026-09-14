<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
$entradasalida = new entradasalida();
$res = $entradasalida->getinfogarantiaproveedorbyid(base64_decode($_GET['cpid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Garantía de Proveedor</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <div style="overflow-x:auto; width: 100%;">
                <table class="table table-bordered">
                    <tr>
                        <th width="200px">Folio</th>
                        <td><?=$res[0]['CADUCIDADPROV_FOLIO'];?> <span style="font-size:10px; color:#<?=$res[0]['STATUS_COLOR']?>">(<?=$res[0]['STATUS_NOMBRE']?>)</span></td>
                    </tr>
                    <tr>
                        <th>Fecha</th>
                        <td><?=$res[0]['CADUCIDADPROV_FECHA'];?></td>
                    </tr>
                    <tr>
                        <th>Proveedor</th>
                        <td><?=$res[0]['NOMBREPROVEEDOR']?> (<?=$res[0]['CORREOPROVEEDOR']?>)</td>
                    </tr>
                    <tr>
                        <th>Almacén</th>
                        <td><?=$res[0]['ALMACEN_NOMBRE'];?> (Sucursal:<?=$res[0]['SUCURSAL_NOMBRE'];?>)</td>
                    </tr>
                    <tr>
                        <th>Usuario</th>
                        <td><?=$res[0]['USUARIO_NOMBRE'];?></td>
                    </tr>
                </table>
            </div>
            <br><br>
            <h4>Detalle:<hr></h4>
            <div style="overflow-x:auto; width: 100%;">
                <table class="table table-bordered">
                    <tr>
                        <th></th> <!-- Nuevo header vacío para los checkboxes -->
                        <th>#</th>
                        <th>CVE</th>
                        <th>ARTICULO</th>
                        <th>LOTE</th>
                        <th>CADUCIDAD</th>
                        <th>REEMPLAZADO</th>
                    </tr>
                    <?php foreach ($res as $index => $re) { ?>
                        <?php 
                            $contador = $index + 1; 

                            $icono = '';
                            if ($re['CADUCIDADPROVDET_REEMPLAZADO'] == 1) {
                                $icono = '<span style="font-size:14pt; color:#007bff;">&#10003;</span>';
                            }

                            $checkbox = '';
                            if ($re['STOCK_STOCKSTATUSID'] == 1) {
                                $checkbox = '<input type="checkbox" class="articuloCheckbox" name="articulos[]" value="'.$re['CADUCIDADPROVDET_ID'].'">';
                            }
                        ?>
                        <tr>
                            <td><?=$checkbox?></td>
                            <td><?=$contador?></td>
                            <td><?=$re['CLAVE_ARTICULO']?></td>
                            <td><?=$re['ARTICULO_NOMBRE']?></td>
                            <td><?=$re['STOCK_LOTE']?></td>
                            <td><?=$re['STOCK_CADUCIDAD']?></td>
                            <td><?=$icono?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <br>
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="invalmacen">Almacén de Entrada<span class="text-danger">*</span></label>
                        <select class="form-control" id="invalmacen" name="invalmacen"></select>
                    </div>
                </div>
            </div>
            <br>
            <button id="procesarSeleccion" class="btn btn-success">Procesar Selección</button>
        </div>
    </div>
</div>
<script>

    $(document).ready(function(){
        //Get Almacenes
        var items2="";
        $.getJSON("../ajax/get.almacenes.catalogo.php?tipoalmacen=1",function(data){
            items2+="<option value=''></option>";
            $.each(data,function(index,item){
                items2+="<option value='"+item.ID+"'>"+item.NOMBRE+"</option>";
            });
            $("#invalmacen").html(items2);
        });
    });

    $("#procesarSeleccion").click(function() {

        // Validar que al menos un artículo esté seleccionado
        let seleccionados = [];
        $(".articuloCheckbox:checked").each(function() {
            seleccionados.push($(this).val());
        });

        if (seleccionados.length === 0) {
            Swal.fire({
                html: "Debes seleccionar al menos un artículo.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
            return;
        }

        // Validar que el almacén esté seleccionado
        if ($("#invalmacen").val() === "") {
            Swal.fire({
                html: "Debes seleccionar un almacén de entrada.",
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success'
                },
                didClose: () => { $('#invalmacen').focus(); }
            });
            return;
        }

        // Envía los IDs seleccionados al archivo PHP (ajusta el nombre del archivo según tu lógica)
        $.ajax({
            url: '../ajax/caducidadproveedor.reposicion.php',
            type: 'POST',
            data: { 
                cpid: '<?=$res[0]['CADUCIDADPROV_ID']?>',
                proveedorid: '<?=$res[0]['CADUCIDADPROV_PROVID']?>',
                proveedorcorreo: '<?=$res[0]['CADUCIDADPROV_PROVCORREO']?>',
                almacenid: $("#invalmacen").val(),
                articulos: seleccionados 
            },
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(response) {
                if (response.trim() === ""){
                    Swal.fire({
                        html: "Solicitud de " + etiqueta + " " + done + " con éxito",
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
            complete: function() {
                $("#loading").hide();
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud:', error);
            }
        });
    });

</script>