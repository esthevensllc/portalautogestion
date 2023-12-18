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
                    <label for="">Ticket_osiptel</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["tickets"] as $row)
                            <option>{{ $row->ticket }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Departamento</label>
                    <select name="departamento" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="btn-group" role="group">
                        <label for="rep_usuarios_afectados" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_usuarios_afectados" name="tipo_reporte" value="1" required>
                            Usuarios afectados
                        </label>
                        <label for="rep_postpago" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_postpago" name="tipo_reporte" value="2" required>
                            Postpago
                        </label>
                        <label for="rep_prepago" class="mb-0 btn btn-light btn-sm">
                            <input type="radio" class="" id="rep_prepago" name="tipo_reporte" value="3" required>
                            Prepago
                        </label>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="row">
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Celdas:</p>
                            <p class="d-inline input_celda"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold">Departamento provincia distrito:</p>
                            <p class="d-inline input_distrito"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Ticket osiptel:</p>
                            <p class="d-inline input_ticket"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Fecha interes:</p>
                            <p class="d-inline input_fecha_interes"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Call start time:</p>
                            <p class="d-inline input_call_start_time"></p>
                        </div>
                        <div class="col-lg-12">
                            <p class="mb-0 font-weight-bold d-inline">Periodo Corte:</p>
                            <p class="d-inline input_periodo_corte"></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
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

    $("select[name=ticket]").select2({width: '100%'});
    $("select[name=departamento]").select2({width: '100%'});

    document.querySelector(".btn-group")
    .addEventListener("click", function(e){
        let element;
        if(e.target.tagName === 'LABEL'){
            element = e.target;
        }else if(e.target.tagName === 'INPUT'){
            element = e.target.parentElement;
        }

        if(element){
            console.log(element.tagName);
            $(".btn-group .btn")
            .removeClass("btn-secondary")
            .removeClass("btn-light")
            .removeClass("active")
            .addClass("btn-light");

            $(element)
            .removeClass("btn-light")
            .addClass("btn-secondary")
            .addClass("active");
        }
    });

    $("#form_export")
    .on("change", function(e){
        if(e.target.tagName === 'SELECT'){
            const data = new FormData(document.querySelector("#form_export"));
            fetch(config.inputsApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
            .then(response => response.json())
            .then(resp => {
                let data = resp.data;
                if(data !== null){
                    document.querySelector(".input_celda").innerHTML = data.celda;
                    document.querySelector(".input_distrito").innerHTML = data.provincia.replace("\n", "<br>");
                    document.querySelector(".input_ticket").innerHTML = data.ticket;
                    document.querySelector(".input_fecha_interes").innerHTML = data.fecha_interes;
                    document.querySelector(".input_call_start_time").innerHTML = data.fecha_ini+" - "+data.fecha_fin;
                    document.querySelector(".input_periodo_corte").innerHTML = data.corte_fecha_ini+" - "+data.corte_fecha_fin;

                    let fecha1 = new Date(data.fecha_ini);
                    let fecha2 = new Date(data.fecha_fin);
                    let diff = ((fecha2.getTime() - fecha1.getTime())/1000/60).toFixed(0);
                    document.querySelector(".input_call_start_time").innerHTML += ` (${diff} minutos)`;

                    let corte_fecha1 = new Date(data.corte_fecha_ini);
                    let corte_fecha2 = new Date(data.corte_fecha_fin);
                    let corte_diff = ((corte_fecha2.getTime() - corte_fecha1.getTime())/1000/60).toFixed(0);
                    document.querySelector(".input_periodo_corte").innerHTML += ` (${corte_diff} minutos)`;
                }else{
                    document.querySelector(".input_celda").innerHTML = '';
                    document.querySelector(".input_distrito").innerHTML = '';
                    document.querySelector(".input_ticket").innerHTML = '';
                    document.querySelector(".input_fecha_interes").innerHTML = '';
                    document.querySelector(".input_call_start_time").innerHTML = '';
                    document.querySelector(".input_periodo_corte").innerHTML = '';
                }
            })
            .catch(error => {
                alert(error);
            });
        }
    });

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        utils.downloadHandler({
            url: config.url
        }, e);
    });

    document.querySelector(".btn_confirm")
    .addEventListener("click", function(e){
        let text = "Esta seguro que desea confirmar el ticket?";
        if (confirm(text) == true) {
            const data = new FormData(document.querySelector("#form_export"));
            fetch(config.confirmApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
            //.then(resp => response.json())
            .then(async(response) => {
                if(!response.ok){
                    let json_response = await response.json();
                    throw new Error(json_response.message);
                }
                // renderTable();
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Confirmado correctamente"
                }).show();
            })
            .catch(error => {
                alert(error);
            });
        }
    });

    $("select[name=ticket]")
    .on("change", function(e){
        console.log(e.target.value);
        let _html = config.departamentos
        .filter(r => `${r.ticket}` === e.target.value)
        .map(r => `<option data-ticket="${r.ticket}">${r.departamento}</option>`)
        .join("");

        document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>` + _html;
        $("select[name=departamento]").select2({width: '100%'});
    });
});
</script>
@endsection