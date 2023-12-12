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
<div class="card">
    <div class="card-body">
        <div class='col-md-3'>
            <form id="form_export" class="row">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="imeiText" placeholder="Ingresa Imei" aria-label="Búsqueda" aria-describedby="btnBuscar" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-danger" type="button" id="btnBuscar">Buscar</button>
                    </div>
                </div>
            </form>
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
            <th>Fecha</th>
            <th>Imei</th>
            <th>Status</th>
            <th>Code</th>
            <th>Ejecución</th>
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

    let _datatable = '';

    $("#btnBuscar").on('click',function(){
        imei = $("#imeiText").val();
        if(imei.length < 14){
            alert('Ingrese un imei correcto a buscar');
            return false;
        }else{
            imei_t = imei;
            imei_t = imei_t.substring(0, 14);
            if ( $.fn.dataTable.isDataTable( '.tbl-informefallas' ) ) {
                _datatable.destroy();
            }              
            _datatable = $(".tbl-informefallas").DataTable({
                language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
                ajax: {
                    url: "{{ url('control-eir-imei/search') }}",
                    type: "POST",
                    data: {imei: imei_t},
                    dataSrc: function(resp){
                        //console.log(resp);
                        //store.relationships = resp.relationships;
                        return resp.data;
                    }
                },
                columns: [
                    {data: 'fecha'},
                    {data: 'imei'},
                    {data: 'status'},
                    {data: 'code'},
                    {data: 'ejecucion'}
                ],
                lengthChange: false,
                searching: false,
                order: [[0, 'desc']],
                scrollX: true
                //serverSide: true
            });
        }
    });

});
</script>
@endsection