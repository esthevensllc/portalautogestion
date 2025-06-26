@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="form-group col-12">
                    <label for="">Csv</label>
                    <input type="file" class="d-block" name="excel" accept=".csv,.txt">
                </div>
                <div class="form-group col-12">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Exportar</button>
                </div>
                <div class="text-danger">
                    *El archivo csv o txt que se sube no debe superar el 1,000,000 de filas
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