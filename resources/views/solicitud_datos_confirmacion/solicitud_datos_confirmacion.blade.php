@extends(backpack_view('blank'))
@include('includes.select2_css')
@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row"> 
                <div class="col">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="lista1">Lista 1:</label>
                            <select class="form-control" id="filter1" name="filter1">
                                <option value="0">MSISDN</option>
                                <option value="1">DNI</option>
                                <option value="2">MSISDN Y DNI</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3" id="col-msisdn">
                            <label for="dniInput">MSISDN:</label>
                            <input type="text" name="filter2" class="form-control" id="msisdnInput" pattern="^51(\d+(,\d+)*)?$" placeholder="Ingrese un número inciando con 51 separados por comas" required>
                            <small class="form-text text-muted text-s-msisdn">El número debe empezar por 51 y separados por comas (por ejemplo, 51345678,51654321).</small>
                        </div>
                        <div class="form-group col-md-3" id="col-dni" style="display:none">
                            <label for="dniInput">DNI:</label>
                            <input type="text" name="filter3" class="form-control" id="dniInput" pattern="^\d{8}+(,\d{8}+)*?$" placeholder="Ingrese un número de 8 dígitos separados por comas">
                            <small class="form-text text-muted text-s-dni">El número debe tener 8 dígitos y separados por comas (por ejemplo, 12345678,87654321).</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lista2">Lista 2:</label>
                            <select class="form-control js-lista-multiple" id="filter4" name="filter4[]" multiple="multiple" required>
                                <option value="id_card_value">NUMERO_DE_DOCUMENTO</option>
                                <option value="id_card_type_value">TIPO_DE_DOCUMENTO</option>
                                <option value="customer_full_name">NOMBRE_CLIENTE</option>
                                <option value="agreement_mode">MODALIDAD_DEL_SERVICIO</option>
                                <option value="subscription_access_number">NUMERO_CONTRATADO</option>
                                <option value="agreement_status">ESTADO_DEL_SERVICIO</option>
                                <option value="subscription_start_date">FECHA_ALTA</option>
                                <option value="subscription_end_date">FECHA_BAJA</option>
                                <option value="subscription_status_date">FECHA_ULTIMO_CAMBIO_ESTADO</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-3" id="colSubmit">
                    <div class="form-group">
                        <button type="button" class="btn btn-danger btn-sm btn_view">Visualizar</button>
                        <button type="submit" class="btn btn-danger btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm" style="min-width: 1100px;">
                <thead>
                    <tr id="cabeceraTable">
                    </tr>
                </thead>
                <tbody id="bodyTable">
                </tbody>
            </table>
        </div>

    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.select2_js')
<script>
$(function() {
    const config = @json($config);

    $('.js-lista-multiple').select2();

    $(".btn_view").on('click', function(){
        if($('#filter4').val().length == 0 ){
            alert("Debe seleccionar un elemento de la lista");
            return false;
        }
        var formdata = $('#form_export').serializeArray();
        $.ajax({
          url: "{{ route('solicitud-datos-confirmacion.getTable') }}",
          type: 'GET',
          data: formdata,
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            console.log(data);
            var cabeceraTable = $("#cabeceraTable");
            var bodyTable = $("#bodyTable");
            cabeceraTable.empty();
            bodyTable.empty();
            if(data.length > 0){
                var cabeceras = Object.keys(data[0]);
                $.each(cabeceras, function(index,key){
                    cabeceraTable.append('<th>'+key+'</th>');
                });

                $.each(data, function(index,value){
                    bodyTable.append('<tr>');
                    $.each(value, function(index2,value2){
                        bodyTable.append('<td>'+value2+'</td>');
                    });
                    bodyTable.append('</tr>');
                });
            }
          },
          complete: function () {
              $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
        });
    });
    
    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        if($('#filter4').val().length == 0 ){
            e.preventDefault();
            alert("Debe seleccionar un elemento de la lista");
        }else{
            utils.downloadHandler({
                url: config.url,
                requestOptions: {headers: {accept: "application/json"}},
                errorCallback: async (error) => {
                    console.log(error);
                }
            }, e).then(function(data){
                //location.reload();
            });
        }
    });

    $("#filter1").on('change',function(){
        if($(this).val() == '0'){
            $("#col-msisdn").show();
            $("#msisdnInput").prop("required", true);
            $("#col-dni").hide(); 
            $("#dniInput").removeAttr("required"); 
            $("#dniInput").val("");
            $(".text-muted").show();         
        }
        if($(this).val() == '1'){
            $("#col-msisdn").hide();
            $("#msisdnInput").removeAttr("required"); 
            $("#msisdnInput").val("");
            $("#col-dni").show(); 
            $("#dniInput").prop("required", true);
            $(".text-muted").show();
        }
        if($(this).val() == '2'){
            $("#col-msisdn").show();
            $("#msisdnInput").prop("required", true);
            $("#col-dni").show(); 
            $("#dniInput").prop("required", true);
            $(".text-muted").hide();
        }
    });
});
</script>
@endsection