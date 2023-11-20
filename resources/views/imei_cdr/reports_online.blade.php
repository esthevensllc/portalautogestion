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
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>

<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-informefallas" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            <th>Fecha</th>
            <th>Archivo</th>
            <th>Cantidad Registros</th>
            <th>Peso</th>
            <th>Fecha Creación</th>
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

{{-- <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script> --}}
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

    let _datatable = $(".tbl-informefallas").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ url('imeis-cdr-report-online/search') }}",
            type: "GET",
        },
        columns: [
            {data: 'fecha'},
            // {data: 'id'},
            {render: function(data, type, row){
                let url = config.downloadUrl.replace('[id]', row['id']);
                let html = `<a href="${url}" target="_blank">${row['filename']}</a>`;
                return html;
            }},
            {data: 'n_registros'},
            {render: function(data, type, row){
                let kb = (row['size_bytes']/1024).toFixed(1);
                let html = `<span>${kb}K</span>`;
                return html;
            }},
            {data: 'created_at'},
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: false,
        order: [[0, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection