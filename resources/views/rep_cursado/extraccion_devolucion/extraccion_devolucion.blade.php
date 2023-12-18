@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <input type="hidden" name="step" value="1">
            <input type="hidden" name="tipo_input" value="1">
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
                            <textarea class="form-control form-control-sm" name="provincias" required></textarea>
                            <div class="invalid-feedback d-block text-dark">
                                Ingrese valores separados por comas para cada distrito y salto de linea para separar entre distritos<br>
                                Ej. PIURA,PIURA,TAMBO GRANDE<br>
                                PIURA,PIURA,PIURA
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-4">
                    <div class="form-group">
                        
                    </div>
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
                <!-- <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <p class="text-bold mb-0">Call Start Time</p>
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm calcular_diff" name="fecha1_date" required>
                        <input type="text" class="form-control form-control-sm calcular_diff" name="fecha1_time" placeholder="00:00:00" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                    <p class="text-bold mb-0" style="visibility: hidden;">.</p>
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm calcular_diff" name="fecha2_date" required>
                        <input type="text" class="form-control form-control-sm calcular_diff" name="fecha2_time" placeholder="00:00:00" required>
                        <div class="invalid-feedback d-block text-dark diff_label" style="font-weight: bold;">
                            DIFENCIA EN MINUTOS: 
                        </div>
                    </div>
                </div> -->
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Ticket_osiptel</label>
                        <input type="text" class="form-control form-control-sm clean_white_space" name="ticket_osiptel" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 202221703
                        </div>
                    </div>
                </div>
                {{-- <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Interés calculado hasta(meses)</label>
                        <input type="number" class="form-control form-control-sm" name="fecha_interes" min="0" required>
                    </div>
                </div> --}}

                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <p class="text-bold mb-0">Periodo corte</p>
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha1_date" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha1_time" placeholder="00:00:00" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                    <p class="text-bold mb-0" style="visibility: hidden;">.</p>
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha2_date" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha2_time" placeholder="00:00:00" required>
                        <div class="invalid-feedback d-block text-dark corte_diff_label" style="font-weight: bold;">
                            DIFENCIA EN MINUTOS: 
                        </div>
                    </div>
                </div>
                {{-- <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Minutos para calcular la cantidad de usuarios afectados:</label>
                        <input type="number" class="form-control form-control-sm calcular_diff" name="minutos_usuarios" min="0" required>
                    </div>
                </div> --}}

                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Procesar</button>
                    </div>
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
<script>
const config = @json($config);
let tbl_ubicacion;
$(function() {
    class SimpleCrudTableComponent
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

    

    tbl_ubicacion = new SimpleCrudTableComponent("#tbl_ubicacion", {
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

    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item").hide();
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()).show();
    });
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item input").prop('disabled', true);
        $(".tabs .tab-item textarea").prop('disabled', true);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" input").prop('disabled', false);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" textarea").prop('disabled', false);
    });
    $("#tabs_select").trigger('change');

    document.querySelectorAll(".calcular_diff")
    .forEach(el => {
        el.addEventListener("change", function(e){
            let fecha1_date = document.querySelector("input[name=fecha1_date]").value;
            let fecha1_time = document.querySelector("input[name=fecha1_time]").value;
            let fecha1 = fecha1_date+" "+fecha1_time;
            let fecha2_date = document.querySelector("input[name=fecha2_date]").value;
            let fecha2_time = document.querySelector("input[name=fecha2_time]").value;
            let fecha2 = fecha2_date+" "+fecha2_time;

            fecha1 = new Date(fecha1);
            fecha2 = new Date(fecha2);
            let diff = ((fecha2.getTime() - fecha1.getTime())/1000/60).toFixed(0);
            console.log(diff);

            document.querySelector(".diff_label").innerHTML = "DIFENCIA EN MINUTOS: "+diff+" minutos";
        });
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

            document.querySelector(".corte_diff_label").innerHTML = "DIFENCIA EN MINUTOS: "+diff+" minutos";
        });
    });

    document.querySelector("#form_export")
    .addEventListener("submit", async function(e){
        e.preventDefault();
        const button = document.querySelector(".btn_export");
        const loader_component = document.querySelector('.loader_component');
        const old_button_text = button.innerHTML;
        button.innerHTML = 'Cargando ...';
        loader_component.style.display = 'block';
        try{
            const data = new FormData(e.target);
            const response = await utils.fetch(config.url, {method: 'POST', body: data, headers: {accept: "application/json"}});
            let _json = {"result": null};
            if(!response.ok){
                _json = await response.json();
                throw new Error(_json.message);
            }else{
                _json = await response.json();
            }

            if(confirm(`La cantidad de usuarios afectados es ${_json.result} desea continuar?`) === true){
                data.set("step", "2");
                const response2 = await utils.fetch(config.url, {method: 'POST', body: data, headers: {accept: "application/json"}});
                if(!response2.ok){
                    _json = await response2.json();
                    throw new Error(_json.message);
                    
                }
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Procesado correctamente"
                }).show();
            }
        } catch(error) {
            alert(error);
        }
        button.innerHTML = old_button_text;
        loader_component.style.display = 'none';
    });

    //alert("Antes de procesar revisar que la tabla de interes este actualizada al dia anterior");

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

    document.querySelector("select[name=departamento]")
    .dispatchEvent(new Event("change"));


    $(".clean_white_space").on("change", function(e){
        e.target.value = e.target.value.replaceAll(" ", "");
    });

});
</script>
@endsection