@extends(backpack_view('blank'))

@section('after_styles')
{{-- DATA TABLES --}}
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
@include('includes.select2_css')
@endsection

@section('header')
<!-- Modal para eliminar-->
<div class="modal" id="dialogo-eliminar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">¿Estás seguro?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar este reporte?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="update-status-modal" tabindex="-1" role="dialog">
    <form id="frm-update-status">
        @csrf
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">¿Estás seguro?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="status" value="">
                    <input type="hidden" name="id" value="">
                    <p class="label">¿Estás seguro de que quieres eliminar este reporte?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal para poner en espera-->
<div class="modal" id="enEsperaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">¿Estás seguro?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres poner en espera este reporte?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-enEspera">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para desaprobar-->
<div class="modal" id="desaprobarModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">¿Estás seguro?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres desaprobar este reporte?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-desaprobar">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para aprobar-->
<div class="modal fade" id="aprobarModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Input dentro del modal -->
                <input type="text" id="input-modal" class="form-control" placeholder="ingrese ticket" required>
                <!-- Mensaje de validación para el input requerido -->
                <div class="invalid-feedback">
                El campo es requerido.
                </div>
            </div>
            <div class="modal-footer">
                <!-- Botón "Cancelar" -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <!-- Botón "Confirmar" -->
                <button type="button" class="btn btn-success" id="btn-aprobar" data-id="0">Aprobar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para revisado-->
<div class="modal fade" id="revisadoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres dar por revisado este reporte?</p>
            </div>
            <div class="modal-footer">
                <!-- Botón "Cancelar" -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <!-- Botón "Confirmar" -->
                <button type="button" class="btn btn-success" id="btn-revisado" data-id="0">Revisado</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para fase final-->
<div class="modal fade" id="faseFinalModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres aplicar fase final?</p>
            </div>
            <div class="modal-footer">
                <!-- Botón "Cancelar" -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <!-- Botón "Confirmar" -->
                <button type="button" class="btn btn-success" id="btn-fase-final" data-id="0">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para fase preventivo-->
<div class="modal fade" id="fasePreventivoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres aplicar fase preventivo?</p>
            </div>
            <div class="modal-footer">
                <!-- Botón "Cancelar" -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <!-- Botón "Confirmar" -->
                <button type="button" class="btn btn-success" id="btn-fase-preventivo" data-id="0">Aplicar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<h4>{{ $config["title"] }}</h4>
<div class="d-flex">
    <div class="col-lg-4 col-md-4 form-group ml-auto">
        <label for="">Buscar Reporte:</label>
        <div class="d-flex">
            <select name="filter_type" class="form-control form-control-sm">
                <option value="numero_reporte_select">N° Reporte</option>
                <option value="ticket">Ticket</option>
            </select>
            <select name="numero_reporte_select" class="form-control form-control-sm slc-filter">
                <option value="">Todos</option>
                @foreach ($config["numero_reportes"] as $row)
                    <option>{{ $row->numero_de_reporte }}</option>
                @endforeach
            </select>
            <select name="ticket" class="form-control form-control-sm slc-filter">
                <option value="">Todos</option>
                @foreach ($config["tickets"] as $row)
                    <option>{{ $row->ticket }}</option>
                @endforeach
            </select>
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
            <th>Número de Reporte</th>
            <th>Ticket</th>
            <th>Reporte</th>
            <th>Fecha</th>
            <th>Fase Final</th>
            <th>Revisado</th>
            <th>Aprobado</th>
            <th>Procesado</th>
            <th>Acreditado Pre</th>
            <th>Acreditado Post</th>
            <th>En Ejecución Pre</th>
            <th>En Ejecución Post</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
{{-- </div> --}}
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
@include('includes.select2_js')
@include('includes.datatables_js')
@include('includes.jszip_js')
@include('includes.datatables_buttons_js')
<script>
let store = {};
var id,name,_datatable;

