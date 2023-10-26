@extends(backpack_view('blank'))

@section('after_styles')
@include("includes.datatables_css")
@include('includes.select2_css')
@endsection

@section('header')
@endsection

@section('content')

<h4>{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="create_form">
            @csrf
            <div class="row">
                <div class="col-3">
                    <div class="form-group mb-3">
                        <label for="">N° de Reporte</label>
                        <input type="text" class="form-control form-control-sm" name="num_reporte" required>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group mb-3">
                        <label for="">Subir Excel</label>
                        <input type="file" class="form-control-file" name="excel" accept=".xlsx" required>
                    </div>
                </div>
                <div class="col-12">
                    <h5>Planos</h5>
                    <div class="table-responsive mb-3">
                        <table id="tbl_ubicacion" class="table table-sm table-bordered mb-0" style="min-width: 1000px;">
                            <thead class="bg-secondary">
                                <tr>
                                    <th class="th_departemento">Departamento</th>
                                    <th>Provincia</th>
                                    <th>Distrito</th>
                                    <th>Plano</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="bg-secondary">
                                <tr>
                                    <th>
                                        <select class="form-control form-control-sm" id="departamento">
                                            <option value="">Seleccione</option>
                                            @foreach ($config["departamentos"] as $row)
                                                <option  value="{{ $row->departamento }}">{{ $row->departamento }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control form-control-sm" id="provincia">
                                            <option value="">Seleccione</option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control form-control-sm" id="distrito">
                                            <option value="">Seleccione</option>
                                        </select>
                                    </th>
                                    <th><textarea id="plano" rows="5" class="form-control form-control-sm"></textarea></th>
                                    <th>
                                        <button type="button" class="btn btn-success btn-sm btn_add_plano">Agregar</button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <h5>Servicios Afectados</h5>
                    <div class="table-responsive mb-3">
                        <table id="tbl_ticket" class="table table-sm table-bordered mb-0" style="min-width: 1000px;">
                            <thead class="bg-secondary">
                                <tr>
                                    <th>Servicio Afectado</th>
                                    <th>Fecha ini</th>
                                    <th>Hora ini</th>
                                    <th>Fecha Fin</th>
                                    <th>Hora Fin</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="bg-secondary">
                                <tr>
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
                                        <button type="button" class="btn btn-success btn-sm btn_add_servicio">Agregar</button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-primary btn-sm">Registrar Informe de Fallas</button>
                </div>
            </div>
        </form>
    </div>
    
</div>
{{-- </div> --}}
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
@include('includes.select2_js')
@include("includes.datatables_js")
<script src="{{asset('js/extraccion_fija.js')}}?v={{ date("YmdHis") }}"></script>
<script>
let store = {};
var id,name,_datatable;

//$(function() {
    const config = @json($config);
    let servicioAfectadoById = {};
    config.servicio_afectado.forEach(r => {
        servicioAfectadoById[r.id] = r;
    });

    // inicio
    let tbl_ubicacion = new SimpleCrudTableComponent("#tbl_ubicacion", {
        departamento: {type: "string", readonly: true},
        provincia: {type: "string", readonly: true},
        distrito: {type: "string", readonly: true},
        plano: {type: "mediumString"},
    });
    let tbl_ticket = new SimpleCrudTableComponent("#tbl_ticket", {
        //ticket: {type: "string"},
        servicio_afectado_id: {type: "hidden"},
        servicio_afectado: {type: "string", readonly: true},
        fecha_ini: {type: "date", readonly: true},
        hora_ini: {type: "time", readonly: true},
        fecha_fin: {type: "date", readonly: true},
        hora_fin: {type: "time", readonly: true},
    });

    document.querySelector("#tbl_ubicacion .btn_add_plano")
    .addEventListener("click", function(e){
        let to_add = {
            departamento: document.querySelector("#departamento").value,
            provincia: document.querySelector("#provincia").value,
            distrito: document.querySelector("#distrito").value,
            plano: document.querySelector("#plano").value
            .replaceAll("\n", "")
            .replaceAll("\t", "")
            .replaceAll(" ", ""),
        };
        let isInvalid = Object.keys(to_add).some(name => to_add[name] === "");
        try {
            if(isInvalid){
                throw new Error("Completar todos los campos antes de agregar");
            }
            if(tbl_ubicacion.getData().some(r => r.provincia === to_add.provincia && r.distrito === to_add.distrito)){
                throw new Error("No se puede agregar el mismo distrito mas de una vez");
            }
            tbl_ubicacion.add(to_add);
            let departamentoEl = document.querySelector("#departamento");
            departamentoEl.value = '';
            departamentoEl.dispatchEvent(new Event("change"));
            document.querySelector("#plano").value = '';
        } catch (error) {
            alert(error.message);
        }
    });

    document.querySelector(".btn_add_servicio")
    .addEventListener("click", function(e){
        //let distritos = tbl_ubicacion.getData();
        //console.log(distritos);
        //let distritosInput = distritos.map(row => `${row._departamento},${row._provincia},${row._distrito}`).join("\n");
        //let planosInput = distritos.map(row => `${row._plano.replaceAll("\n", "").replaceAll("\r", "")}`).join("\n");

        let servicio_afectado_id = document.querySelector("#servicio_afectado").value;

        let to_add = {
            servicio_afectado_id: servicio_afectado_id,
            servicio_afectado: '',
            fecha_ini: document.querySelector("#fecha_ini").value,
            hora_ini: document.querySelector("#hora_ini").value,
            fecha_fin: document.querySelector("#fecha_fin").value,
            hora_fin: document.querySelector("#hora_fin").value
        };
        if(servicioAfectadoById[servicio_afectado_id] !== undefined){
            to_add.servicio_afectado = servicioAfectadoById[servicio_afectado_id].label;
        }
        let isInvalid = Object.keys(to_add).some(name => to_add[name] === "");
        try {
            if(isInvalid){
                throw new Error("Completar todos los campos antes de agregar un detalle");
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

    // departamento - distrito events
    document.querySelector("#departamento")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("#provincia");
        let _new_html = config["provincias"].filter(op => op.departamento === e.target.value
        || op.departamento === "").map(row => `<option value="${row.provincia}" data-dep="${row.departamento}">${row.provincia}</option>`)
        .join("");
        sub_element.innerHTML = `<option value="" data-dep="">Seleccione</option>${_new_html}`;
        sub_element.dispatchEvent(new Event("change"));
    });

    document.querySelector("#provincia")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("#distrito");
        let option;
        document.querySelector("#provincia")
        .children
        .forEach(op => {
            if(op.value === e.target.value){
                option = op;
            }
        });

        let _new_html = config["distritos"].filter(op => 
            (option.attributes["data-dep"].value === op.departamento
            && e.target.value === op.provincia)
            || op.departamento === ""
        ).map(row => `<option data-dep="${row.departamento}"data-prov="${row.provincia}">${row.distrito}</option>`)
        .join("");

        sub_element.innerHTML = `<option data-dep="" data-prov="">Seleccione</option>${_new_html}`;
        sub_element.dispatchEvent(new Event("change"));
    });

    document.querySelector("#create_form")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let numDetails = tbl_ubicacion.getData().length;
        if(numDetails < 1){
            alert("Se debe agregar como minimo un plano y distrito");
            return;
        }
        numDetails = tbl_ticket.getData().length;
        if(numDetails < 1){
            alert("Se debe agregar como minimo un servicio afectado");
            return;
        }
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const data = new FormData(e.target);
        utils.fetch(config.createApi, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            let isOk = response.ok;
            let json = await response.json();
            if(isOk){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Cargado correctamente"
                }).show();
                // _datatable.ajax.reload();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: json.message
                }).show();
            }

            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error.message);
        });
    });
        
// });
</script>
@endsection