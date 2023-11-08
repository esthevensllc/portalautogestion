@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4 form-group tab-item">
                    <label for="">Número de cuenta</label>
                    <input type="text" class="form-control form-control-sm" name="num_cuenta" required>
                    <div class="invalid-feedback d-block text-dark">
                        Ej. 8.22104233.00.00.100000, 8.21527968.00.00.100000
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
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {accept: "application/json"}}
        }, e);
    });
});
</script>
@endsection