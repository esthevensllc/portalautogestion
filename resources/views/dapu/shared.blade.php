@extends(backpack_view('blank'))

@include('includes.datatables_css')

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                @include($config["form_view"])
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
            <table class="table table-sm" style="min-width: 1000px;">
                <thead>
                    <tr>
                        @foreach ($config["fields"] as $row)
                            <th>{{ $row["label"] }}</th>
                        @endforeach
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
@include('includes.datatables_js')
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
            const method = @if(array_key_exists('form_method', $config)) "{{$config['form_method']}}" @else 'GET' @endif;
            const formData = new FormData(e.target);
            let apiUrl = config.url;
            if(method === "GET"){
                const string_params = new URLSearchParams(formData).toString();
                apiUrl = `${config.url}?${string_params}`;
            }
            const response = await fetch(apiUrl, {
                method: method,
                headers: {
                    'Accept': 'application/json'
                },
                body: method === "GET" ? undefined : formData
            });

            await utils.fetchAuthMiddleware(response);

            if(!response.ok){
                const _json = await response.json();
                throw new Error(_json.message ?? response.statusText);
            }

            const data = await response.json();
            
            let html_body = data.map(row => `<tr>
                ${Object.keys(config.fields).map(f => `<td>${row[f]??''}</td>`).join('')}
            </tr>`).join('');
            // Comprobar si DataTable ya está inicializado
            if ($.fn.DataTable.isDataTable('.table')) {
                // Destruir la instancia existente
                $('.table').DataTable().destroy();
            }
            document.querySelector('table tbody').innerHTML = html_body;
            var table = new DataTable('.table', {
                language: {
                    url: "{{ asset('packages/datatables-language/spanish.json') }}",
                },
            });
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
        let handlerOptions = {url: config.url_export, btn_export: ".btn-download button"};
        utils.downloadHandler(handlerOptions, customEvent);
    }
});

let tabSelect = document.querySelector('#tabs_select');
if(tabSelect){
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
}
</script>
@endsection
