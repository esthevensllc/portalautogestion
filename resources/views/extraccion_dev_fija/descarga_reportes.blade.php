@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">N° Reporte</label>
                    <select name="numero_reporte" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Ticket</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-12"></div>
                <div class="col-lg-6 col-md-4 form-group">
                    <div class="d-inline-block pr-3">
                        <input type="radio" class="" id="rep_usuarios" name="tipo_reporte" value="1" required>
                        <label for="rep_usuarios">Usuarios Afectados</label>
                    </div>
                    <div class="d-inline-block pr-3">
                        <input type="radio" class="" id="rep_postpago" name="tipo_reporte" value="2" required>
                        <label for="rep_postpago">Postpago</label>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="row">
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Ticket:</p>
                            <p class="d-inline input_ticket"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Servicio Afectado:</p>
                            <p class="d-inline input_servicio"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Fecha Inicio:</p>
                            <p class="d-inline input_fecha_ini"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Fecha Fin:</p>
                            <p class="d-inline input_fecha_fin"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Interes Calculado Hasta:</p>
                            <p class="d-inline input_meses"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Compensación:</p>
                            <p class="d-inline input_compensacion"></p>
                        </div>
                        <div class="col-lg-12">
                            <table class="table-distritos table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Departamento</th>
                                        <th>Provincia</th>
                                        <th>Distrito</th>
                                        <th>Planos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
@include('includes.select2_js')
<script>
const config = @json($config);
const informesFalla = @json($informesFalla);
const ticketReports = @json($ticketReports);
let ticketsByNumReporte = {};
$(function() {
    // inicio

    informesFalla.forEach((row, index) => {
        let key = row["numero_reporte"];
        if(ticketsByNumReporte[key] === undefined){
            ticketsByNumReporte[key] = [];
        }
        if(ticketReports.some(r => r.ticket === row.ticket)){
            ticketsByNumReporte[key].push({ticket: row.ticket});
        }
    });

    let reportesHtml = Object.keys(ticketsByNumReporte)
    .map(numero_reporte => `<option value="${numero_reporte}">${numero_reporte}</option>`)
    .join("");

    $("select[name=numero_reporte]").html(`<option value="">Seleccione</option>` + reportesHtml);
    $("select[name=numero_reporte]").select2({width: '100%'});
    $("select[name=ticket]").select2({width: '100%'});

    $("#form_export")
    .on("change", function(e){
        if(e.target.tagName === 'SELECT'){
            const data = new FormData(document.querySelector("#form_export"));
            fetch(config.findInputApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
            .then(response => response.json())
            .then(resp => {
                let data = resp.data;
                if(data  !== null){
                    let servicioAfectadoById = {};
                    resp.serviciosAfectados.forEach(row => {
                        servicioAfectadoById[row.id] = row;
                    });
                    document.querySelector(".input_ticket").innerHTML = data.ticket;
                    document.querySelector(".input_servicio").innerHTML = servicioAfectadoById[data.servicio_afectado_id].label;
                    document.querySelector(".input_fecha_ini").innerHTML = data.fecha_ini;
                    document.querySelector(".input_fecha_fin").innerHTML = data.fecha_fin;
                    document.querySelector(".input_meses").innerHTML = data.meses;
                    document.querySelector(".input_compensacion").innerHTML = Number(data.compensacion_id) === 1 ? 'Si aplica' : 'No aplica';
                    let distritosHtml = data.planos.map(row => `<tr>
                        <td>${row.departamento}</td>
                        <td>${row.provincia}</td>
                        <td>${row.distrito}</td>
                        <td>${row.planos.map(p => p.plano).join(",")}</td>
                    </tr>`).join("");
                    document.querySelector(".table-distritos tbody").innerHTML = distritosHtml;
                    // document.querySelector(".input_planos").innerHTML = data.planos.map(row => row.plano).join("");
                }else{
                    document.querySelector(".input_ticket").innerHTML = '';
                    document.querySelector(".input_servicio").innerHTML = '';
                    document.querySelector(".input_fecha_ini").innerHTML = '';
                    document.querySelector(".input_fecha_fin").innerHTML = '';
                    document.querySelector(".input_meses").innerHTML = '';
                    document.querySelector(".input_compensacion").innerHTML = '';
                    document.querySelector(".table-distritos tbody").innerHTML = '';
                }
            })
            .catch(error => {
                alert(error);
            });
        }
    });


    $("select[name=numero_reporte]")
    .on("change", function(e){
        let _html = ticketsByNumReporte[e.target.value]
        .map(r => `<option value"${r.ticket}">${r.ticket}</option>`)
        .join("");

        document.querySelector("select[name=ticket]").innerHTML = `<option value="">Seleccione</option>` + _html;
        $("select[name=ticket]").select2({width: '100%'});
    });

    $("#form_export").on("submit", function(e){
        utils.downloadHandler({
            url: config.exportApi
        }, e);
    });
});
</script>
@endsection
