@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$data['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Número de cuenta</label>
                        <input type="text" class="form-control form-control-sm" name="cod_cliente" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000,8.21527968.00.00.100000
                        </div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Periodo</label>
                        <input type="text" class="form-control form-control-sm" name="periodo" placeholder="YYYYMM" required>
                        <div class="invalid-feedback d-block text-dark">
                            Para Ingresar mas de un periodo separar por comas<br>
                            Ej. 202208,202209
                        </div>
                    </div>
                </div>
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
<script>
$(function() {
    const config = @json($data);
    const downloadFile = (blob, fileName) => {
        const url = window.URL.createObjectURL(new Blob([blob]))
        const link = document.createElement('a')
        link.href= url
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();

        link.parentNode.removeChild(link);
    }

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        $(".btn_export").prop('disabled', true);
        $(".loader_component").show();
        const cod_cliente = $("#form_export input[name=cod_cliente]").val();
        const periodo = $("#form_export input[name=periodo]").val();
        fetch("{{asset($data['url_validator'])}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}`, {
            method: 'GET'
        })
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            if(response.passes === true){
                fetch("{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}`, {
                    method: 'GET'
                })
                .then(response => {
                    if(!response.ok){
                        throw new Error(response.statusText);
                    }
                    return response;
                })
                //.then(response => response.blob())
                .then(async(response) => {
                    const content_disp = response.headers.get('Content-Disposition');
                    let filename = config['filename'];

                    const header_parts = content_disp.replaceAll('"', '').split(";");
                    header_parts.forEach(row => {
                        if(row.split("=")[1] !== undefined){
                            filename = row.split("=")[1];
                        }
                    });
                    const response_content = await response.blob();
                    downloadFile(response_content , filename);
                    $(".loader_component").hide();
                    $(".btn_export").prop('disabled', false);
                })
                .catch(error => {
                    $(".loader_component").hide();
                    $(".btn_export").prop('disabled', false);
                    Promise.reject();
                    alert(error);
                    //throw(error);
                });
            }else{
                $(".loader_component").hide();
                $(".btn_export").prop('disabled', false);
                alert(response.errors.message);
            }
        })
        .catch(error => {
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false);
            alert(error);
        });
    });
    if("{{ isset($data['url_export']) ? $data['url_export'] : '' }}" === ''){
        $(".btn_export").prop('disabled', true);
    }
});
</script>
@endsection