@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}">
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
</style>

@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export" class="row">
            @csrf
            <div class="mb-3 col-6">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="">Tipo Busqueda</label>
                            <select class="form-control form-control-sm select-busqueda" name="tipobusqueda_id" required>
                                <option value=''>Seleccione</option>
                                @foreach ($config["tiposBusqueda"] as $row)
                                    <option value='{{ $row["id"] }}'>{{ $row["label"] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="form-group">
                            <label for="">Ingrese Base:</label>
                            <input type="file"  class="form-control" name="base_imei" placeholder="Ingrese Nintex" accept=".csv,.txt">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="form-group">
                            <label class="label-imei" style="display : none;" for="">Ingrese los Imeis:</label>
                            <input type="text" class="form-control input-imei" name="text_imei" style="display : none;" placeholder="">
                            <p class="label-imei" style="display : none;">Ej. 01160100384813,01160100384814</p>
                            <label class="label-msisdn" style="display : none;" for="">Ingrese los MSISDN:</label>
                            <input type="text" class="form-control input-msisdn" name="text_msisdn" style="display : none;" placeholder="">
                            <p class="label-msisdn" style="display : none;">Ej. 51950165853,51971339230</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="">Fecha Inicio</label>
                            <input type="text" class="form-control form-control-sm datepicker bootstrap-timepicker" name="f_ini" required>
                            <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha1_time" placeholder="00:00:00" required>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="">Fecha Fin</label>
                            <input type="text" class="form-control form-control-sm datepicker bootstrap-timepicker" name="f_fin" required>
                            <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha2_time" placeholder="00:00:00" required>
                        </div>
                    </div>
                    <div class="col-12" style="display: flex; align-items: end;">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 col-6"> 
                <div class="row">     
                    <div class="col-md-8" id="leyenda" style="display: none;">
                        <div>
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
                    <div class="col-md-4">
                        <button class="btn btn-danger" id="toogleButton">Leyenda</button>
                    </div>
                </div>
            <div>
        </form>
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

{{-- <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script> --}}
<script type="text/javascript" src="{{ asset('packages/moment/min/moment.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/bootstrap-timepicker/js/bootstrap-timepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/bootstrap-timepicker/js/bootstrap-datepicker.es.min.js') }}"></script>

@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        if($(".input-imei").is(":hidden")){
            $(".input-imei").prop("disabled", true);
        }
        if($(".input-msisdn").is(":hidden")){
            $(".input-msisdn").prop("disabled", true);
        }
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {"Accept": "application/json"}},
            successCallback: () => {
                _datatable.ajax.reload();
            }
        }, e);
    });

    $("#toogleButton").on('click', function(event){
        event.preventDefault();
        $("#leyenda").toggle();
    });

    $(".select-busqueda").change(function(){
        if($(this).val() == "1"){
            $(".label-imei").show();
            $(".input-imei").show().prop("disabled", false);
        }else{
            $(".label-imei").hide();
            $(".input-imei").hide().prop("disabled", true);
        }
        if($(this).val() == "2"){
            $(".label-msisdn").show();
            $(".input-msisdn").show().prop("disabled", false);
        }else{
            $(".label-msisdn").hide();
            $(".input-msisdn").hide().prop("disabled", true);
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

    $('.datepicker').datepicker({
        format: "dd/mm/yyyy",
        language: "es"
    });

    $(".clean_white_space").on("change", function(e){
        e.target.value = e.target.value.replaceAll(" ", "");
    });

    let _datatable = $(".tbl-informefallas").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ url('bloqueo-imei/search') }}",
            type: "GET",
            dataSrc: function(resp){
                //console.log(resp);
                //store.relationships = resp.relationships;
                return resp.data;
            }
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