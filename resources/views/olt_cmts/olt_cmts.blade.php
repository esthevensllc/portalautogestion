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
                        <select id="type_id" class="form-control form-control-sm slc-list-values" name="type_id" required>
                            <option value="">Seleccione</option>
                            @foreach ($config["types"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group tab-item">
                        <label for="">Fecha</label>
                        <input
                            type="date"
                            class="form-control form-control-sm slc-list-values"
                            name="fecha"
                            min="{{ $config["oltSummary"]->fecha_min }}"
                            max="{{ $config["oltSummary"]->fecha_max }}"
                            required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Values</label>
                        <select id="slc-values" class="form-control form-control-sm" name="values[]" multiple required>
                            @foreach ($config["values"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4">
                    <div class="">
                        <label id="fecha_min" class="d-block">Fecha mínima: {{ $config["oltSummary"]->fecha_min }}</label>
                        <label id="fecha_maxima" class="d-block">Fecha máxima: {{ $config["oltSummary"]->fecha_max }}</label>
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

    $("#slc-values").select2({width: '100%'});

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {accept: "application/json"}}
        }, e);
    });

    $(".slc-list-values").on("change", function(){
        let type_id = $("select[name=type_id]").val();
        let fecha = $("input[name=fecha]").val();
        if(type_id === "" || fecha === ""){
            $("#slc-values").html(``);
            $("#slc-values").select2({width: '100%'});
            return;
        }
        utils.fetch(`${config.findValuesApi}?type_id=${type_id}&fecha=${fecha}`)
        .then(resp => resp.json())
        .then(resp => {
            let html = resp.data.map(row => `<option value="${row.id}">${row.label}</option>`).join("");
            $("#slc-values").html(`<option value="all">Todos</option>${html}`);
            $("#slc-values").select2({width: '100%'});
        })
        .catch(error => {
            $("#slc-values").html(``);
            $("#slc-values").select2({width: '100%'});
        });
    });
});
</script>
@endsection