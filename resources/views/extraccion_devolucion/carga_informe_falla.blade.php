@extends(backpack_view('blank'))

@section('after_styles')
{{-- DATA TABLES --}}
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    tbody tr td, thead, tr th{
        padding: 0.4rem !important;
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
@endsection

@section('content')

<h4>{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">N° De Reporte:</label>
                    <input type="text" class="form-control" name="numero_reporte" placeholder="Ingrese número de reporte" required/>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Subir Excel:</label>
                    <input type="file" class="form-control-file" name="excel" accept=".xlsx" required>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-danger btn-sm btn_export">Cargar archivo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="d-flex">
    <h4 class="mb-0 mr-3 pt-4">Últimos 10 Reportes</h4>
    <div class="col-lg-3 col-md-4 form-group ml-auto">
        <label for="">Buscar Reporte:</label>
        <select name="numero_reporte_select" class="form-control form-control-sm">
            <option value="">Todos</option>
            @foreach ($config["numero_reportes"] as $row)
                <option>{{ $row->numero_de_reporte }}</option>
            @endforeach
        </select>
    </div>
</div>
<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100" cellspacing="0"
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
            <th>Revisado</th>
            <th>Aprobado</th>
            <th>Procesado</th>
            <th>Acreditado Pre</th>
            <th>Acreditado Post</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
@include('includes.select2_js')

{{-- DATA TABLES SCRIPT --}}
<script type="text/javascript" src="{{ asset('packages/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader-bs4/js/fixedHeader.bootstrap4.min.js') }}"></script>
<script>
    let store = {};
    var id,name,_datatable;
    
$(function() {
    _datatable = $(".table").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ asset('extraccion-devolucion/carga-informe-fallas/search') }}",
            type: "GET",
            dataSrc: function(resp){
                //console.log(resp);
                //store.relationships = resp.relationships;
                return resp.data;
            }
        },
        columns: [
            {data: 'numero_de_reporte'},
            {data: 'ticket'},
            {data: 'name_file'},
            {data: 'fecha_carga'},
            {data: 'revisado'},
            {data: 'aprobado'},
            {data: 'procesado'},
            {data: 'acreditado_pre'},
            {data: 'acreditado_post'},
            {render: function(data, type, row){
                var buttonAprobar = `<div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Aprobación
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#revisadoModal"  data-id="${row['numero_de_reporte']}"><li class="la la-check-circle text-success"></li> Revisado</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#aprobarModal"  data-id="${row['numero_de_reporte']}"><li class="la la-check-circle text-success"></li> Aprobar</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#desaprobarModal" data-id="${row['numero_de_reporte']}"><li class="la la-times-circle text-danger"></li> Desaprobar</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#enEsperaModal" data-id="${row['numero_de_reporte']}"><li class="la la-circle"></li> En Espera</a>
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
        },
        lengthChange: false,
        searching: false,
        order: [[3, 'desc']]
        //serverSide: true
    });  

    const config = @json($config);

    // inicio
    $("select[name=numero_reporte_select]").select2({width: '100%'});

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const data = new FormData(e.target);
        fetch(config.api, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            if(!response.ok){
                let json_response = await response.json();
                throw new Error(json_response.message);
            }
            let json_response = await response.json();
            if(json_response.result){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Cargado correctamente"
                }).show();
                _datatable.ajax.reload();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: "El número de Reporte ya existe, no se cargó el archivo"
                }).show();
            }

            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error);
        });
    });

    $("select[name=numero_reporte_select]")
    .on("change", function(e){
        //console.log(e.target.value);
        /*let _html = config.numero_reportes
        .filter(r => `${r.numero_de_reporte}` === e.target.value)
        .map(r => `<option data-reporte="${r.numero_de_reporte}">${r.numero_de_reporte}</option>`)
        .join("");*/
        _datatable.ajax.url(`{{ asset('extraccion-devolucion/carga-informe-fallas/search') }}/${e.target.value}`).load();
    });

    $(document).on('click', '#eliminar', function() {
        id = $(this).data('id');
        $('#dialogo-eliminar').modal('show');
    });


    $('#confirmar-eliminar').on("click", function(e){        
        //console.log(e.target);  
        //const _token = document.querySelector('meta[name=csrf-token]').getAttribute('content');
        const _token = document.querySelector('input[name=_token]').value;
        const data = new FormData();
        data.append('_token', _token);
        
        fetch(`{{ asset('extraccion-devolucion/carga-informe-fallas/delete') }}/${id}`, {
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
        window.open(`http://172.19.192.170/portalautogestion/storage/app/carga_informe_falla/${id}_${name}`,"_blank");
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
            url: '{{ asset('extraccion-devolucion/carga-informe-fallas/aprobar') }}',
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
            url: '{{ asset('extraccion-devolucion/carga-informe-fallas/desaprobar') }}',
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
            url: '{{ asset('extraccion-devolucion/carga-informe-fallas/en-espera') }}',
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
            url: '{{ asset('extraccion-devolucion/carga-informe-fallas/revisado') }}',
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
});
</script>
@endsection