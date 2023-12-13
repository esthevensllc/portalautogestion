@extends(backpack_view('blank'))

@section('after_styles')
<style>
    /*#tbl_ubicacion tbody input[name=departamento]{
        max-width: 100px !important;
    }*/
</style>
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-12">
                    <table id="tbl_ubicacion" class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th class="th_departemento">Departamento</th>
                                <th>Provincia</th>
                                <th>Distrito</th>
                                <th>Plano</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th>
                                    <select class="form-control form-control-sm" name="_departamento" id="">
                                        <option value="">Seleccione</option>
                                        @foreach ($config["departamentos"] as $row)
                                            <option  value="{{ $row->departamento }}">{{ $row->departamento }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm" name="_provincia" id="">
                                        <option value="">Seleccione</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm" name="_distrito" id="">
                                        <option value="">Seleccione</option>
                                    </select>
                                </th>
                                <th><textarea name="_plano" rows="5" class="form-control form-control-sm"></textarea></th>
                                <th>
                                    <button type="button" class="btn btn-success btn-sm btn_add">Agregar</button>
                                </th>
                            </tr>
                        </tfoot>
                    </table>

                    <table id="tbl_ticket" class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Ticket</th>
                                <th>Servicio Afectado</th>
                                <th>Fecha ini</th>
                                <th>Hora ini</th>
                                <th>Fecha Fin</th>
                                <th>Hora Fin</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="tfoot-light">
                            <tr>
                                <th><input type="text" name="ticket" class="form-control form-control-sm" value=""></th>
                                <th>
                                    <select class="form-control form-control-sm" name="servicio_afectado" id="">
                                        @foreach ($config["servicio_afectado"] as $row)
                                            <option>{{ $row->osiptel }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th><input type="date" name="fecha_ini" class="form-control form-control-sm" value=""></th>
                                <th><input type="text" name="hora_ini" class="form-control form-control-sm" value="" placeholder="00:00:00"></th>
                                <th><input type="date" name="fecha_fin" class="form-control form-control-sm" value=""></th>
                                <th><input type="text" name="hora_fin" class="form-control form-control-sm" value="" placeholder="00:00:00"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Interes calculado hasta(meses)</label>
                    <input type="number" class="form-control form-control-sm" name="meses_interes" required>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Procesar</button>
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

const config = @json($config);
let tbl_ubicacion = new SimpleCrudTableComponent("#tbl_ubicacion", {
    departamento: {type: "string", readonly: true},
    provincia: {type: "string", readonly: true},
    distrito: {type: "string", readonly: true},
    plano: {type: "mediumString"},
});
let tbl_ticket = new SimpleCrudTableComponent("#tbl_ticket", {
    ticket: {type: "string"},
    servicio_afectado: {type: "string"},
    fecha_ini: {type: "date"},
    hora_ini: {type: "time"},
    fecha_fin: {type: "date"},
    hora_fin: {type: "time"},
});
document.querySelector("#tbl_ubicacion .btn_add")
.addEventListener("click", function(e){
    let to_add = {
        departamento: document.querySelector("select[name=_departamento]").value,
        provincia: document.querySelector("select[name=_provincia]").value,
        distrito: document.querySelector("select[name=_distrito]").value,
        plano: document.querySelector("textarea[name=_plano]").value,
    };
    tbl_ubicacion.add(to_add);
});
/*document.querySelector("#tbl_ticket .btn_add")
.addEventListener("click", function(e){
    let to_add = {
        ticket: document.querySelector("*[name=_ticket]").value,
        servicio_afectado: document.querySelector("*[name=_servicio_afectado]").value,
        fecha_ini: document.querySelector("*[name=_fecha_ini]").value,
        hora_ini: document.querySelector("*[name=_hora_ini]").value,
        fecha_fin: document.querySelector("*[name=_fecha_fin]").value,
        hora_fin: document.querySelector("*[name=_hora_fin]").value,
    };
    tbl_ticket.add(to_add);
});*/

document.querySelector("#form_export")
.addEventListener("submit", async function(e){
    e.preventDefault();
    utils.downloadHandler({
        url: config.url,
        requestOptions: {headers: {accept: "application/json"}}
    }, e);
    /*const button = document.querySelector(".btn_export");
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

        new Noty({
            type: 'success',
            layout: 'topRight',
            text: "Procesado correctamente"
        }).show();
    } catch(error) {
        alert(error);
    }
    button.innerHTML = old_button_text;
    loader_component.style.display = 'none';*/
});

    document.querySelector("select[name=_departamento]")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("select[name=_provincia]");
        let _new_html = config["provincias"].filter(op => op.departamento === e.target.value
        || op.departamento === "").map(row => `<option value="${row.provincia}" data-dep="${row.departamento}">${row.provincia}</option>`)
        .join("");
        sub_element.innerHTML = `<option value="" data-dep="">Seleccione</option>${_new_html}`;
        sub_element.dispatchEvent(new Event("change"));
    });

    document.querySelector("select[name=_provincia]")
    .addEventListener("change", function(e) {
        let sub_element = document.querySelector("select[name=_distrito]");
        let option;
        document.querySelector("select[name=_provincia]")
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
</script>
@endsection
