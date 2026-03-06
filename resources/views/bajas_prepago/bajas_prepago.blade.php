@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
    .dataTables_scrollBody{
        position: unset !important;
    }
    div.dt-buttons {
        display: block !important;
    }
</style>
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_import">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Archivo</label>
                        <input type="file" class="d-block" name="archivo" accept=".txt" required>
                        <div class="invalid-feedback d-block text-dark">
                            El archivo de estar sin cabeceras.
                            <br>Debe tener un valor msisdn por fila y tener un formato txt.
                        </div>
                    </div>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="clase">Clase</label>
                        <select name="clase" id="clase" class="form-control form-control-sm" required>
                            <option value="">Seleccione</option>
                            <option>VARIACIÓN - OPERATIVA</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="sub_clase">Sub clase</label>
                        <select name="sub_clase" id="sub_clase" class="form-control form-control-sm" required>
                            <option value="">Seleccione</option>
                            <option>OSIPTEL - BAJA DE SERVICIO POR FALTA DE TRÁFICO</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <select name="notas" id="notas" class="form-control form-control-sm" required>
                            <option value="">Seleccione</option>
                            <option>Se realiza la baja del servicio contratado bajo la modalidad prepago por no haber realizado tráfico de voz y/o datos (entrante y/o saliente) en el plazo de (3) días calendario desde la fecha de activación</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-12">
                    <table class="table table-sm table-bordered" style="max-width: 400px; display: none;" id="table_summary">
                        <thead class="table-secondary">
                            <tr>
                                <th>Flag</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Importar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-bajas_prepago" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            <th>Usuario</th>
            <th>Clase</th>
            <th>Sub clase</th>
            <th>Notas</th>
            <th>Cantidad lineas</th>
            <th>Fecha carga</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script type="text/javascript" src="{{ asset('packages/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader-bs4/js/fixedHeader.bootstrap4.min.js') }}"></script>
<script>
$(function() {
    const config = @json($config);

    let _datatable = $(".tbl-bajas_prepago").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ url('bajas-prepago/search') }}",
            type: "GET",
        },
        columns: [
            {data: 'username'},
            {data: 'clase'},
            {data: 'subclase'},
            {data: 'notas'},
            {data: 'cant_lineas'},
            {data: 'fecha_carga'},
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: false,
        order: [[5, 'desc']],
        scrollX: true
        //serverSide: true
    });

    const finalImportHandler = (e) => {
        const _token = $("input[name=_token]").val();
        const baseId = $(e.target).attr('data-id');
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        utils.fetch(config.importApi.replace('[id]', baseId), {
            headers: {"Accept": "application/json", "Content-Type": "application/json"},
            method: 'POST',
            body: JSON.stringify({_token, baseId: baseId})
        })
        .then(async(response) => {
            let isOk = response.ok;
            let json = await response.json();
            if(isOk){
                $("#table_summary").hide();
                _datatable.ajax.reload();
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Cargado correctamente"
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: json.message
                }).show();
            }
            loader_component.style.display = 'none';
        });
    };

    const downloadFileHandler = (e) => {
        const baseId = $(e.target).attr('data-id');
        const estado = $(e.target).attr('data-estado');

        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        utils.fetch(config.downloadApi.replace('[id]', baseId).replace('[estado]', estado), {
            headers: {"Accept": "application/json"},
            method: 'GET',
        })
        .then(async(response) => {
            if (response.ok) {
                return response;
            }
            let json = await response.json();
            throw json;
        })
        .then((response) => {
            let filename = 'reporte';
            const content_disp = response.headers.get('Content-Disposition');
            const header_parts = (content_disp??"").replaceAll('"', '').split(";");
            header_parts.forEach(row => {
                if(row.split("=")[1] !== undefined){
                    filename = row.split("=")[1];
                }
            });
            return response.blob().then(blob => ({blob, filename}));
        })
        .then((response) => {
            loader_component.style.display = 'none';
            utils.downloadBlob(response.blob, response.filename);
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error.message);
        });
    };

    document.querySelector("#form_import")
    .addEventListener("submit", function(e){
        e.preventDefault();

        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const data = new FormData(e.target);
        utils.fetch(config.preImportApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        .then(async(response) => {
            let isOk = response.ok;
            let json = await response.json();
            if(isOk){
                let summaryHtml = json.summary.map(row => {
                    let buttonHtml = `<button type="button" class="btn btn-sm btn-outline-success btn-download-flag" data-id="${json['baseId']}" data-estado="${row['flag_validacion']}">Descargar</button>`;
                    if (row['flag_validacion'] === 'VALIDADO') {
                        buttonHtml = `<button type="button" class="btn btn-sm btn-primary btn-final-import" data-id="${json['baseId']}" data-estado="${row['flag_validacion']}">Procesar</button>`;
                    }
                    return `<tr>
                        <td>${row['flag_validacion']}</td>
                        <td>${row['cantidad']}</td>
                        <td>${buttonHtml}</td>
                    </tr>`;
                });
                $("#table_summary").show();
                $("#table_summary tbody").html(summaryHtml);
                
                $("#table_summary .btn-download-flag").on('click', downloadFileHandler);
                $("#table_summary .btn-final-import").on('click', finalImportHandler);
            } else {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: json.message
                }).show();
            }
            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error.message);
        });
    });
});
</script>
@endsection