$(function() {

    _datatable = $(".tbl-informefallas").DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="la la-download"></i> Descargar tabla',
                className: 'btn btn-danger btn-sm',
                filename: 'informe_fallas',
                exportOptions: {
                    modifier: { page: 'all' },
                    columns: ':not(:last-child)'
                }
            }
        ],
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ asset('extraccion-devolucion/informe-fallas/search') }}",
            type: "GET",
            dataSrc: function(resp){
                return resp.data;
            }
        },
        columns: [
            {data: 'numero_de_reporte'},
            {data: 'ticket'},
            {data: 'name_file'},
            {data: 'fecha_carga'},
            {data: 'fase_final'},
            {data: 'revisado'},
            {data: 'aprobado'},
            {data: 'procesado'},
            {data: 'acreditado_pre'},
            {data: 'acreditado_post'},
            {data: 'en_ejecucion_pre'},
            {data: 'en_ejecucion_post'},
            {render: function(data, type, row){
                var buttonAprobar = `<div class="dropdown d-inline-block" style="position: unset;">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Aprobación
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#revisadoModal"  data-id="${row['numero_de_reporte']}"><li class="la la-check-circle text-success"></li> Revisado</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#aprobarModal"  data-id="${row['numero_de_reporte']}"><li class="la la-check-circle text-success"></li> Aprobar</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#desaprobarModal" data-id="${row['numero_de_reporte']}"><li class="la la-times-circle text-danger"></li> Desaprobar</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#enEsperaModal" data-id="${row['numero_de_reporte']}"><li class="la la-circle"></li> En Espera</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#faseFinalModal"  data-id="${row['numero_de_reporte']}"><li class="la la-check-circle text-success"></li> Fase final</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#fasePreventivoModal" data-id="${row['numero_de_reporte']}"><li class="la la-circle"></li> Fase Preventivo</a>
                    </div>
                </div>
                <div class="dropdown d-inline-block" style="position: unset;">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuEnEjecucion" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Ejecución
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuEnEjecucion">
                        <a class="dropdown-item show-alert-confirmation" href="#" data-id="${row['numero_de_reporte']}" data-name="enEjecucionPre" data-label="En Ejecución Pre">
                            <li class="la la-check-circle text-success"></li> En Ejecución Pre
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#" data-id="${row['numero_de_reporte']}" data-name="enEsperaPre" data-label="En Espera Pre">
                            <li class="la la-circle"></li> En Espera Pre
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#" data-id="${row['numero_de_reporte']}" data-name="enEjecucionPost" data-label="En Ejecución Post">
                            <li class="la la-check-circle text-success"></li> En Ejecución Post
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#" data-id="${row['numero_de_reporte']}" data-name="enEsperaPost" data-label="En Espera Post">
                            <li class="la la-circle"></li> En Espera Post
                        </a>
                    </div>
                </div>`;
                return `<button
                    class="btn btn-sm btn-info" id="descargar"
                    data-id="${row['numero_de_reporte']}"
                    data-name="${row['name_file']}"
                    data-value="descargar"><li class="la la-eye"></li> Descargar</button>
                    <button
                    class="btn btn-sm btn-danger" id="eliminar"
                    data-id="${row['numero_de_reporte']}"
                    data-value="eliminar"><li class="la la-trash"></li> Eliminar</button>
                    ${buttonAprobar}`;
            }},
        ],
        "fnDrawCallback": function() {
            _datatable.cells().nodes().each(function(cell, i) {
                if($(cell).text() === '0') {
                    $(cell).html('<li class="la la-circle"></li>');
                }
                if($(cell).text() === '1') {
                    $(cell).html('<li class="la la-check-circle text-success"></li>');
                }
                if($(cell).text() === '2') {
                    $(cell).html('<li class="la la-times-circle text-danger"></li>');
                }
            });
            $(".show-alert-confirmation").on("click", function(e){
                let id = e.target.attributes["data-id"].value;
                let name = e.target.attributes["data-name"].value;
                let label = e.target.attributes["data-label"].value;
                $("#frm-update-status input[name=status]").val(name);
                $("#frm-update-status input[name=id]").val(id);
                $("#frm-update-status .label").text(`¿Estás seguro de que quieres poner ${label} este reporte?`);
                $("#update-status-modal").modal("show");
            });
        },
        lengthChange: false,
        searching: false,
        order: [[3, 'desc']],
        scrollX: true
        //serverSide: true
    });  

    const config = @json($config);

    // inicio
    $("select[name=numero_reporte_select]").select2({width: '100%'});
    $("select[name=ticket]").select2({width: '100%'});
    setTimeout(() => {
        $("select[name=ticket]").next().hide();
    }, 100);

    function filter_sub_elements(element, callback_filter)
    {
        let sub_element = element;
        let options = sub_element.children;
        let first_value;
        options.forEach(op => {
            //console.log(op);
            let include_element = callback_filter(op);
            // let attr_value = op.attributes["data-dep"].value;
            op.classList.remove("d-none");
            if(!include_element){
                // console.log(value, attr_value);
                op.classList.add("d-none");
            } else if(!first_value){
                //console.log("new value", op.value);
                sub_element.value = op.value;
                first_value = op.value;
            }
        });
    }

    $("select[name=filter_type]")
    .on("change", function(e){
        let type = e.target.value;
        $("select[name=numero_reporte_select]").next().hide();
        $("select[name=ticket]").next().hide();
        $(`select[name=${type}]`).val('').trigger('change').next().show();
    });

    $("select[name=numero_reporte_select]")
    .on("change", function(e){
        let subUrl = e.target.value !== '' ? '/n-reporte' : '';
        _datatable.ajax.url(`{{ asset('extraccion-devolucion/informe-fallas/search') }}${subUrl}/${e.target.value}`).load();
    });
    $("select[name=ticket]")
    .on("change", function(e){
        let subUrl = e.target.value !== '' ? '/ticket' : '';
        _datatable.ajax.url(`{{ asset('extraccion-devolucion/informe-fallas/search') }}${subUrl}/${e.target.value}`).load();
    });

    $(document).on('click', '#eliminar', function() {
        id = $(this).data('id');
        $('#dialogo-eliminar').modal('show');
    });

    $('#confirmar-eliminar').on("click", function(e){
        const _token = document.querySelector('input[name=_token]').value;
        const data = new FormData();
        data.append('_token', _token);
        
        fetch(`{{ asset('extraccion-devolucion/informe-fallas/delete') }}/${id}`, {
            method: 'POST', body: data
        })
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            new Noty({
                type: 'success',
                layout: 'topRight',
                text: 'Se elimino el reporte correctamente'
            }).show();
            _datatable.ajax.reload();
        })
        .catch(error => {
            new Noty({
                type: 'error',
                layout: 'topRight',
                text: 'Ocurrió un error al eliminar el reporte'
            }).show();
        });
        $('#dialogo-eliminar').modal('hide');
    });

    $(document).on('click', '#descargar', function() {
        id = $(this).data('id');
        name = $(this).data('name');
        let downloadApi = "{{ url('extraccion-devolucion/informe-fallas/[numReporte]/download') }}";
        window.open(downloadApi.replace('[numReporte]', id),"_blank");
    });

    $('#input-modal').on('input', function() {
        var inputVal = $(this).val();
        inputVal = inputVal.replace(/[^0-9]/g, ''); // Eliminar caracteres no numéricos
        $(this).val(inputVal);

        if (inputVal.length > 9) {
          $(this).val(inputVal.slice(0, 9)); // Limitar a 9 caracteres
        }
    });

    // Agregar el valor del input al atributo data del botón
    $('#aprobarModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-aprobar').attr("data-id", value);
    });

    // Agregar el valor del input al atributo data del botón
    $('#desaprobarModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-desaprobar').attr("data-id", value);
    });

    // Agregar el valor del input al atributo data del botón
    $('#enEsperaModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-enEspera').attr("data-id", value);
    });

    // Agregar el valor del input al atributo data del botón
    $('#revisadoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-revisado').attr("data-id", value);
    });

    // Agregar el valor del input al atributo data del botón
    $('#faseFinalModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-fase-final').attr("data-id", value);
    });

    // Agregar el valor del input al atributo data del botón
    $('#fasePreventivoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var value = $(button).data('id');
        $('#btn-fase-preventivo').attr("data-id", value);
    });


    // confirmacion del modal de aprobacion
    $('#btn-aprobar').click(function() {
        var ticket = $('#input-modal').val();
        id = $(this).data('id');
        // Validar si el input está vacío
        if (ticket === '') {
            $('#input-modal').addClass('is-invalid');
            return false;
        } else {
            $('#input-modal').removeClass('is-invalid');
        }

        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/aprobar') }}',
            type: 'POST',
            data: {ticket: ticket, id: id},
            success: function(response) {
                $('#aprobarModal').modal('hide');
                console.log(response);
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se aprobó correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "El número de Ticket ya existe, no se aprobó"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    // confirmacion del modal de desaprobacion
    $('#btn-desaprobar').click(function() {
        id = $(this).data('id');

        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/desaprobar') }}',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                $('#desaprobarModal').modal('hide');
                console.log(response);
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se desaprobó correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "Ocurrio un error, no se desaprobó"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    // confirmacion del modal de desaprobacion
    $('#btn-enEspera').click(function() {
        id = $(this).data('id');

        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/en-espera') }}',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                $('#enEsperaModal').modal('hide');
                console.log(response);
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se puso en espera correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "Ocurrio un error, no se pudo poner en espera"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    // confirmacion del modal de desaprobacion
    $('#btn-revisado').click(function() {
        id = $(this).data('id');
        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/revisado') }}',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                $('#revisadoModal').modal('hide');
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se puso en revisado correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "Ocurrio un error, no se pudo poner en revisado"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    // confirmacion del modal de aprobacion
    $('#btn-fase-final').click(function() {
        numero_reporte = $(this).data('id');
        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/fase-final') }}',
            type: 'POST',
            data: {numero_reporte: numero_reporte, estado: 1},
            success: function(response) {
                $('#faseFinalModal').modal('hide');
                console.log(response);
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se estabelcio la fase correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "Ocurrio un error"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    // confirmacion del modal de aprobacion
    $('#btn-fase-preventivo').click(function() {
        numero_reporte = $(this).data('id');
        $.ajax({
            url: '{{ asset('extraccion-devolucion/informe-fallas/fase-final') }}',
            type: 'POST',
            data: {numero_reporte: numero_reporte, estado: 0},
            success: function(response) {
                $('#faseFinalModal').modal('hide');
                console.log(response);
                if(response.result){
                    _datatable.ajax.reload();
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: "Se estabelcio la fase correctamente"
                    }).show();
                }else{
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: "Ocurrio un error"
                    }).show();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                // Aquí puedes agregar código para manejar errores de la solicitud AJAX
            }
        });
    });

    $('#frm-update-status').on("submit", function(e) {
        e.preventDefault();
        $("#update-status-modal").modal("hide");
        let status = $("#frm-update-status input[name=status]").val();
        let id = $("#frm-update-status input[name=id]").val();
        $.ajax({
            url: "{{ asset('extraccion-devolucion/informe-fallas/update-status') }}",
            type: 'POST',
            data: {status: status, id: id},
            success: function(response) {
                _datatable.ajax.reload();
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Se actualizo correctamente"
                }).show();
            },
            error: function(xhr, status, error) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: "Error: "+xhr.responseText
                }).show();
            }
        });
    });

});
</script>
@endsection
