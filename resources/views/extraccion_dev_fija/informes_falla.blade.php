@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
    .dataTables_scrollBody{
        position: unset !important;
    }
</style>
@endsection

@section('header')
<div class="modal" id="update-status-modal" tabindex="-1" role="dialog">
    <form id="frm-update-status">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">¿Estás seguro?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="status" value="">
                    <input type="hidden" name="numero_reporte" value="">
                    <input type="hidden" name="servicio_afectado_id" value="">
                    <p class="label">¿Estás seguro de que quieres eliminar este reporte?</p>
                    <div class="extra-content">
                        <input type="text" class="form-control" name="ticket" placeholder="ingrese Ticket" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
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
            <th>Revisado</th>
            <th>Aprobado</th>
            <th>Procesado</th>
            <th>En Ejecución</th>
            <th>Acreditado</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.datatables_js')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);
    let servicioAfectadoById = {};
    config.serviciosAfectados.forEach(r => {
        servicioAfectadoById[r.id] = r;
    });

    let _datatable = $(".tbl-documentlog").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
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
            {data: 'revisado'},
            {data: 'aprobado'},
            {data: 'procesado'},
            {data: 'en_ejecucion'},
            {data: 'acreditado'},
            {render: function(data, type, row){
                let html = `<button
                        class="btn btn-sm btn-info btn-download"
                        data-id="${row['numero_reporte']}"
                        data-name="${row['name_file']}"
                        data-value="descargar"><li class="la la-eye"></li> Descargar</button>
                    <button
                        class="btn btn-sm btn-danger btn-delete"
                        data-id="${row['numero_reporte']}"
                        data-servicioafectadoid="${row['servicio_afectado_id']}"
                        data-value="eliminar"><li class="la la-trash"></li> Eliminar</button>
                    <div class="dropdown d-inline-block" style="position: unset;">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Aprobación
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="revisado">
                            <li class="la la-check-circle text-success"></li> Revisado
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="aprobar">
                            <li class="la la-check-circle text-success"></li> Aprobar
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="desaprobar">
                            <li class="la la-times-circle text-danger"></li> Desaprobar
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="enEspera">
                            <li class="la la-circle"></li> En Espera
                        </a>
                    </div>
                </div>
                <div class="dropdown d-inline-block" style="position: unset;">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuEnEjecucion" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Ejecución
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuEnEjecucion">
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="enEjecucion" data-label="En Ejecución">
                            <li class="la la-check-circle text-success"></li> En Ejecución
                        </a>
                        <a class="dropdown-item show-alert-confirmation" href="#"
                            data-id="${row['numero_reporte']}" data-servicioafectadoid="${row['servicio_afectado_id']}" data-name="enEsperaEjecucion" data-label="En Espera">
                            <li class="la la-circle"></li> En Espera
                        </a>
                    </div>
                </div>`;
                return html;
            }},
        ],
        "fnDrawCallback": function() {
            _datatable.cells().nodes().each(function(cell, i) {
                if($(cell).text() === '0') {
                    $(cell).html('<li class="la la-circle"></li>');
                }else if($(cell).text() === '1') {
                    $(cell).html('<li class="la la-check-circle text-success"></li>');
                }else if($(cell).text() === '2') {
                    $(cell).html('<li class="la la-times-circle text-danger"></li>');
                }
            });
            $(".btn-download").on("click", function(e){
                let numReporte = $(this).attr("data-id");
                let servicioafectadoid = $(this).attr("data-servicioafectadoid");
                window.open(config.downloadApi.replace("[numReporte]", numReporte)
                .replace("[servicioAfectadoId]", servicioafectadoid), '_blank');
            });
            $(".btn-delete").on("click", function(e){
                let numReporte = $(this).attr("data-id");
                let servicioafectadoid = $(this).attr("data-servicioafectadoid");
                let _token = $("input[name=_token]").val();
                if(confirm(`¿Estas seguro que quieres eliminar el informe de fallas ${numReporte}?`)){
                    utils.fetch(config.deleteApi.replace("[numReporte]", numReporte).replace("[servicioAfectadoId]", servicioafectadoid), {
                        method: "POST",
                        headers: {"Content-Type": "application/json", "Accept": "application/json"},
                        body: JSON.stringify({_token: _token})
                    })
                    .then(async (resp) => {
                        let isOK = resp.ok;
                        let json = await resp.json();
                        if(isOK){
                            _datatable.ajax.reload();
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: "Se elimino correctamente"
                            }).show();
                        }else{
                            new Noty({
                                type: 'error',
                                layout: 'topRight',
                                text: "Error: "+json.message
                            }).show();
                        }
                    });
                }
            });
            $(".show-alert-confirmation").on("click", function(e){
                let alertByStatus = {
                    revisado: {label: "dar por Revisado", btnClass: "btn-success"},
                    aprobar: {label: "Aprobar", btnClass: "btn-success"},
                    desaprobar: {label: "Desaprobar", btnClass: "btn-danger"},
                    enEspera: {label: "poner En Espera", btnClass: "btn-danger"},
                    enEjecucion: {label: "poner En Ejecución", btnClass: "btn-primary"},
                    enEsperaEjecucion: {label: "poner En Espera Ejecución", btnClass: "btn-primary"},
                };
                let id = e.target.attributes["data-id"].value;
                let servicioAfectadoId = e.target.attributes["data-servicioafectadoid"].value;
                let name = e.target.attributes["data-name"].value;
                let label = alertByStatus[name].label;
                let btnClass = alertByStatus[name].btnClass;
                $("#frm-update-status input[name=status]").val(name);
                $("#frm-update-status input[name=numero_reporte]").val(id);
                $("#frm-update-status input[name=servicio_afectado_id]").val(servicioAfectadoId);
                $("#frm-update-status .label").text(`¿Estás seguro de que quieres ${label} este reporte?`);
                // <input type="text" class="form-control" placeholder="ingrese Ticket" required>
                if(name === 'aprobar'){
                    $("#frm-update-status .extra-content").html(`<input type="text" class="form-control" placeholder="Ingrese Ticket" name="ticket" required>`);
                }else{
                    $("#frm-update-status .extra-content").html(``);
                }
                $("#frm-update-status button[type=submit]")
                .removeClass("btn-success")
                .removeClass("btn-danger")
                .removeClass("btn-primary")
                .addClass(`${btnClass}`);
                $("#update-status-modal").modal("show");
            });
        },
        lengthChange: false,
        searching: true,
        order: [[0, 'desc']],
        scrollX: true
        //serverSide: true
    });

    $('#frm-update-status').on("submit", function(e) {
        e.preventDefault();
        $("#update-status-modal").modal("hide");
        // let status = $("#frm-update-status input[name=status]").val();
        // let numero_reporte = $("#frm-update-status input[name=numero_reporte]").val();
        let formData = new FormData(e.target);
        utils.fetch(config.updateStatusApi, {
            method: "POST",
            headers: {"Accept": "application/json"},
            body: formData
        })
        .then(async (resp) => {
            let isOK = resp.ok;
            let json = await resp.json();
            if(isOK){
                _datatable.ajax.reload();
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Se actualizo correctamente"
                }).show();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: "Error: "+json.message
                }).show();
            }
        });
    });
});
</script>
@endsection