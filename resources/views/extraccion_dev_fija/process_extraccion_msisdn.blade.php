@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
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
                <h4>Se proceso correctamente la extracción con los tickets</h4>
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

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_process">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Comentario / N° De Reporte:</label>
                    <input type="text" class="form-control" name="numero_reporte" placeholder="Ingrese número de reporte" required/>
                    <div class="invalid-feedback d-block text-dark">
                        Ingrese un breve comentario sobre la extracción a realizar
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Subir Excel <a href="{{ asset('resources/plantilla_ext_fija_codcli.xlsx') }}" class="btn btn-secondary btn-sm ml-2">Plantilla</a></label>
                    <input type="file" class="form-control-file" name="excel" accept=".xlsx" required>
                </div>
                <div class="col-12"></div>
                {{-- <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Ticket</label>
                    <input type="text" class="form-control form-control-sm" name="ticket">
                    <div class="invalid-feedback d-block text-dark">
                        Ingresar el ticket de devolución si se tiene, si no se ingresa se va a generar uno aleatorio
                    </div>
                </div> --}}
                {{-- <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Servicio Afectado</label>
                        <select class="form-control form-control-sm" name="servicio_afectado_id">
                            <option value="">Seleccione</option>
                            @foreach ($config["servicio_afectado"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Corte inicio</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="fecha_ini" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="hora_ini" placeholder="00:00:00" required>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Corte fin</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="fecha_fin" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="hora_fin" placeholder="00:00:00" required>
                        <div class="invalid-feedback d-block text-dark corte_diff_label" data-min="" style="font-weight: bold;">
                            DIFENCIA EN MINUTOS: 
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Compensación</label>
                        <select class="form-control form-control-sm" name="compensacion_id">
                            <option value="">Seleccione</option>
                            @foreach ($config["compensaciones"] as $row)
                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}
                <div class="col-12">
                    <div class="table-responsive mb-3">
                        <table id="tbl_ticket" class="table table-sm table-bordered mb-0" style="min-width: 1000px;">
                            <thead class="bg-secondary">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Servicio Afectado</th>
                                    <th>Fecha ini</th>
                                    <th>Hora ini</th>
                                    <th>Fecha Fin</th>
                                    <th>Hora Fin</th>
                                    <th>Compensación</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="bg-secondary">
                                <tr>
                                    <th>
                                        <input type="text" id="ticket" class="form-control form-control-sm" value="">
                                    </th>
                                    <th>
                                        <select class="form-control form-control-sm" id="servicio_afectado">
                                            <option value="">Seleccione</option>
                                            @foreach ($config["servicio_afectado"] as $row)
                                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th><input type="date" id="fecha_ini" class="form-control form-control-sm" value=""></th>
                                    <th><input type="text" id="hora_ini" class="form-control form-control-sm" value="" placeholder="00:00:00"></th>
                                    <th><input type="date" id="fecha_fin" class="form-control form-control-sm" value=""></th>
                                    <th><input type="text" id="hora_fin" class="form-control form-control-sm" value="" placeholder="00:00:00"></th>
                                    <th>
                                        <select class="form-control form-control-sm" id="compensacion">
                                            <option value="">Seleccione</option>
                                            @foreach ($config["compensaciones"] as $row)
                                                <option value="{{ $row->id }}">{{ $row->label }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th>
                                        <button type="button" class="btn btn-success btn-sm btn_add_servicio">Agregar</button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm">Procesar</button>
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
<script src="{{asset('js/extraccion_fija.js')}}?v={{ date("YmdHis") }}"></script>
<script>
$(function() {
    const config = @json($config);

    let servicioAfectadoById = {};
    let compensacionById = {};
    config.servicio_afectado.forEach(r => {
        servicioAfectadoById[r.id] = r;
    });
    config.compensaciones.forEach(r => {
        compensacionById[r.id] = r;
    });

    let tbl_ticket = new SimpleCrudTableComponent("#tbl_ticket", {
        ticket: {type: "string"},
        servicio_afectado_id: {type: "hidden"},
        servicio_afectado: {type: "string", readonly: true},
        fecha_ini: {type: "date", readonly: true},
        hora_ini: {type: "time", readonly: true},
        fecha_fin: {type: "date", readonly: true},
        hora_fin: {type: "time", readonly: true},
        compensacion_id: {type: "hidden"},
        compensacion: {type: "string", readonly: true},
    });

    document.querySelector(".btn_add_servicio")
    .addEventListener("click", function(e){
        let servicio_afectado_id = document.querySelector("#servicio_afectado").value;
        let compensacion_id = document.querySelector("#compensacion").value;

        let to_add = {
            ticket: document.querySelector("#ticket").value,
            servicio_afectado_id: servicio_afectado_id,
            servicio_afectado: '',
            fecha_ini: document.querySelector("#fecha_ini").value,
            hora_ini: document.querySelector("#hora_ini").value,
            fecha_fin: document.querySelector("#fecha_fin").value,
            hora_fin: document.querySelector("#hora_fin").value,
            compensacion_id: compensacion_id,
            compensacion: '',
        };
        if(servicioAfectadoById[servicio_afectado_id] !== undefined){
            to_add.servicio_afectado = servicioAfectadoById[servicio_afectado_id].label;
        }
        if(compensacionById[compensacion_id] !== undefined){
            to_add.compensacion = compensacionById[compensacion_id].label;
        }
        let isInvalid = Object.keys(to_add).some(name => to_add[name] === "");
        try {
            if(isInvalid){
                throw new Error("Completar todos los campos antes de agregar un detalle");
            }
            if(tbl_ticket.getData().some(r => r.ticket === to_add.ticket)){
                throw new Error("No se puede agregar el mismo ticket mas de una vez");
            }
            if(tbl_ticket.getData().some(r => r.servicio_afectado_id === servicio_afectado_id)){
                throw new Error("No se puede agregar el mismo servicio afectado mas de una vez");
            }
            let dtFechaIni = new Date(`${to_add.fecha_ini}T${to_add.hora_ini}`);
            let dtFechaFin = new Date(`${to_add.fecha_fin}T${to_add.hora_fin}`);
            if(isNaN(dtFechaIni.getTime())){
                throw new Error("La fecha y hora de inicio no son validos");
            }
            if(isNaN(dtFechaFin.getTime())){
                throw new Error("La fecha y hora fin no son validos");
            }
            if(dtFechaIni.getTime() > dtFechaFin.getTime()){
                throw new Error("La fecha de inicio no puede ser mayor a la fecha fin");
            }
            tbl_ticket.add(to_add);
        } catch (error) {
            alert(error.message);
        }
    });

    document.querySelector("#form_process")
    .addEventListener("submit", function(e){
        e.preventDefault();

        let numDetails = tbl_ticket.getData().length;
        if(numDetails < 1){
            alert("Se debe agregar como minimo un servicio afectado");
            return;
        }

        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const body = new FormData(e.target);

        utils.fetch(`${config.api}`, {
            method: "POST",
            headers: {
                // "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: body
        })
        .then(utils.fetchErrorMiddleware)
        .then(response => response.json())
        .then(response => {
            document.querySelector(".ticket_generado").innerHTML = response.tickets.join(', ');
            $("#notification_modal").modal("show");
            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            let jsonError = JSON.parse(error.message);
            alert(jsonError.message);
        });
    });

    /*document.querySelectorAll(".corte_calcular_diff")
    .forEach(el => {
        el.addEventListener("change", function(e){
            let fecha1_date = document.querySelector("input[id=fecha_ini]").value;
            let fecha1_time = document.querySelector("input[id=hora_ini]").value;
            let fecha1 = fecha1_date+" "+fecha1_time;
            let fecha2_date = document.querySelector("input[id=fecha_fin]").value;
            let fecha2_time = document.querySelector("input[id=hora_fin]").value;
            let fecha2 = fecha2_date+" "+fecha2_time;

            fecha1 = new Date(fecha1);
            fecha2 = new Date(fecha2);
            let diff = ((fecha2.getTime() - fecha1.getTime())/1000/60).toFixed(0);
            console.log(diff);

            document.querySelector(".corte_diff_label").setAttribute("data-min", diff);
            document.querySelector(".corte_diff_label").innerHTML = "DIFENCIA EN MINUTOS: "+diff+" minutos";
        });
    });

    document.querySelector("input[id=fecha_ini]").addEventListener("change", function(e){
        let fecha2 = document.querySelector("input[id=fecha_fin]");
        fecha2.min = e.target.value;
    });
    document.querySelector("input[id=fecha_fin]").addEventListener("change", function(e){
        let fecha1 = document.querySelector("input[id=fecha_ini]");
        fecha1.max = e.target.value;
    });*/
});
</script>
@endsection