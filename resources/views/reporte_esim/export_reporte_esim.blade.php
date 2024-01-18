@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Nintex</label>
                        <input type="text" class="form-control form-control-sm" name="nintex" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Archivo</label>
                        <input type="file" accept=".csv" name="archivo" required>
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
let config = @json($config);
document.querySelector("form")
.addEventListener("submit", function(e){
    utils.downloadHandler({
        url: config.url_export,
        requestOptions: {
            headers: {"Accept": "application/json"}
        }
    }, e);
});
</script>
@endsection