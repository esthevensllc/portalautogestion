@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
                            {{-- <option value="1">Periodos</option> --}}
                            <option value="1">LINEA</option>
                            <option value="2">DNI</option>
                            <option value="3">CSV MSISDN</option>
                            <option value="4">CSV DNI</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-8 tabs" data-tab-target="tabs_select">
                    <div class="row">
                        <div class="col-lg-4 col-md-5 tab-item" data-tab-target="1">
                            <div class="form-group">
                                <label for="">Linea</label>
                                <input type="text" name="linea" class="form-control form-control-sm" required>
                                <div class="invalid-feedback d-block text-dark">
                                    Ej. 51947158416
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-5 tab-item" data-tab-target="2">
                            <div class="form-group">
                                <label for="">DNI</label>
                                <input type="text" name="dni" class="form-control form-control-sm" required disabled>
                                <div class="invalid-feedback d-block text-dark">
                                    Ej. 42233044
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-5 tab-item" data-tab-target="3">
                            <div class="form-group">
                                <label for="">Archivo MSISDN</label>
                                <input type="file" class="d-block" name="file_msisdn" accept=".csv" required disabled>
                                <div class="invalid-feedback d-block text-dark">
                                    Subir el archivo con formato csv sin cabeceras
                                    <br>Ingresar los msisdn en la primera columna, anteponer el codigo 51
                                    <br>Ej. 51965251434
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-5 tab-item" data-tab-target="4">
                            <div class="form-group">
                                <label for="">Archivo DNI</label>
                                <input type="file" class="d-block" name="file_dni" accept=".csv" required disabled>
                                <div class="invalid-feedback d-block text-dark">
                                    Subir eL archivo con formato csv sin cabeceras y en la primera columna
                                    <br>Ej. 42233044
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="type" class="form-control form-control-sm">
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Visualizar</button>
                        <div class="dropdown d-inline-block btn-download">
                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Descargar
                            </button>
                            <div class="dropdown-menu btn-download-options" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item btn-download-csv" data-type="Csv" href="#">Csv</a>
                                <a class="dropdown-item btn-download-excel" data-type="Xlsx" href="#">Excel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm" style="min-width: 2000px;">
                <thead>
                    <tr>
                        <th>LINEA</th>
                        <th>PRODUCTO</th>
                        <th>TIPO_DOC</th>
                        <th>NUM_DOC</th>
                        <th>NOMBRE</th>
                        <th>AGREEMENT_MODE</th>
                        <th>AGREEMENT_SERVICE_GROUP</th>
                        <th>STATUS</th>
                        <th style="min-width: 145px;">F_INICIO</th>
                        <th style="min-width: 145px;">F_FIN</th>
                        <th>DIRECCION</th>
                        <th>DEPARTAMENTO</th>
                        <th>PROVINCIA</th>
                        <th>DISTRITO</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
const config = @json($config);

document.getElementById('form_export')
.addEventListener('submit', async function(e){
    e.preventDefault();
    const button = document.querySelector('.btn_export');
        const loader_component = document.querySelector('.loader_component');
        button.disabled = true;
        button.innerHTML = 'Cargando ...';
        loader_component.style.display = 'block';
        try {
            const formData = new FormData(e.target);
            const response = await fetch(`${config.url}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            await utils.fetchAuthMiddleware(response);

            if(!response.ok){
                const _json = await response.json();
                throw new Error(_json.message ?? response.statusText);
            }

            const data = await response.json();
            let html_body = data.map(row => `<tr>
                <td>${row.linea??''}</td>
                <td>${row.producto??''}</td>
                <td>${row.tipo_doc??''}</td>
                <td>${row.num_doc??''}</td>
                <td>${row.nombre??''}</td>
                <td>${row.agreement_mode??''}</td>
                <td>${row.agreement_service_group??''}</td>
                <td>${row.status??''}</td>
                <td>${row.f_inicio??''}</td>
                <td>${row.f_fin??''}</td>
                <td>${row.direccion??''}</td>
                <td>${row.departamento??''}</td>
                <td>${row.provincia??''}</td>
                <td>${row.distrito??''}</td>
            </tr>`).join('');

            document.querySelector('table tbody').innerHTML = html_body;
        } catch (error) {
            console.log(error);
            alert(error);
        }
        button.disabled = false;
        button.innerHTML = 'Visualizar';
        loader_component.style.display = 'none';
});

document.querySelector('.btn-download .btn-download-options')
.addEventListener('click', function(e){
    if(e.target.tagName === 'A'){
        document.querySelector("input[name=type]").value = e.target.attributes['data-type'].value;
        let customEvent = {
            target: document.getElementById('form_export')
        };
        let handlerOptions = {url: config.url_export, btn_export: ".btn-download button", requestOptions: {headers: {'Accept': 'application/json'}}};
        utils.downloadHandler(handlerOptions, customEvent);
    }
});

let tabSelect = document.querySelector('#tabs_select');
utils.tabs.createTabSelectController(tabSelect);

tabSelect.addEventListener('change', function(e){
    document.querySelectorAll('.tabs[data-tab-target=tabs_select] .tab-item input')
    .forEach(panelInput => {
        panelInput.disabled = true;
    });
    let input = document.querySelector('.tabs[data-tab-target=tabs_select] .tab-item-active input');
    if(input){
        input.disabled = false;
    }
});
</script>
@endsection
