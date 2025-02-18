@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_search" class="row">
            @csrf
            <input type="hidden" name="type" value="Csv">
            <div class="col-lg-2">
                <div class="form-group">
                    <label for="">Tipo input</label>
                    <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
                        <option value="1">IMEI</option>
                        <option value="2">CSV IMEI</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-8 tabs" data-tab-target="tabs_select">
                <div class="row">
                    <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
                        <div class="form-group">
                            <label for="">IMEI</label>
                            <input type="text" name="value" class="form-control form-control-sm">
                            <div class="invalid-feedback d-block text-dark">
                                Puede ingresar mas de un imei separado por comas
                                <br>Ej. 35409988708912
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
                        <div class="form-group">
                            <label for="">CSV IMEI</label>
                            <input type="file" name="file_value" class="d-block" accept=".csv">
                            <div class="invalid-feedback d-block text-dark">
                                Subir el archivo en formato csv sin cabeceras y con los valores en la primera columna
                                <br>Ej. 35409988708912
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12" style="display: flex; align-items: end;">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Buscar</button>
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
        </form>
    </div>
</div>

<div class="table-responsive">
    <table id="table_lista_excepciones" class="table table-sm mb-0" style="min-width: 1500px; background: #FFFFFF;">
        <thead class="bg-danger">
            <tr>
                <th>TRANSACT_DATE</th>
                <th>IMEI</th>
                <th>OPERACION</th>
                <th>MSISDN</th>
                <th>IMSI</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.datatables_js')
<script>
$(function() {
    const config = @json($config);

    const _datatable = $("table").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
            type: "POST",
            processData: false,
            contentType: false,
            data: function(data){
                //console.log(data);
                //let value = document.querySelector("form input[name=value]").value;
                //return {...data, value: value}
                return new FormData(document.querySelector("#form_search"));
            },
            dataSrc: function(resp){
                return resp.data;
            }
        },
        columns: [
            {data: 'transact_date'},
            {data: 'imei'},
            {data: 'operacion'},
            {data: 'msisdn'},
            {data: 'imsi'}
        ],
        //serverSide: true,
        scrollX: true,
        searching: false
    });

    function renderTable(){
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        let value = document.querySelector("form input[name=value]").value;

        utils.fetch(`${config.url}?value=${value}`)
        .then(resp => resp.json())
        .then(response => {
            _datatable.ajax.reload();
            document.querySelector("#table_lista_excepciones tbody").innerHTML = htmlBody;
            loader_component.style.display = 'none';
        });
    }

    document.querySelector("#form_search")
    .addEventListener("submit", async function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';
        await _datatable.ajax.reload();
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

    document.querySelector('.btn-download .btn-download-options')
    .addEventListener('click', function(e){
        if(e.target.tagName === 'A'){
            document.querySelector("input[name=type]").value = e.target.attributes['data-type'].value;
            let customEvent = {
                target: document.getElementById('form_search')
            };
            let handlerOptions = {url: config.exportApi, btn_export: ".btn-download button"};
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
});
</script>
@endsection