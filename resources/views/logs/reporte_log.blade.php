@extends(backpack_view('blank'))

@section('after_styles')
  {{-- DATA TABLES --}}
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    /*tbody tr td, thead, tr th{
        padding: 0.4rem;
    }*/
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
</style>
@endsection

@section('content')
<div class="d-flex mb-2">
    <h4 class="mb-0 mr-3">Logs reportes</h4>
</div>
<div id="main_filters" class="mb-2">
    
</div>
<div class="">
<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            <th>Id</th>
            <th>hostname</th>
            <th>name</th>
            <th>dirección</th>
            <th>area</th>
            <th>contacto</th>
            <th>codigo_c</th>
            <th>reponsable</th>
            <th>filename</th>
            <th>ini</th>
            <th>fin</th>
            <th>estado</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
@include('includes.spinner_loader')
@csrf
@endsection

@section('after_scripts')
{{-- DATA TABLES SCRIPT --}}
<script type="text/javascript" src="{{ asset('packages/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('packages/datatables.net-fixedheader-bs4/js/fixedHeader.bootstrap4.min.js') }}"></script>
<script>
    class FiltersComponent
    {
        constructor(el_selector, config = {}){
            this._el_selector = el_selector;
            this.uuid = this.__generate_uuid();
            const id = this.__generate_uuid();
            this._filters = [];
            this._config = config;
            this._handlers = [];

            const html_fields = Object.keys(config.fields??{})
            .map(i => `<option value="${i}" data-type="${config.fields[i].type}">${config.fields[i].label}</option>`)
            .join('');

            const html_operators = Object.keys(config.operators??{})
            .map(i => `<option value="${i}" data-types="${config.operators[i].types}">${config.operators[i].label}</option>`)
            .join('');

            this.template_agregator = `<div class="btn-group" role="group">
                <select class="form-control form-control-sm" name="field" style="max-width: 90px;">
                    ${html_fields}
                </select>
                <select class="form-control form-control-sm" name="operator" style="max-width: 90px;">
                    ${html_operators}
                </select>
                <input type="text" class="form-control form-control-sm" name="value">
                <button class="btn btn-primary btn-sm btn_add_filter" style="min-width: 90px;">
                    <i class="la la-plus"></i> Agregar
                </button>
            </div>`;
            this.template_agregator = `<div class="d-flex" role="group">
                <select class="form-control form-control-sm" name="field" style="max-width: 90px;">
                    ${html_fields}
                </select>
                <select class="form-control form-control-sm" name="operator" style="max-width: 90px;">
                    ${html_operators}
                </select>
                <input type="text" class="form-control form-control-sm" name="value">
                <button class="btn btn-primary btn-sm pt-0 pb-0 btn_add_filter" style="min-width: 90px;">
                    <i class="la la-plus"></i> Agregar
                </button>
            </div>`;
            $(el_selector).html(`
            <div class="row" id="${id}" data-uuid="${this.uuid}">
                <div class="col-12 mb-2">
                    <form id="form_${this.uuid}" autocomplete="off">
                        <div class="row">
                            <div class="col-lg-4">
                                ${this.template_agregator}
                            </div>
                            <div class="col-lg-3">
                                
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-12">
                    <div class="row filters_added">
                    </div>
                </div>
            </div>`);

            let form_id = `form_${this.uuid}`;
            $(`#${form_id}`).on('submit', this.handle_add_filter.bind(this));
            $(`#${form_id} *[name=field]`).on('change', function(e){
                const type = $(`#${form_id} *[name=field] option[value=${e.target.value}]`).attr('data-type');
                let operators = $(`#${form_id} *[name=operator] option`);
                console.log(operators);
                for(let i=0; i < operators.length; i++){
                    let operator = $(operators[i]);
                    console.log('data-types', operator.attr('data-types'));
                    console.log('type', type);
                    const is_ok = operator.attr('data-types').split(",").some(t => t === type);
                    if(is_ok){
                        operator.show();
                    }else{
                        operator.hide();
                    }
                }
            });
        }

        __generate_uuid() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
                return v.toString(16);
            });
        }

        addEventListener(event, handler){
            this._handlers.push({event, handler});
        }

        handle_add_filter(e){
            e.preventDefault();
            const field = $(`#form_${this.uuid} *[name=field]`).val();
            const operator = $(`#form_${this.uuid} *[name=operator]`).val();
            const value = $(`#form_${this.uuid} *[name=value]`).val();
            this._filters.push({'id': this.__generate_uuid(), 'field': field, 'operator': operator, 'value': value});
            // console.log(`${field}.${operator}.${value}`);
            this._handlers.filter(h => h.event === 'on_add_filter')
            .forEach(h => {
                h.handler();
            });
            this.render();
        }

        handle_delete_filter(id){
            console.log(id);
            this._filters = this._filters.filter(row => row.id !== id);
            this.render();

            this._handlers.filter(h => h.event === 'on_del_filter')
            .forEach(h => {
                h.handler();
            });
        }

        handle_change_filter(e, id){
            const name = e.target.name;
            const value = e.target.value;
            console.log({[name]: value});
            this._filters = this._filters.map(row => row.id == id ? {...row, [name]: value} : row);
            this.render();

            this._handlers.filter(h => h.event === 'on_change_filter')
            .forEach(h => {
                h.handler();
            });
        }

        get_formated_filters(){
            return this._filters.map(row => `${row.field}.${row.operator}.${row.value}`);
        }

        render(){
            const added_html = this._filters.map(row => {
                const html_operators = Object.keys(this._config.operators??{})
                .filter(i => {
                    //console.log(this._config.operators[i].types, row.type);
                    return this._config.operators[i].types.some(type => type === this._config.fields[row.field].type)
                })
                .map(i => `<option value="${i}">${this._config.operators[i].label}</option>`)
                .join('');

                const is_date = ['date', 'datetime'].some(type => type === this._config.fields[row.field].type);

                return `<div class="col-lg-4 col-md-6" data-added_id="${row.id}">
                    <div class="btn-group w-100" role="group">
                        <input type="text" class="form-control form-control-sm" name="field" disabled>
                        <select class="form-control form-control-sm" name="operator" style="max-width: 90px;">
                            ${html_operators}
                        </select>
                        <input type="${is_date?'date':'text'}" class="form-control form-control-sm" name="value">
                        <button class="btn btn-secondary btn-sm d-inline-block pt-0 pb-0 btn_del_filter" data-added_id="${row.id}">
                            <i class="la la-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            $(this._el_selector+' .filters_added').html(added_html);
            this._filters.forEach(row => {
                $(`${this._el_selector} .filters_added div[data-added_id=${row.id}] *[name=field]`).val(row.field);
                $(`${this._el_selector} .filters_added div[data-added_id=${row.id}] *[name=operator]`).val(row.operator);
                $(`${this._el_selector} .filters_added div[data-added_id=${row.id}] *[name=value]`).val(row.value);
            });
            
            const _this = this;
            $(`.filters_added .form-control`).on('change', function(e){
                const id = $(this).parent().parent().attr('data-added_id');
                _this.handle_change_filter(e, id);
            });
            $(`.btn_del_filter`).on('click', function(){
                const id = $(this).attr('data-added_id');
                _this.handle_delete_filter(id);
            });
        }
    }

    let store = {};
    const _datatable = $(".table").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: "{{ asset('logs/reporte_log/search') }}",
            type: "POST",
            dataSrc: function(resp){
                //store.relationships = resp.relationships;
                return resp.data;
            }
        },
        columns: [
            {data: 'id'},
            {data: 'hostname'},
            {data: 'name'},
            {data: 'direccion'},
            {data: 'area'},
            {data: 'contacto'},
            {data: 'codigo_c'},
            {data: 'responsable'},
            {data: 'filename'},
            {data: 'ini'},
            {data: 'fin'},
            {render: function(data, type, row){
                return parseInt(row['estado']) === 1 ? '<span class="badge badge-success">Correcto</span>' : '<span class="badge badge-secondary">Error</span>';
            }},
            /*{render: function(data, type, row){
                let html =  row['modules_id'].map(id_tracing => `${store.relationships.modules_by_id[id_tracing]['label']}`).join(', ');
                return `<div class="container" style="max-width: 200px; min-height: 200px;">${html}</div>`;
            }},*/
            {render: function(data, type, row){
                return ``;
            }},
        ],
        serverSide: true,
        scrollX: true
    });
    document.querySelector('table tbody').addEventListener('click', function(e){
        if(e.target.tagName === 'BUTTON'){
            console.log(e.target);
            const id = e.target.getAttribute('data-id');
            const status = parseInt(e.target.getAttribute('data-value'));
            
            //const _token = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            const _token = document.querySelector('input[name=_token]').value;
            const data = new FormData();
            data.append('_token', _token);
            
            fetch(`{{ asset('admin/roles') }}/${id}/status/${status === 1 ? 0 : 1}`, {
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
                    text: 'Se cambio el estado correctamente'
                }).show();
                _datatable.ajax.reload();
            })
            .catch(error => {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Ocurrió un error al cambiar el estado'
                }).show();
            });
        }
    }, false);

    // filters config
    const fields = {
        'id': {label: 'Id', type: 'number'},
        'hostname': {label: 'Hostname', type: 'string'},
        'name': {label: 'name', type: 'string'},
        'direccion': {label: 'direccion', type: 'string'},
        'area': {label: 'area', type: 'string'},
        'contacto': {label: 'contacto', type: 'string'},
        'codigo_c': {label: 'codigo_c', type: 'string'},
        'responsable': {label: 'responsable', type: 'string'},
        'filename': {label: 'filename', type: 'string'},
        'ini': {label: 'Fecha ini', type: 'datetime'},
        'fin': {label: 'Fecha fin', type: 'datetime'},
        'estado': {label: 'estado', type: 'string'},
    }
    const operators = {
        'eq': {label: 'Igual', types: ['string', 'number', 'date', 'datetime']},
        'ne': {label: 'Diferente', types: ['string', 'number', 'date', 'datetime']},
        'is_null': {label: 'Esta vacio', types: ['string', 'number', 'date', 'datetime']},
        'not_null': {label: 'No es vacio', types: ['string', 'number', 'date', 'datetime']},
        'cn': {label: 'Contiene', types: ['string', 'number']},
        'in': {label: 'In', types: ['string']},
        'lt': {label: 'Menor que', types: ['number', 'date', 'datetime']},
        'gt': {label: 'Mayor que', types: ['number', 'date', 'datetime']},
    }
    let component = new FiltersComponent(`#main_filters`, {
        fields,
        operators,
    });
    component.addEventListener('on_add_filter', function(){
        console.log('on_add_filter', component.get_formated_filters());
        let str_filter = component.get_formated_filters().map(f => `filter[]=${f}`).join('&');
        if(str_filter !== ""){
            str_filter = "?"+str_filter;
        }
        _datatable.ajax.url("{{ asset('logs/reporte_log/search') }}"+str_filter).load();
    });
    component.addEventListener('on_del_filter', function(){
        console.log('on_del_filter', component._filters);
        let str_filter = component.get_formated_filters().map(f => `filter[]=${f}`).join('&');
        if(str_filter !== ""){
            str_filter = "?"+str_filter;
        }
        _datatable.ajax.url("{{ asset('logs/reporte_log/search') }}"+str_filter).load();
    });
    component.addEventListener('on_change_filter', function(){
        console.log('on_change_filter', component._filters);
        let str_filter = component.get_formated_filters().map(f => `filter[]=${f}`).join('&');
        if(str_filter !== ""){
            str_filter = "?"+str_filter;
        }
        _datatable.ajax.url("{{ asset('logs/reporte_log/search') }}"+str_filter).load();
    });
</script>
@endsection