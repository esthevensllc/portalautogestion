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
                    <label for="">Ticket</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Departamento</label>
                    <select name="departamento" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="row">
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Ticket:</p>
                            <p class="d-inline input_ticket"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Departamento:</p>
                            <p class="d-inline input_departamento"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">provincia:</p>
                            <p class="d-inline input_provincia"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Distrito:</p>
                            <p class="d-inline input_distrito"></p>
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
                    </div>
                </div>
                <div class="col-lg-12 form-group d-none">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    <button type="button" class="btn btn-secondary btn-sm btn_confirm">Confirmar</button>
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
$(function() {
    // inicio
    const config = @json($config);
    const reportInputs = @json($reportInputs);

    let tickets = {};
    reportInputs.forEach((row, index) => {
        if(reportInputs[row["ticket"]] === undefined){
            reportInputs[row["ticket"]] = [];
        }
        reportInputs[row["ticket"]].push(row);
    });

    let ticketHtml = Object.keys(tickets)
    .map(ticket => `<option value="${ticket}">${ticket}</option>`)
    .join("");

    $("select[name=ticket]").html(`<option value="">Seleccione</option>` + ticketHtml);
    $("select[name=ticket]").select2({width: '100%'});
    $("select[name=departamento]").select2({width: '100%'});

    $("#form_export")
    .on("change", function(e){
        if(e.target.tagName === 'SELECT'){
            const data = new FormData(document.querySelector("#form_export"));
            fetch(config.findInputApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
            .then(response => response.json())
            .then(resp => {
                let data = resp.data;
                if(data !== null){
                    document.querySelector(".input_ticket").innerHTML = data.ticket;
                    document.querySelector(".input_departamento").innerHTML = data.departamento;
                    document.querySelector(".input_provincia").innerHTML = data.provincia;
                    document.querySelector(".input_distrito").innerHTML = data.distrito;
                    document.querySelector(".input_servicio").innerHTML = data.servicio_afectado;
                    document.querySelector(".input_fecha_ini").innerHTML = data.fecha_ini;
                    document.querySelector(".input_fecha_fin").innerHTML = data.fecha_fin;
                    document.querySelector(".input_meses").innerHTML = data.meses;
                }else{
                    document.querySelector(".input_ticket").innerHTML = '';
                    document.querySelector(".input_departamento").innerHTML = '';
                    document.querySelector(".input_provincia").innerHTML = '';
                    document.querySelector(".input_distrito").innerHTML = '';
                    document.querySelector(".input_servicio").innerHTML = '';
                    document.querySelector(".input_fecha_ini").innerHTML = '';
                    document.querySelector(".input_fecha_fin").innerHTML = '';
                    document.querySelector(".input_meses").innerHTML = '';
                }
            })
            .catch(error => {
                alert(error);
            });
        }
    });


    $("select[name=ticket]")
    .on("change", function(e){
        let _html = tickets[e.target.value]
        .map(r => `<option data-ticket="${r.ticket}">${r.departamento}</option>`)
        .join("");

        document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>` + _html;
        $("select[name=departamento]").select2({width: '100%'});
    });
});
</script>
@endsection
