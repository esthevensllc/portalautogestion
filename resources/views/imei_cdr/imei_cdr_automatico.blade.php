@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
</style>
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <form id="form_import">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="">Ingrese Base IMEI</label>
                                <input type="file" name="import_base_imei" class="d-block" accept=".csv,.txt" required>
                            </div>
                        </div>
                        <div class="col-12" style="display: flex; align-items: end;">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-sm btn_export mb-0">Cargar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <form id="form_delete">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="">Ingrese Base IMEI</label>
                                <input type="file" name="delete_base_imei" class="d-block" accept=".csv,.txt" required>
                            </div>
                        </div>
                        <div class="col-12" style="display: flex; align-items: end;">
                            <div class="form-group">
                                <button type="submit" class="btn btn-danger btn-sm btn_export mb-0">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <table class="table table-striped table-sm mb-0">
                    <thead class="bg-danger">
                        <tr>
                            <th>RAT Type</th>
                            <th>Values(Decimal)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($config["ratTypes"] as $row)
                            <tr>
                                <td>{{ $row["type"] }}</td>
                                <td>{{ $row["value"] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-informefallas" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            <th>Username</th>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Cantidad Registros</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
<script type="text/javascript" src="{{ asset('packages/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader-bs4/js/fixedHeader.bootstrap4.min.js') }}"></script>

@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_import")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const data = new FormData(e.target);
        utils.fetch(config.importBaseImeiUrl, {
            method: 'POST',
            body: data,
            headers: {"Accept": "application/json"}
        })
        .then(resp => {
            if(resp.ok){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: 'Se cargo correctamente'
                }).show();
                _datatable.ajax.reload();
            } else {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Ocurrió un error al cargar el archivo'
                }).show();
            }
        });
    });

    document.querySelector("#form_delete")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let passes = confirm(`Esta seguro que desea eliminar?`);
        if(passes){
            const data = new FormData(e.target);
            utils.fetch(config.deleteBaseImeiUrl, {
                method: 'POST',
                body: data,
                headers: {"Accept": "application/json"}
            })
            .then(resp => {
                if(resp.ok){
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Se elimino correctamente'
                    }).show();
                    _datatable.ajax.reload();
                } else {
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Ocurrió un error al eliminar'
                    }).show();
                }
            });
        }
    });

    function deleteFilenameHandler(e)
    {
        let id = $(e.target).attr("data-id");
        let filename = $(e.target).attr("data-filename");
        let deleteFilename = confirm(`Esta seguro que desea eliminar el file ${filename}`);
        if(deleteFilename){
            let token = $("input[name=_token]").val();
            utils.fetch(config.deleteUrl.replace("[id]", id), {
                headers: {
                    "Accept": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                method: 'DELETE'
            })
            //.then(resp => resp.json())
            .then(resp => {
                if(resp.ok){
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Se elimino correctamente'
                    }).show();
                    _datatable.ajax.reload();
                } else {
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Ocurrió un error al eliminar el reporte'
                    }).show();
                }
            });
        }
    }


    /*$('input[name=date_range]').daterangepicker({
        "timePicker": true,
        "timePicker24Hour": true,
        "timePickerSeconds": true,
        "locale": {
            "format": "DD/MM/YYYY HH:mm:ss"
        },
    }, function(start, end, label) {
        console.log('New date range selected: ' + start.format('YYYY-MM-DD HH:mm:ss') + ' to ' + end.format('YYYY-MM-DD HH:mm:ss') + ' (predefined range: ' + label + ')');
    });*/

    let _datatable = $(".tbl-informefallas").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ url('imei-cdr-automatico/search') }}",
            type: "GET",
        },
        columns: [
            {data: 'username'},
            {data: 'filename'},
            {data: 'uploaded_at'},
            {data: 'n_registros'},
            {render: function(data, type, row){
                let html = `<button
                    class="btn btn-sm btn-danger pt-0 pb-0 btn-delete"
                    data-id="${row['id_report']}"
                    data-filename="${row['filename']}"
                    ><li class="la la-trash"></li> Eliminar</button>`;
                return html;
            }}
        ],
        "fnDrawCallback": function() {
            $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: false,
        order: [[3, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection