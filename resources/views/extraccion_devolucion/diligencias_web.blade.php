@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Ticket</label>
                    <input type="text" class="form-control form-control-sm" name="ticket" required>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="btn-group" role="group">
                        <label for="rep_usuarios_afectados" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_usuarios_afectados" name="tipo_reporte" value="1" required>
                            Correo
                        </label>
                        <label for="rep_postpago" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_postpago" name="tipo_reporte" value="2" required>
                            Documento
                        </label>
                        <label for="rep_doc_correo" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_doc_correo" name="tipo_reporte" value="3" required>
                            Documento y Correo
                        </label>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
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
@include('includes.select2_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        utils.downloadHandler({
            url: config.exportApi,
            requestOptions: {
                headers: {
                    "Accept": "application/json"
                }
            }
        }, e);
    });

    document.querySelector(".btn-group")
    .addEventListener("click", function(e){
        let element;
        if(e.target.tagName === 'LABEL'){
            element = e.target;
        }else if(e.target.tagName === 'INPUT'){
            element = e.target.parentElement;
        }

        if(element){
            console.log(element.tagName);
            $(".btn-group .btn")
            .removeClass("btn-secondary")
            .removeClass("btn-light")
            .removeClass("active")
            .addClass("btn-light");

            $(element)
            .removeClass("btn-light")
            .addClass("btn-secondary")
            .addClass("active");
        }
    });
});
</script>
@endsection