@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@endsection

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
                        <select id="tabs_select" class="form-control form-control-sm" name="tipo_input">
                            <option value="tab_macs">INGRESAR MACS</option>
                            <option value="tab_excel">EXCEL</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_macs">
                        <label for="">MAC</label>
                        <textarea class="form-control form-control-sm" name="mac" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ingrese valores separados por comas<br>
                        </div>
                    </div>
                    <div class="form-group tab-item tab_excel">
                        <label for="">Excel</label>
                        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        <div class="invalid-feedback d-block text-dark">
                            Subir en archivo con formato xlsx y sin cabeceras<br>
                            Ingresar las macs en la primera columna<br>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group tab-item">
                        <label for="">Fecha</label>
                        <input
                            type="date"
                            class="form-control form-control-sm slc-list-values"
                            name="fecha"
                            min="{{ $config["summary"]['fecha_min'] }}"
                            max="{{ $config["summary"]['fecha_max'] }}"
                            required>
                    </div>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4">
                    <div class="">
                        <label id="fecha_min" class="d-block">Fecha mínima: {{ $config["summary"]['fecha_min'] }}</label>
                        <label id="fecha_maxima" class="d-block">Fecha máxima: {{ $config["summary"]['fecha_max'] }}</label>
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
@include('includes.select2_js')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

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

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {accept: "application/json"}}
        }, e);
    });
});
</script>
@endsection