@extends(backpack_view('blank'))

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="upload_form">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="tipo_documento_id">Tipo Documento</label>
                        <select class="form-control form-control-sm" name="tipo_documento_id" id="tipo_documento_id">
                            <option value="">Seleccione</option>
                            @foreach ($config["tiposDocumento"] as $row)
                                <option value="{{ $row['id'] }}">{{ $row['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="form-group">
                        <label for="archivo">Archivo</label>
                        <input type="file" class="" name="documento" id="archivo" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 inputs-panel tipodocumento-2-inputs">
                    <div class="form-group">
                        <label for="imei">Imei</label>
                        <input type="number" class="form-control form-control-sm" name="imei" id="imei" required>
                    </div>
                </div>
            </div>
            <div class="row form-section-2">                
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Cargar</button>
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

    document.querySelector("#upload_form")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let formData = new FormData(e.target);
        utils.fetch(config.url, {
            headers: {
                "Accept": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            method: 'POST',
            body: formData
        })
        .then(async(resp) => {
            let ok = resp.ok;
            let response = await resp.json();
            if(ok){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: 'Se cargo correctamente'
                }).show();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.message
                }).show();
            }
        })
        .catch(error => {
            alert(error.message);
        });
    });

    function renderInputsByTipoDocumento(tipo_documento_id){
        $(".inputs-panel").hide();
        $(".inputs-panel input").prop("readonly", true);
        $(".tipodocumento-"+tipo_documento_id+"-inputs").show();
        $(".tipodocumento-"+tipo_documento_id+"-inputs input").prop("readonly", false);
        if(tipo_documento_id.toString() === "2"){
            $("input[type=file]").prop("accept", ".pdf");
        }else{
            $("input[type=file]").prop("accept", ".xlsx");
        }
    }


    $("#tipo_documento_id").on("change", function(e){
        renderInputsByTipoDocumento(e.target.value);
    });
    renderInputsByTipoDocumento($("#tipo_documento_id").val());

});
</script>
@endsection
