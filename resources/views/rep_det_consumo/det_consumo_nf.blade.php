@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
                            <option value="1">Número de cuenta</option>
                            <option value="2">Excel de Lineas</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 tabs">
                    <div class="row">
                        <div class="col-lg-12 tab-item 1">
                            <div class="form-group">
                                <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Número de cuenta</label>
                                <input type="text" class="form-control form-control-sm" name="num_cuenta" required>
                                <div class="invalid-feedback d-block text-dark">
                                    Ej. 8.22104233.00.00.100000,8.21527968.00.00.100000
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 tab-item 2">
                            <div class="form-group">
                                <label for="">Excel</label>
                                <input type="file" class="" name="excel" required>
                                <div class="invalid-feedback d-block text-dark">
                                    Subir en archivo con formato xlsx y sin cabeceras
                                    Ingresar las lineas en la primera columna y anteponer el codigo 51
                                    Ej. 51947123456
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="row">
                        <div class="col-lg-12 form-group">
                            <label for="">Fecha Inicio</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_ini" required>
                        </div>
                        <div class="col-lg-12 form-group">
                            <label for="">Fecha Fin</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_fin" required>
                        </div>
                    </div>
                </div>
                
                <div class="col-12" style="display: flex; align-items: end;">
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

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        utils.downloadHandler({
            url: config.url
        }, e);
    });
</script>
@endsection
