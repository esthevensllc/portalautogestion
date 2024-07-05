@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_import">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Archivo</label>
                    <input type="file" class="d-block" name="archivo" accept=".xlsx,.xls,.csv,.txt" required>
                    <div class="invalid-feedback d-block text-dark">
                        El archivo de estar sin cabeceras.
                        <br>Debe tener tres valores(imei, imsi, linea) y tener un formato xlsx, xls, csv o txt.
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Ejecutar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div id="result"></div>
            </div>
        </div>
    </div>
</div>

@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.datatables_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_import")
    .addEventListener("submit", async function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';
        let formData = new FormData(e.target);
        await fetch(`${config.url}`, {
            method: "POST",
            headers: {
                "Accept": "application/json"
            },
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            if(response.passes === true){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Se ejecutó correctamente"
                }).show();
                window.location.href = '/portalautogestion/storage/' + response.data.file;
                resultado = response.data.notFound;
                if(resultado[0]['total'] == '0'){
                    $("#result").html('Se ubico el total de lineas');
                }else{
                    $("#result").html('No se ubico '+resultado[0]['total']+' lineas');
                }                
            }else if(response.errors !== null){
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.errors.message
                }).show();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: "Ocurrio un error no identificado"
                }).show();
            }
        });
        loader_component.style.display = 'none';
    });

    /*document.querySelector("#btn_export")
    .addEventListener("click", async function(e){
        await _datatable.ajax.reload();
        let event = {target: document.querySelector("#form_search")};
        await utils.downloadHandler({
            url: config.exportApi,
            requestOptions: {headers: {accept: "application/json"}},
        }, event);
        e.target.innerHTML = "Exportar";
    });*/
});
</script>
@endsection