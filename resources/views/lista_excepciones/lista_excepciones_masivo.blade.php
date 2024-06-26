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
                    <label for="">Tipo Operación</label>
                    <select name="tipo_operacion_id" class="form-control form-control-sm reload_ticket" required>
                    <option value="">Seleccione</option>
                        @foreach ($config["tipos_operacion"] as $row)
                            <option value="{{ $row->id }}">{{ $row->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Archivo</label>
                    <input type="file" class="d-block" name="archivo" accept=".xlsx,.xls,.csv,.txt" required>
                    <div class="invalid-feedback d-block text-dark">
                        El archivo de estar sin cabeceras.
                        <br>Debe tener tres valores(imei, imsi, linea) y tener un formato xlsx, xls, csv o txt.
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Importar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-documentlog" cellspacing="0"
>
    <thead class="bg-danger">
        <tr>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Tipo Operación</th>
            <th>Documento</th>
            <th>Peso</th>
            {{-- <th>EIR</th> --}}
            <th>Cantidad Registros</th>
            <th>Log Response</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.datatables_js')
<script>
$(function() {
    const config = @json($config);

    const tiposOperacionById = {};

    config.tipos_operacion.forEach(r => {
        tiposOperacionById[r.id] = r;
    });

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
                    text: "Se importó correctamente"
                }).show();
                _datatable.ajax.reload();
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

    let _datatable = $(".tbl-documentlog").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.search,
            type: "GET",
        },
        columns: [
            {data: 'username'},
            {data: 'fecha'},
            {render: function(data, type, row){
                return tiposOperacionById[row['tipo_operacion_id']].label;
            }},
            {render: function(data, type, row){
                let url = config.downloadFile.replace('[filename]', row['filename']);
                let html = `<a href="${url}" target="_blank">${row['filename']}</a>`;
                return html;
            }},
            {render: function(data, type, row){
                let kb = (row['size_bytes']/1024).toFixed(1);
                let html = `<span>${kb}K</span>`;
                return html;
            }},
            {data: 'cant_registros'},
            /*{data: 'cant_unicos'},
            {data: 'exec_ok'},
            {data: 'exec_fail'},*/
            {render: function(data, type, row){
                let url = config.downloadEir.replace('[id]', row['eir_id']);
                let html = `<a href="${url}" target="_blank">Descargar</a>`;
                return html;
            }},
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: false,
        order: [[1, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection