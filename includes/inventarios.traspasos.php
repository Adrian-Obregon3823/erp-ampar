<?php include_once("../includes/head.php");?>
<!DOCTYPE html>
<html lang="es">
<div class="container-fluid mt-4">
        <h2>Traspaso de Artículos entre Almacenes</h2><br><br>
        <div class="row">
            <!-- Almacén de origen -->
            <div class="col-md-5">
                <h4>Sucursal de Origen</h4>
                <select class="form-control mb-3" id="sucursal_origen">
                </select>
                <h4>Almacén de Origen</h4>
                <select class="form-control mb-3" id ="almacen_origen">
                </select>
                <h5>Artículos Disponibles</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Laptop Dell <span class="badge bg-success">10</span>
                        <button class="btn btn-info btn-sm">Seleccionar</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Monitor LG <span class="badge bg-success">5</span>
                        <button class="btn btn-info btn-sm">Seleccionar</button>
                    </li>
                </ul>
            </div>

            <!-- Control de traspaso -->
            <div class="col-md-2 d-flex align-items-center justify-content-center">
                <button class="btn btn-warning">Traspasar <i class="mdi mdi-arrow-right"></i></button>
            </div>

            <!-- Almacén de destino -->
            <div class="col-md-5">
                <h4>Sucursal de Destino</h4>
                <select class="form-control mb-3" id="sucursal_destino">
                </select>
                <h4>Almacén de Destino</h4>
                <select class="form-control mb-3" id="almacen_destino">
                </select>
                <h5>Artículos a Traspasar</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No hay artículos seleccionados
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>
<script>
    $(document).ready(function(){

        //Get Sucursal
        var items1="";
        $.getJSON("../ajax/get.sucursales.php",function(data){
            items1+="<option value=''></option>";
            $.each(data,function(index,item){
                items1+="<option value='"+item.ID+"'>"+item.NOMBRE+"</option>";
            });
            $("#sucursal_origen").html(items1);
            $("#sucursal_destino").html(items1);
        });

    });

    $("#sucursal_origen").change(function(){
        //Get Almacenes
        var items4="";
        $.getJSON("../ajax/get.almacenes.catalogo.php?sucursalid="+$("#sucursal_origen").val(),function(data){
            items4+="<option value=''></option>";
            $.each(data,function(index,item){
                items4+="<option value='"+item.ID+"'>"+item.NOMBRE+"</option>";
            });
            $("#almacen_origen").html(items4);
        });
    });

    $("#sucursal_destino").change(function(){
        //Get Almacenes
        var items5="";
        $.getJSON("../ajax/get.almacenes.catalogo.php?sucursalid="+$("#sucursal_destino").val(),function(data){
            items5+="<option value=''></option>";
            $.each(data,function(index,item){
                items5+="<option value='"+item.ID+"'>"+item.NOMBRE+"</option>";
            });
            $("#almacen_destino").html(items5);
        });
    });
</script>