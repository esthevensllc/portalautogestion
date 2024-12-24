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
            <div class="mb-0 col-6">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Ingresa Imei" name="value">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-danger" type="button">Buscar</button>
                    </div>
<!--                     <div class="input-group-append">
                        <button type="button" class="btn btn-outline-success btn_export" type="button" id="btn_export">Exportar</button>
                    </div> -->
                </div>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table id="table_lista_excepciones" class="table table-sm mb-0" style="min-width: 2000px; background: #FFFFFF;">
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
            type: "GET",
            data: function(data){
                let value = document.querySelector("form input[name=value]").value;
                return {...data, value: value}
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

    document.querySelector("#btn_export")
    .addEventListener("click", async function(e){
        await _datatable.ajax.reload();
        let event = {target: document.querySelector("#form_search")};
        await utils.downloadHandler({
            url: config.exportApi,
            requestOptions: {headers: {accept: "application/json"}},
        }, event);
        e.target.innerHTML = "Exportar";
    });
});
</script>
@endsection