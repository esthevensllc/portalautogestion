@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select id="tabs_select" class="form-control form-control-sm" name="tipo_input_id">
                            @foreach ($config["tiposInput"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_1">
                        <label for="">Número de cuenta</label>
                        <input type="text" class="form-control form-control-sm" name="num_cuenta" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000, 8.21527968.00.00.100000
                        </div>
                    </div>
                    <div class="form-group tab-item tab_2">
                        <label for="">Excel</label>
                        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        <div class="invalid-feedback d-block text-dark">
                            Subir en archivo con formato xlsx sin cabeceras<br>
                            Ingresar las lineas en la primera columna sin el codigo 51<br>
                            Ej. 947123456
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Periodo</label>
                        <input type="number" class="form-control form-control-sm" name="periodo" placeholder="YYYYMM" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 202310
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
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

    $("#form_export").on('submit', function(e){
        e.preventDefault();

        let cellExpression = /^[0-9,]+$/;
        if($("#tabs_select").val() === "3" && !$("*[name=lineas]").val().match(cellExpression)){
            alert("Las lineas solo pueden contener numeros separados por comas");
            return;
        }
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {accept: "application/json"}}
        }, e);
    });
    
    //$(".tabs .tab-item").hide();
    //$(".tabs .tab-item."+$("#tabs_select").val()).show();
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item").hide();
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()).show();
    });
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item input").prop('disabled', true);
        $(".tabs .tab-item textarea").prop('disabled', true);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" input").prop('disabled', false);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" textarea").prop('disabled', false);
    });
    $("#tabs_select").trigger('change');

});
</script>
@endsection