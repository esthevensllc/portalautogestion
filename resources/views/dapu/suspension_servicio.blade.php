@extends(backpack_view('blank'))

@section('content')

<h4 style="">SUSPENSIÓN DE SERVICIO</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Número</label>
                        <input type="text" name="numero" class="form-control form-control-sm" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ingrese el número sin el codigo 51<br>
                            Ej. 947123456
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">DNI</label>
                        <input type="text" name="dni" class="form-control form-control-sm" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 70838434
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha inicio</label>
                        <input type="date" name="fecha1" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha fin</label>
                        <input type="date" name="fecha2" class="form-control form-control-sm" required>
                    </div>
                </div>
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
            <table class="table table-sm" style="min-width: 1100px;">
                <thead>
                    <tr>
                        <th>Codigo cliente</th>
                        <th>Cliente</th>
                        <th>Nro doc</th>
                        <th>Doc</th>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Fecha suspensión</th>
                        <th>Fecha alta</th>
                        <th>Fecha baja</th>
                        <th>Segmento</th>
                        <th>Motivo estado</th>
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
    document.getElementById('form_export').addEventListener('submit', async function(e){
        e.preventDefault();

        const button = document.querySelector('.btn_export');
        const loader_component = document.querySelector('.loader_component');
        button.disabled = true;
        button.innerHTML = 'Cargando ...';
        loader_component.style.display = 'block';
        try {
            const formData = new FormData(e.target);
            const string_params = new URLSearchParams(formData).toString();
            const response = await fetch(`${config.url}?${string_params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            const html_body = data.map(row => `<tr>
                <td>${row.codigo_cliente??''}</td>
                <td>${row.cliente??''}</td>
                <td>${row.nro_doc??''}</td>
                <td>${row.doc??''}</td>
                <td>${row.msisdn??''}</td>
                <td>${row.estado??''}</td>
                <td>${row.fecha_suspension??''}</td>
                <td>${row.fecha_alta??''}</td>
                <td>${row.fecha_baja??''}</td>
                <td>${row.segmento??''}</td>
                <td>${row.motivo_estado??''}</td>
            </tr>`).join('');
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = html_body;
        } catch (error) {
            console.log(error);
            alert(error);
        }
        button.disabled = false;
        button.innerHTML = 'Visualizar';
        loader_component.style.display = 'none';
    }, false);

    document.querySelector('.btn-download .btn-download-options')
    .addEventListener('click', function(e){
        if(e.target.tagName === 'A'){
            const button = document.querySelector('.btn-download button');
            button.innerHTML = 'Cargando ...';
            button.disabled = true;

            const formData = new FormData(document.getElementById('form_export'));
            const string_params = new URLSearchParams(formData).toString();

            const type = e.target.attributes['data-type'].value;
            fetch("{{ asset('dapu/suspension-servicio/export') }}"+`?${string_params}&type=${type}`, {
                method: 'GET',
            })
            .then(utils.fetchAuthMiddleware)
            .then(async (response) => {
                if(!response.ok){
                    throw new Error(response.statusText);
                }
                const content_disp = response.headers.get('Content-Disposition');
                const header_parts = (content_disp??"").replaceAll('"', '').split(";");
                console.log(header_parts);
                let filename = '';
                header_parts.forEach(row => {
                    if(row.split("=")[1] !== undefined){
                        filename = row.split("=")[1];
                    }
                });
                const blob = await response.blob();
                if(filename.includes('.xls') || filename.includes('.xlsx')){
                    utils.downloadFile(blob, filename, 'default');
                }else{
                    utils.downloadFile(blob, filename);
                }
                button.innerHTML = 'Descargar';
                button.disabled = false;
            })
            .catch(error => {
                button.innerHTML = 'Descargar';
                button.disabled = false;
                alert(error);
            })
        }
    });
</script>
@endsection