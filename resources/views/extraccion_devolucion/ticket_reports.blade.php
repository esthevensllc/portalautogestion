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
{{-- DATA TABLES SCRIPT --}}
<script type="text/javascript" src="{{ asset('packages/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader-bs4/js/fixedHeader.bootstrap4.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" integrity="sha384-+mbV2IY1Zk/X1p/nWllGySJSUN8uMs+gUAN10Or95UBH0fpj6GfKgPmgC5EXieXG" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
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