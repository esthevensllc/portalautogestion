@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row"> 
                <div class="col-sm-6">           
                    <!-- <div class="form-group">
                       <div class="form-check-inline">
                            <label class="form-check-label" for="granularidad">Granularidad:</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <select class="form-control" id="selectGranu" name="selectGranu">
                                <option value="0">Departamento</option>
                                <option value="1">Provincia</option>
                                <option value="2">Distrito</option>
                            </select>
                        </div>
                    </div> -->
                    <!-- Botón desplegable -->
                    <div class="dropright">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Granularidad
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <!-- Lista de inputs de tipo radio -->
                            <div class="form-check ml-2">
                                <input class="form-check-input" type="radio" name="selectGranu" id="option1" value="0">
                                <label class="form-check-label" for="option1">
                                    Departamento
                                </label>
                            </div>
                            <div class="form-check ml-2">
                                <input class="form-check-input" type="radio" name="selectGranu" id="option2" value="1">
                                <label class="form-check-label" for="option2">
                                    Provincia
                                </label>
                            </div>
                            <div class="form-check ml-2">
                                <input class="form-check-input" type="radio" name="selectGranu" id="option3" value="2">
                                <label class="form-check-label" for="option3">
                                    Distrito
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-2 mb-4"  id="colDep" style="display: none;">
                    <label for="selectDep">Departamento:</label>
                    <select class="form-control" id="selectDep" name="selectDep" required>
                        <option value="0">Seleccione</option>
                        <?php
                        foreach ($departamentos as $departamento){
                            if($departamento['departamento'] != "" && $departamento['departamento'] != "NULL"){
                                echo "<option value='".$departamento['departamento']."'>".$departamento['departamento']."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-sm-2" id="colProv" style="display: none;">
                    <label for="selectProv">Provincia:</label>
                    <select class="form-control" id="selectProv" name="selectProv" disabled>
                        <option value="0">Seleccione</option>
                    </select>
                </div>
                <div class="col-sm-2" id="colDist" style="display: none;">
                    <label for="selectDist">Distrito:</label>
                    <select class="form-control" id="selectDist" name="selectDist" disabled>
                        <option value="0">Seleccione</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3" id="colSubmit" style="display: none;" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);
    
    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        if($('input[name="selectGranu"]:checked').val() == '0' && $("#selectDep").val() == '0'){
            e.preventDefault();
            alert("Debe seleccionar un departamento");
        }else{
            if($('input[name="selectGranu"]:checked').val() == '1' && $("#selectProv").val() == '0'){
                e.preventDefault();
                alert("Debe seleccionar una provincia");
            }else{
                if($('input[name="selectGranu"]:checked').val() == '2' && $("#selectDist").val() == '0'){
                    e.preventDefault();
                    alert("Debe seleccionar un distrito");
                }else{
                    utils.downloadHandler({
                        url: config.url
                    }, e);
                }
            }
        }
    });

    $('input[name="selectGranu"]').on('change',function(){
        if($(this).val() == '0'){
            $("#colDep").show();
            $("#colProv").hide();
            $("#colDist").hide();
            $("#colSubmit").show();            
        }
        if($(this).val() == '1'){
            $("#colDep").show();
            $("#colProv").show();
            $("#colDist").hide();
            $("#colSubmit").show();
        }
        if($(this).val() == '2'){
            $("#colDep").show();
            $("#colProv").show();
            $("#colDist").show();
            $("#colSubmit").show();
        }
    });

    $("#selectDep").change(function () {
        changeDpto();
    });

    $("#selectProv").change(function () {
        changeProv();
    });

    function changeDpto() {
      var CodeUbgDep = $("#selectDep").val();
      $("#selectDist").html("<option selected value='0'>Seleccione</option>");
      //var filtTypeMap = $(".slc-mapGran").val();
      if(CodeUbgDep!='0'){
        $.ajax({
          url: "{{ asset('bases-clientes-prepago/getUbigeo') }}",
          type: 'GET',
          dataType: 'json',
          data: "param1="+CodeUbgDep+"&type=selectDep",
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            var list = "<option selected value='0'>seleccione</option>"
            $.each(data,function (x,y) {
              list +="<option value='"+y.provincia+"'>"+y.provincia+"</option>";              
            })
            $("#selectProv").html(list);
            $("#selectProv").removeAttr("disabled")
          },
          complete: function () {
              //reset();
              //$("#colProv").show();
              $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
        });

        // if(filtTypeMap=="555"){
          //fillColorByUbg(CodeUbgDep,1);
        // }
      }else{
        //fillColorByUbgInit();
        //reset();
        $("#selectProv").html("<option selected value='0'>Seleccione</option>");
        $("#selectDist").html("<option selected value='0'>Seleccione</option>");
        //$("#colProv").hide();
        //$("#colDist").hide();
        //fillColorByUbg('',0,$(".slc-crit").val());
      }
    }

    function changeProv() {
      var CodeUbgDep = $("#selectDep").val();
      var CodeUbgProv = $("#selectProv").val();

      if(CodeUbgProv!='0'){
        $.ajax({
          url: "{{ asset('bases-clientes-prepago/getUbigeo') }}",
          type: 'GET',
          dataType: 'json',
          data: "param1="+CodeUbgDep+"&param2="+CodeUbgProv+"&type=selectProv",
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            var list = "<option selected value='0'>seleccione</option>"
            $.each(data,function (x,y) {
              list +="<option value='"+y.distrito+"'>"+y.distrito+"</option>";              
            })
            $("#selectDist").html(list);
            $("#selectDist").removeAttr("disabled")
          },
          complete: function () {
              //reset();
              //$("#colDist").show();
              $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
        });


      }else{
        //reset();
        $("#selectDist").html("<option selected value='0'>Seleccione</option>");
        //$("#colDist").hide();
      }
    }

});
</script>
@endsection