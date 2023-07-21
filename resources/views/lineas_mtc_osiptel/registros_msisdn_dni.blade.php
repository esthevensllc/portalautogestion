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
            <th>Carta</th>
            <th>Fecha</th>
            <th>Tipo de Plan</th>
            <th>Numero Post</th>
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
<script>
    const config = @json($config);
    const _datatable = $(".table").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
            type: "POST",
            dataSrc: function(resp){
                return resp.data;
            }
        },
        columns: [
            {data: 'carta'},
            {data: 'fecha'},
            {data: 'tipo_plan'},
            {data: 'num_post'},
        ],
        //serverSide: true,
        scrollX: true
    });
</script>
@endsection