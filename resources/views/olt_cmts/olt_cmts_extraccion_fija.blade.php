@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@include('includes.datatables_css')
@endsection

@section('header')
<div class="modal fade" id="notification_modal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="staticBackdropLabel" style="color: var(--green);">Procesado Correctamente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4>Se proceso correctamente la extracción con el número de reporte</h4>
                <h2 class="ticket_generado"></h2>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <input type="hidden" name="ticket">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select id="type_id" class="form-control form-control-sm slc-list-values" name="type_id" required>
                            <option value="">Seleccione</option>
                            @foreach ($config["types"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group tab-item">
                        <label for="">Fecha inicio afectación</label>
                        <input
                            type="date"
                            class="form-control form-control-sm slc-list-values"
                            name="fecha_ini"
                            min="{{ $config['oltSummary']->fecha_min }}"
                            max="{{ $config['oltSummary']->fecha_max }}"
                            required>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            name="hora_ini"
                            maxlength="8"
                            placeholder="00:00:00"
                            required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group tab-item">
                        <label for="">Fecha fin afectación</label>
                        <input
                            type="date"
                            class="form-control form-control-sm slc-list-values"
                            name="fecha_fin"
                            min="{{ $config['oltSummary']->fecha_min }}"
                            max="{{ $config['oltSummary']->fecha_max }}"
                            required>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            name="hora_fin"
                            maxlength="8"
                            placeholder="00:00:00"
                            required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Values</label>
                        <select id="slc-values" class="form-control form-control-sm" name="values[]" multiple required>
                            @foreach ($config["values"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">N° De Reporte</label>
                    <input type="text" class="form-control form-control-sm" name="numero_reporte" required>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4">
                    <div class="">
                        <label id="fecha_min" class="d-block">Fecha mínima: {{ $config["oltSummary"]->fecha_min }}</label>
                        <label id="fecha_maxima" class="d-block">Fecha máxima: {{ $config["oltSummary"]->fecha_max }}</label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Procesar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
            </div>
        </form>

        <table
        class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-documentlog" cellspacing="0">
            <thead class="bg-danger">
                <tr>
                    <th>Numero de Reporte</th>
                    <th>Servicio Afectado</th>
                    <th>Ticket</th>
                    <th>Reporte</th>
                    <th>Fecha</th>
                    <th>Username</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.select2_js')
@include('includes.utils_js')
@include('includes.datatables_js')
<script>
$(function() {
    const config = @json($config);
    let servicioAfectadoById = {};
    config.serviciosAfectados.forEach(r => {
        servicioAfectadoById[r.id] = r;
    });

    $("#slc-values").select2({width: '100%'});

    let _datatable = $(".tbl-documentlog").DataTable({
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
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.searchApi,
            type: "GET",
        },
        columns: [
            {data: 'numero_reporte'},
            {render: function(data, type, row){
                return (servicioAfectadoById[row.servicio_afectado_id] ?? {}).label;
            }},
            {data: 'ticket'},
            {data: 'name_file'},
            {data: 'fecha_carga'},
            {data: 'username'},
        ],
        "fnDrawCallback": function() {
            
        },
        lengthChange: false,
        searching: true,
        order: [[4, 'desc']],
        scrollX: true
        //serverSide: true
    });

    $("#form_export").on('submit', function(e){
        e.preventDefault();

        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const data = new FormData(e.target);

        utils.downloadHandler({
            url: config.url,
            requestOptions: {
                headers: {Accept: "application/json"},
            },
            successCallback: () => {
                document.querySelector(".ticket_generado").innerHTML = data.get('numero_reporte');
                $("#notification_modal").modal("show");
                loader_component.style.display = 'none';

                _datatable.ajax.reload();
            }
        }, e);

        /*
        utils.fetch(config.url, {
            method: 'POST',
            headers: {Accept: "application/json"},
            body: data
        })
        .then(utils.fetchErrorMiddleware)
        .then(response => response.json())
        .then(response => {
            $("#form_export input[name=ticket]").val(response.tickets.join(','));
            utils.downloadHandler({
                url: config.exportFinalApi
            }, e);

            document.querySelector(".ticket_generado").innerHTML = response.numeroReporte;
            $("#notification_modal").modal("show");
            loader_component.style.display = 'none';

            _datatable.ajax.reload();
        })
        .catch(error => {
            loader_component.style.display = 'none';
            let jsonError = JSON.parse(error.message);
            alert(jsonError.message);
        });*/
    });

    $(".slc-list-values").on("change", function(){
        let type_id = $("select[name=type_id]").val();
        let fecha = $("input[name=fecha_ini]").val();
        if(type_id === "" || fecha === ""){
            $("#slc-values").html(``);
            $("#slc-values").select2({width: '100%'});
            return;
        }
        utils.fetch(`${config.findValuesApi}?type_id=${type_id}&fecha=${fecha}`)
        .then(resp => resp.json())
        .then(resp => {
            let html = resp.data.map(row => `<option value="${row.id}">${row.label}</option>`).join("");
            $("#slc-values").html(`<option value="all">Todos</option>${html}`);
            $("#slc-values").select2({width: '100%'});
        })
        .catch(error => {
            $("#slc-values").html(``);
            $("#slc-values").select2({width: '100%'});
        });
    });
});
</script>
@endsection