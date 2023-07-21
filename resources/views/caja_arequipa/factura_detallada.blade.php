@extends(backpack_view('blank'))

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Número de cuenta</label>
                        <textarea class="form-control form-control-sm" name="num_cuenta" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000, 8.21527968.00.00.100000
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Periodo</label>
                        <input type="text" class="form-control form-control-sm" name="periodo" placeholder="YYYYMM" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 202208,202209
                        </div>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
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
            url: config.url,
            requestOptions: {headers: {"Accept": "application/json"}}
        }, e);
    });
});
</script>
@endsection
