@extends(backpack_view('blank'))

@section('after_styles')
  {{-- DATA TABLES --}}
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    /*tbody tr td, thead, tr th{
        padding: 0.4rem;
    }*/
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
<div class="d-flex mb-2">
    <h4 class="mb-0 mr-3">{{ $config["title"] }}</h4>
</div>

<div class="">
<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100" cellspacing="0"
>
    <thead class="bg-danger">
        <tr>
            <th>Ticket</th>
            <th>Departamento</th>
            <th>Fecha hora</th>
            <th>Numero Afectados</th>
            <th>Numero Afectados Post</th>
            <th>Acreditados Post</th>
            <th>No Acreditados Post</th>
            <th>Numero Afectados Pre</th>
            <th>Acreditados Pre</th>
            <th>No Acreditados Pre</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
@include('includes.spinner_loader')
@csrf
@endsection

@section('after_scripts')
@include('includes.datatables_js')
@include('includes.jszip_js')
@include('includes.datatables_buttons_js')
<script>
    const config = @json($config);
    const _datatable = $(".table").DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="la la-download"></i> Descargar tabla',
                className: 'btn btn-danger btn-sm',
                filename: 'registros_de_ticket',
                exportOptions: {
                    modifier: { page: 'all' },
                    columns: ':not(:last-child)'
                }
            }
        ],
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
            type: "POST",
            dataSrc: function(resp){
                //store.relationships = resp.relationships;
                return resp.data;
            }
        },
        columns: [
            {data: 'ticket'},
            {data: 'departamento'},
            {data: 'fecha_hora_exec'},
            {data: 'numero_afectados'},
            {data: 'numero_afectados_post'},
            {data: 'acreditados_post'},
            {data: 'no_acreditados_post'},
            {data: 'numero_afectados_pre'},
            {data: 'acreditados_pre'},
            {data: 'no_acreditados_pre'},
        ],
        order: [[2, 'desc']],
        //serverSide: true,
        scrollX: true
    });
</script>
@endsection