@extends(backpack_view('blank'))

@section('content')
<h4 style="">Reporte fiscal</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            <div class="row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Msisdn</label>
                        <input type="text" class="form-control form-control-sm" name="msisdn" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ingresar el valor sin el codigo 51<br>
                            Ej. 947123456
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm" name="periodo1" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm" name="periodo2" required>
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
    const config = @json($data);

    const onSubmitHandler = (e) => {
        e.preventDefault();

        const button = document.querySelector('.btn_export');
        button.disabled = true;
        button.innerHTML = 'Cargando ...';

        const formData = new FormData(e.target);
        const string_params = new URLSearchParams(formData).toString();

        fetch(`${config.url_validator}?${string_params}`, {method: 'GET'})
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            if(response.passes === true){
                fetch(`${config.url_export}?${string_params}`, {method: 'GET'})
                .then(response => {
                    if(!response.ok){
                        throw new Error(response.statusText);
                    }
                    return response;
                })
                .then(response => response.blob())
                .then(response => {
                    let filename = config['filename'];
                    utils.downloadFile(response , filename);
                    button.disabled = false;
                    button.innerHTML = 'Descargar';
                })
                .catch(error => {
                    button.disabled = false;
                    button.innerHTML = 'Descargar';
                    alert(error);
                });
            }else{
                button.disabled = false;
                button.innerHTML = 'Descargar';
                alert(response.errors.message);
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = 'Descargar';
            alert(error);
        });
    }

    document.getElementById('form_export').addEventListener('submit', onSubmitHandler, false);
});
</script>
@endsection
