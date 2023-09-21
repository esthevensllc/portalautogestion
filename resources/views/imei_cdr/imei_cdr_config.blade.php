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
                        <label for="">Generar Reporte Cada Hora</label>
                        <select class="form-control form-control-sm" name="generate_report">
                            <option value="0">NO</option>
                            <option value="1">SI</option>
                        <select>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Guardar</button>
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

    if(config.config !== null){
        document.querySelector("select[name=generate_report]").value = config.config.generate_report;
    }

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const data = new FormData(e.target);
        utils.fetch(config.url, {
            method: 'POST',
            body: data,
            headers: {"Accept": "application/json"}
        })
        .then(resp => {
            if(resp.ok){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: 'Se actualizo correctamente'
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Ocurrió un error al eliminar el reporte'
                }).show();
            }
        });
    });
});
</script>
@endsection
