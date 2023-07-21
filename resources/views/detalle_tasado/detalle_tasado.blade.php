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
                        <label for="">Números de cuenta</label>
                        <input type="text" class="form-control form-control-sm" name="numero_cuenta" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000, 8.21527968.00.00.100000
                        </div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
                            <option value="1">Fecha unica</option>
                            <option value="2">Rango de fechas</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="row">
                        <div class="col-lg-12 tab-item 1">
                            <div class="form-group">
                                <label for="">Fecha</label>
                                <input type="date" class="form-control form-control-sm" name="fecha" required>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group tab-item 2">
                            <label for="">Fecha Inicio</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_ini" required>
                        </div>
                        <div class="col-lg-12 form-group tab-item 2">
                            <label for="">Fecha Fin</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_fin" required>
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
        utils.downloadHandler({
            url: config.url
        }, e);
    });

    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item").hide();
        $(".tabs .tab-item."+$("#tabs_select").val()).show();
    });
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item input").prop('disabled', true);
        $(".tabs .tab-item textarea").prop('disabled', true);
        $(".tabs .tab-item."+$("#tabs_select").val()+" input").prop('disabled', false);
        $(".tabs .tab-item."+$("#tabs_select").val()+" textarea").prop('disabled', false);
    });
    $("#tabs_select").trigger('change');

    let fecha1 = document.querySelector("input[name=fecha_ini]");
    let fecha2 = document.querySelector("input[name=fecha_fin]");
    fecha1.addEventListener("change", function(e){
        fecha2.min = e.target.value;
        if(e.target.value !== ''){
            let _date = new Date(e.target.value+"T00:00:00");
            _date.setMonth(_date.getMonth()+12);
            fecha2.max = _date.toISOString().substring(0, 10);
        }else{
            fecha2.max = '';
        }
    });
    fecha2.addEventListener("change", function(e){
        fecha1.max = e.target.value;

        if(e.target.value !== ''){
            let _date = new Date(e.target.value+"T00:00:00");
            _date.setMonth(_date.getMonth()-12);
            fecha1.min = _date.toISOString().substring(0, 10);
        }else{
            fecha1.min = '';
        }
    });

});
</script>
@endsection