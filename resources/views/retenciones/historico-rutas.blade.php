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
    <h4 class="mb-0 mr-3">{{ $config['title'] }}</h4>
</div>

<table id="rutas" class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100" cellspacing="0">
    <thead class="bg-danger">
        <tr>
            <th>dia_load</th>
            <th>callcenter</th>
            <th>operadorfinal</th>
            <th>fileName</th>
            <th>pathName</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

@include('includes.spinner_loader')
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
    $("#rutas").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ $config['api_rutas'] }}",
            type: "GET",
            dataSrc: function(resp){
                return resp.data;
            }
        },
        columns: [
            {data: 'dia_load', name: 'dia_load'},
            {data: 'callcenter', name: 'callcenter'},
            {data: 'operadorfinal', name: 'operadorfinal'},
            {data: 'fileName', name: 'fileName'},
            {data: 'pathName', name: 'pathName'}
        ],
        paging: true,
        pageLength: 50,
        lengthChange: true,
        searching: false,
        info: false,
        order: [[0, 'desc']],
        //serverSide: true,
        scrollX: true
    });
</script>
@endsection
