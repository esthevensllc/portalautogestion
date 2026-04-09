@extends(backpack_view('blank'))

@section('after_styles')
{{-- DATA TABLES --}}
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
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
                    <input type="file" class="form-control-file" name="excel" @if(!array_key_exists('fileFormat', $config)) accept=".xlsx" @else accept="{{ $config['fileFormat'] }}" @endif required>
                </div>
                <div class="col-lg-12">
                    <h5>Extracción</h5>
                    <hr>
                </div>
                <div class="col-lg-12 form-group">
                    <div class="mb-3 row">
                        <div class="col-lg-3 col-md-4 tabs">
                            <div class="tab-item tab_1">
                                <div class="form-group">
                                    <label for="">Cells ID 2G</label>
                                    <input type="text" class="form-control form-control-sm clean_white_space" name="cell_2g">
                                    <div class="invalid-feedback d-block text-dark">
                                        Ej. 44531,44538,44539
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="">Cells ID 3G</label>
                                    <input type="text" class="form-control form-control-sm clean_white_space" name="cell_3g">
                                    <div class="invalid-feedback d-block text-dark">
                                        Ej. 12145,12146,12147
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="">Cells ID 4G</label>
                                    <input type="text" class="form-control form-control-sm clean_white_space" name="cell_4g">
                                    <div class="invalid-feedback d-block text-dark">
                                        Ej. 38711036,38711037
                                    </div>
                                </div>
                                <div class="form-group d-none">
                                    <label for="">Departamento provincia distrito</label>
                                    <textarea class="form-control form-control-sm" name="provincias"></textarea>
                                    <div class="invalid-feedback d-block text-dark">
                                        Ingrese valores separados por comas para cada distrito y salto de linea para separar entre distritos<br>
                                        Ej. PIURA,PIURA,TAMBO GRANDE<br>
                                        PIURA,PIURA,PIURA
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4">
                            <table id="tbl_ubicacion" class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Departamento</th>
                                        <th>Provincia</th>
                                        <th>Distrito</th>
                                        <th>#</th>
                                    </tr>
                                    <tr>
                                        <th>
                                            <select name="departamento" class="form-control form-control-sm">
                                                <option value="">Seleccione</option>
                                                @foreach ($config["departamentos"] as $row)
                                                    <option>{{ $row->departamento }}</option>
                                                @endforeach
                                            </select>
                                        </th>
                                        <th>
                                            <select name="provincia" class="form-control form-control-sm">
                                                <option data-dep="">Seleccione</option>
                                                @foreach ($config["provincias"] as $row)
                                                    <option data-dep="{{ $row->departamento }}">{{ $row->provincia }}</option>
                                                @endforeach
                                            </select>
                                        </th>
                                        <th>
                                            <select name="distrito" class="form-control form-control-sm">
                                            <option data-dep="" data-prov="">Seleccione</option>
                                            @foreach ($config["distritos"] as $row)
                                                <option data-dep="{{ $row->departamento }}"data-prov="{{ $row->provincia }}">{{ $row->distrito }}</option>
                                            @endforeach
                                            </select>
                                        </th>
                                        <th>
                                            <button type="button" class="btn btn-primary btn-sm btn_add_ubicacion">+</button>
                                        </th>
                                    </tr>
                                    
                                </thead>
                                <tbody></tbody>
                            </table>
                            <p class="text-danger">*Después de seleccionar el departamento, provincia y distrito dar click en el boton de agregar</p>
                        </div>

                        <div class="col-lg-3 col-md-4">
                            <div class="form-group">
                                <p class="text-bold mb-0">Periodo corte</p>
                                <label for="">Fecha inicio</label>
                                <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha1_date">
                                <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha1_time" placeholder="00:00:00">
                            </div>
                            <div class="form-group">
                                <p class="text-bold mb-0" style="visibility: hidden;">.</p>
                                <label for="">Fecha fin</label>
                                <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha2_date">
                                <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha2_time" placeholder="00:00:00">
                                <div class="invalid-feedback d-block text-dark corte_diff_label" data-min="" style="font-weight: bold;">
                                    DIFENCIA EN MINUTOS: 
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <button type="button" class="btn btn-secondary btn-sm btn-add-detalle">Agregar a detalle</button>
                        </div>
                        <div class="col-lg-6 col-md-4">                            
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label for="">FASE</label>
                            <select name="fase" class="form-control form-control-sm" required>
                                <option val="">Seleccione</option>
                                <option val="0">PREVENTIVO</option>
                                <option val="1">FINAL</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="table-responsive mb-3">
                        <table id="tbl_extraccion" class="table table-sm table-bordered mb-0" style="min-width: 1200px;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Cells ID 2G</th>
                                    <th>Cells ID 3G</th>
                                    <th>Cells ID 4G</th>
                                    <th style="min-width: 300px;">Distrito</th>
                                    <th>Fecha corte inicio</th>
                                    <th>Hora Corte inicio</th>
                                    <th>Fecha Corte fin</th>
                                    <th>Hora Corte fin</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-danger btn-sm btn_export">Cargar archivo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="d-flex">
    <h4 class="mb-0 mr-3 pt-4">Reportes</h4>
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
{{-- <div class="table-responsive"> --}}
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
{{-- </div> --}}
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
    class SimpleCrudTableComponent
    {
        constructor(selector, fields){
            this._selector = selector;
            this.fields = fields;
            this.data = [];
            this._html_by_ftype = {
                string: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" {readonly}/>',
                largeString: '<textarea name="{name}[]" rows="10" class="form-control form-control-sm" {readonly}>{value}</textarea>',
                mediumString: '<textarea name="{name}[]" rows="5" class="form-control form-control-sm" {readonly}>{value}</textarea>',
                date: '<input type="date" name="{name}[]" class="form-control form-control-sm" value="{value}" {readonly} />',
                time: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" placeholder="00:00:00" {readonly} />',
            };
            let _this = this;
            document.querySelector(`${this._selector} tbody`)
            .addEventListener("click", function(e){
                if(e.target.tagName === "BUTTON" && e.target.classList.contains("btn_delete")){
                    let _index = e.target.attributes["data-index"].value;

                    _this.deleteByEl(e.target.parentNode.parentNode);
                }
            });
        }

        add(values = {}){
            let _html_new = Object.keys(this.fields).map(field => {
                let _html = this._html_by_ftype[this.fields[field].type];
                let _readonly = (this.fields[field].readonly??false) ? "readonly" : "";
                let _value = values[field] ?? '';
                _html = _html.replace("{name}", field);
                _html = _html.replace("{value}", _value);
                _html = _html.replace("{readonly}", _readonly);
                return `<td>${_html}</td>`;
            }).join("");
            let _options_html = `<td><button type="button" class="btn btn-secondary btn-sm btn_delete" data-index="${this.data.length}">-</button></td>`;
            let _body = document.querySelector(`${this._selector} tbody`);
            let old_html = _body.innerHTML;
            let new_tr = document.createElement("tr");
            new_tr.innerHTML = `<tr>${_html_new}${_options_html}</tr>`;
            _body.appendChild(new_tr);
        }

        delete(index){
            let _body = document.querySelector(`${this._selector} tbody`);
            for (var i = 0; i < _body.childNodes.length; i++) {
                if(i === index){
                    _body.removeChild(_body.childNodes[i]);
                }
            }
        }

        deleteByEl(node){
            let _body = document.querySelector(`${this._selector} tbody`);
            _body.removeChild(node);
        }

        render(){

        }
    }

    class SimpleCrudTableComponent2
    {
        constructor(selector, fields){
            this._selector = selector;
            this.fields = fields;
            this.data = [];
            this._html_by_ftype = {
                string: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" />',
                largeString: '<textarea name="{name}[]" rows="10" class="form-control form-control-sm">{value}</textarea>',
                mediumString: '<textarea name="{name}[]" rows="5" class="form-control form-control-sm">{value}</textarea>',
                date: '<input type="date" name="{name}[]" class="form-control form-control-sm" value="{value}" />',
                time: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" placeholder="00:00:00" />',
            };
            this._handlers = {};
            this._data = [];
            let _this = this;
            document.querySelector(`${this._selector} tbody`)
            .addEventListener("click", function(e){
                if(e.target.tagName === "BUTTON" && e.target.classList.contains("btn_delete")){
                    let _index = e.target.attributes["data-index"].value;

                    _this.delete(parseInt(_index));
                    (_this._handlers["on_delete"]??[])
                    .forEach(handler => {
                        handler(_index);
                    });
                }
            });
        }

        addHandler(key, value){
            this._handlers[key] = value;
        }

        add(row){
            this._data.push(row);
            (this._handlers["on_add"]??[])
            .forEach(handler => {
                handler();
            });
            this.render();
        }

        delete(index){
            this._data = this._data.filter((r,i) => index !== i);
            this.render();
        }

        render(){
            let _html = this._data.map((row, index) => {
                let _html = Object.keys(this.fields).map(field => {
                    return `<td>${row[field]}</td>`;
                }).join("");
                _html += `<td><button type="button" class="btn btn-secondary btn-sm btn_delete" data-index="${index}">-</button></td>`;
                return `<tr>${_html}</tr>`;
            }).join("");
            let _body = document.querySelector(`${this._selector} tbody`).innerHTML = _html;
        }
    }

    _datatable = $(".tbl-informefallas").DataTable({
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
        order: [[3, 'desc']],
        scrollX: true
        //serverSide: true
    });  

    const config = @json($config);

    // inicio
    $("select[name=numero_reporte_select]").select2({width: '100%'});
    document.querySelector("select[name=departamento]")
    .dispatchEvent(new Event("change"));

    let tbl_extraccion = new SimpleCrudTableComponent("#tbl_extraccion", {
        cell2g: {type: "mediumString", readonly: true},
        cell3g: {type: "mediumString", readonly: true},
        cell4g: {type: "mediumString", readonly: true},
        distritos: {type: "mediumString", readonly: true},
        corteFechaIni: {type: "date"},
        corteHoraIni: {type: "time"},
        corteFechaFin: {type: "date"},
        corteHoraFin: {type: "time"},
    });

    let tbl_ubicacion = new SimpleCrudTableComponent2("#tbl_ubicacion", {
        departamento: {type: "string"},
        provincia: {type: "string"},
        distrito: {type: "string"},
        //plano: {type: "largeString"},
    });
    function renderProvinciasInput(){
        console.log("render provincias");
        let _value = tbl_ubicacion._data.map(row => `${row["departamento"]},${row["provincia"]},${row["distrito"]}`).join("\n");
        document.querySelector("*[name=provincias]").value = _value;
    }
    tbl_ubicacion.addHandler("on_add", [renderProvinciasInput]);
    tbl_ubicacion.addHandler("on_delete", [
        (_index) => renderProvinciasInput(),
    ]);

    document.querySelector(".btn_add_ubicacion")
    .addEventListener("click", function(e){
        let departamento = document.querySelector("select[name=departamento]").value;
        let provincia = document.querySelector("select[name=provincia]").value;
        let distrito = document.querySelector("select[name=distrito]").value;
        let is_added = tbl_ubicacion._data.some(row => row["provincia"] === provincia && row["distrito"] === distrito);
        if(is_added){
            alert("La provincia ya esta agregada");
        } else {
            tbl_ubicacion.add({
                departamento: departamento,
                provincia: provincia,
                distrito: distrito
            });
        }
    });

    document.querySelectorAll(".corte_calcular_diff")
    .forEach(el => {
        el.addEventListener("change", function(e){
            let fecha1_date = document.querySelector("input[name=corte_fecha1_date]").value;
            let fecha1_time = document.querySelector("input[name=corte_fecha1_time]").value;
            let fecha1 = fecha1_date+" "+fecha1_time;
            let fecha2_date = document.querySelector("input[name=corte_fecha2_date]").value;
            let fecha2_time = document.querySelector("input[name=corte_fecha2_time]").value;
            let fecha2 = fecha2_date+" "+fecha2_time;

            fecha1 = new Date(fecha1);
            fecha2 = new Date(fecha2);
            let diff = ((fecha2.getTime() - fecha1.getTime())/1000/60).toFixed(0);
            console.log(diff);

            document.querySelector(".corte_diff_label").setAttribute("data-min", diff);
            document.querySelector(".corte_diff_label").innerHTML = "DIFENCIA EN MINUTOS: "+diff+" minutos";
        });
    });

    document.querySelector("input[name=corte_fecha1_date]").addEventListener("change", function(e){
        let fecha2 = document.querySelector("input[name=corte_fecha2_date]");
        fecha2.min = e.target.value;
    });
    document.querySelector("input[name=corte_fecha2_date]").addEventListener("change", function(e){
        let fecha1 = document.querySelector("input[name=corte_fecha1_date]");
        fecha1.max = e.target.value;
    });

    $(".btn-add-detalle").on("click", function(e){
        let cell_2g = $("input[name=cell_2g]").val();
        let cell_3g = $("input[name=cell_3g]").val();
        let cell_4g = $("input[name=cell_4g]").val();
        let distritos = $("*[name=provincias]").val();
        let fecha_corte_ini = $("*[name=corte_fecha1_date]").val();
        let hora_corte_ini = $("*[name=corte_fecha1_time]").val();
        let fecha_corte_fin = $("*[name=corte_fecha2_date]").val();
        let hora_corte_fin = $("*[name=corte_fecha2_time]").val();
        let minutes_diff = document.querySelector(".corte_diff_label").attributes["data-min"].value;
        minutes_diff = parseInt(minutes_diff);
        if(hora_corte_fin !== ''){
            if(isNaN(minutes_diff)){
                alert("La diferencia de minutos no es valida");
                return;
            }
            if(minutes_diff <= 0){
                alert("La diferencia de minutos debe ser mayor a cero");
                return;
            }
            if(cell_2g === "" && cell_3g === "" && cell_4g === ""){
                alert("Debe ingresar almenos un Cells ID en 2G,3G o 4G");
                return;
            }
            let cellExpression = /^[0-9,]+$/;
            if(cell_2g !== "" && !cell_2g.match(cellExpression)){
                alert("Las Cells ID 2G solo pueden contener numeros separados por comas");
                return;
            }
            if(cell_3g !== "" && !cell_3g.match(cellExpression)){
                alert("Las Cells ID 3G solo pueden contener numeros separados por comas");
                return;
            }
            if(cell_4g !== "" && !cell_4g.match(cellExpression)){
                alert("Las Cells ID 4G solo pueden contener numeros separados por comas");
                return;
            }
            let numDistritos = document.querySelectorAll("#tbl_ubicacion tbody tr").length;
            if(numDistritos < 1){
                alert("Se debe agregar como minimo un distrito");
                return;
            }
            tbl_extraccion.add({
                cell2g: cell_2g,
                cell3g: cell_3g,
                cell4g: cell_4g,
                distritos: distritos,
                corteFechaIni: fecha_corte_ini,
                corteHoraIni: hora_corte_ini,
                corteFechaFin: fecha_corte_fin,
                corteHoraFin: hora_corte_fin
            });
            $("input[name=cell_2g]").val('');
            $("input[name=cell_3g]").val('');
            $("input[name=cell_4g]").val('');
            // $("*[name=provincias]").val('');
            $("*[name=corte_fecha1_date]").val('');
            $("*[name=corte_fecha1_time]").val('');
            $("*[name=corte_fecha2_date]").val('');
            $("*[name=corte_fecha2_time]").val('');
            document.querySelector("input[name=corte_fecha1_date]").dispatchEvent(new Event("change"));
            document.querySelector("input[name=corte_fecha2_date]").dispatchEvent(new Event("change"));
        }else{
            alert("Antes de agregar completar todos los datos");
        }
    });

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

    document.querySelector("select[name=departamento]")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("select[name=provincia]");
        filter_sub_elements(sub_element, op => op.attributes["data-dep"].value === e.target.value || op.attributes["data-dep"].value === "");
        sub_element.dispatchEvent(new Event("change"));
    });

    document.querySelector("select[name=provincia]")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("select[name=distrito]");
        let option;
        document.querySelector("select[name=provincia]")
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

        sub_element.innerHTML = `<option data-dep="" data-prov="">Seleccione</option>`+_new_html;

        /*filter_sub_elements(sub_element, op => 
            (option.attributes["data-dep"].value === op.attributes["data-dep"].value
            && e.target.value === op.attributes["data-prov"].value)
            || op.attributes["data-dep"].value === ""
        );*/
        //sub_element.dispatchEvent(new Event("change"));
    });

    $(".clean_white_space").on("change", function(e){
        e.target.value = e.target.value.replaceAll(" ", "");
    });

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let numDetails = document.querySelectorAll("#tbl_extraccion tbody tr").length;
        if(numDetails < 1){
            alert("Se debe agregar como minimo un detalle");
            return;
        }
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
        window.open(config.downloadApi.replace('[numReporte]', id),"_blank");
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