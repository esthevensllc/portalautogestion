@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="form-group col-lg-2">
                    <label for="">Año</label>
                    <input type="number" class="form-control form-control-sm" name="anio" min="0" max="9999">
                </div>
                <div class="form-group col-lg-2">
                    <label for="">Mes</label>
                    <input type="number" class="form-control form-control-sm" name="mes" min="0" max="12">
                </div>
                <div class="form-group col-12">
                    <label for="">Excel</label>
                    <input type="file" class="d-block" name="excel">
                </div>
                <div class="form-group col-12">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Exportar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection


@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {"Accept": "application/json"}}
        }, e);
    });
});
</script>
@endsection