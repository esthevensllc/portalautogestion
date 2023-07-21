@php
function _render_modules($modulos, $level = 0){
    $html = "";
    //$sub_modulos = $module->modulos ?? [];
    $next_level = $level + 1;
    foreach($modulos as $row){
        $sub_modulos = $row->modulos ?? [];
        $submodulos_class = '';
        $has_submodule = "";
        $sub_html = "";
        $input_name = "name='modules_id[]' value='{$row->id_tracing}'";
        if(count($sub_modulos) > 0){
            $submodulos_class = 'font-weight-bold';
            $has_submodule = "not-tracing";
            $input_name = "";
            $sub_html = _render_modules($sub_modulos, $next_level);
        }
        if($level === 0){
            $html .= "<div class='col-lg-4 col-md-6 tracing-{$row->id_tracing}'>
                <ul class='list-group mb-3'>
                    <li class='list-group-item p-2 list-group-item-secondary'>
                        <input
                            type='checkbox'
                            id='permission-{$row->id_tracing}'
                            data-id_tracing='{$row->id_tracing}'
                            data-father_id='{$row->father_id}'
                            class='{$has_submodule}'
                            {$input_name}
                        >
                        <label
                            for='permission-{$row->id_tracing}'
                            class='m-0 {$submodulos_class}'>
                            {$row->label}
                        </label>
                    </li>
                    <li class='list-group-item p-2'>
                        {$sub_html}
                    </li>
                </ul>
            </div>";
        }else{
            $margin_left = $level === 1 ? 'ml-0' : 'ml-3';
            $html .= "<div class='{$margin_left} tracing-{$row->id_tracing}'>
                <input
                    type='checkbox'
                    id='permission-{$row->id_tracing}'
                    data-id_tracing='{$row->id_tracing}'
                    data-father_id='{$row->father_id}'
                    class='{$has_submodule}'
                    {$input_name}
                >
                <label
                    for='permission-{$row->id_tracing}'
                    class='m-0 {$submodulos_class}'>
                    {$row->label}
                </label>
                {$sub_html}
            </div>";
        }

    }
    if($level === 0){
        return "<div class='main-{$level} row'>{$html}</div>";
    }
    return "<div class='main-{$level}'>{$html}</div>";
}

$html_modules = _render_modules($modules, 0);

@endphp

@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}">
@endsection

@section('content')
    <div class="d-flex mb-2">
        <h4 class="mb-0 mr-3">{{ $config['title'] }}</h4>
        <a href="{{ asset('admin/roles') }}" class="btn btn-sm btn-primary"><li class="la la-list"></li> Listado</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form id="form_rol">
                @csrf
                <input type="hidden" name="id" value="{{ $rol->id ?? '' }}">
                <div class="row">
                    <div class="col-12 form-group" data-name="name">
                        <label for="">Rol</label>
                        <input type="text" class="form-control form-control-sm" name="name" value="{{ $rol->name ?? '' }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="status">
                        <label for="">Status</label>
                        <input type="text" class="form-control form-control-sm" value="{{ (int) ($rol->status ?? 0) === 1 ? 'Activo' : 'Desactivado' }}" disabled>
                    </div>
                    <div class="col-12 form-group" data-name='modules_id'>
                        <label for="">Permisos</label>
                        {!! $html_modules !!}
                        <span class="invalid-feedback d-block"></span>
                    </div>
                    <div class="col-12 form-group">
                        <button class="btn btn-primary btn-sm">Guardar</button>
                    </div>
                </div>
        </div>
    </div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
<script src="{{asset('packages/select2/dist/js/select2.min.js')}}"></script>
<script>
    const store = {
        modules: @json($modules),
        rol: @json($rol),
        config: @json($config)
    }

    let onCheckHandler = (e) => {
        let id_tracing = e.target.getAttribute('data-id_tracing');
        let father_id = e.target.getAttribute('data-father_id');
        let checked = e.target.checked;
        const list = document.querySelectorAll(`.tracing-${id_tracing} input[type=checkbox]`);
            
        console.log(`.tracing-${id_tracing} input[type=checkbox]`);
        list.forEach(checkbox => {
            checkbox.checked = checked;
        });

        let checkboxs = document.querySelectorAll(`.tracing-${father_id} input[type=checkbox]`);
        //console.log(checkboxs);
        if(checkboxs){
            let all_is_checked = true;
            checkboxs.forEach(checkbox => {
                if(!checkbox.checked && !checkbox.classList.contains('not-tracing')){
                    all_is_checked = false;
                }
            });
            let checkbox = document.querySelector(`#permission-${father_id}[type=checkbox]`).checked = all_is_checked;
            console.log(all_is_checked);
        }
    }

    if(store.rol){
        store.rol.modules_id.forEach(id_tracing => {
            let checkbox = document.querySelector(`#permission-${id_tracing}[type=checkbox]`);
            if(checkbox){
                checkbox.checked = true;
            }
        });
    }else{
        document.querySelector(`.form-group[data-name=status]`).style.display = 'none';
    }

    document.querySelector('#form_rol').addEventListener('change', function(e){
        if(e.target.tagName === 'INPUT' && e.target.getAttribute('type') === 'checkbox'){
            onCheckHandler(e);
        }
    }, false);
    document.querySelector('#form_rol').addEventListener('submit', async function(e){
        e.preventDefault();
        document.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.innerHTML = '';
        });
        try {
            const data = new FormData(e.target);
            let url = store.config.url.replace('{id}', data.get('id'));
            const _response = await fetch(url, {method: 'POST', body: data});
            const response = await _response.json();
            if(response.passes){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: store.config.success_message
                }).show();
            }else{
                Object.keys(response.errors).forEach(name => {
                    const input = document.querySelector(`.form-group[data-name=${name}] .form-control`);
                    if(input){
                        input.classList.add('is-invalid');
                    }
                    document.querySelector(`.form-group[data-name=${name}] .invalid-feedback`).innerHTML = response.errors[name];
                });
            }

        } catch (error) {
            new Noty({
                type: 'error',
                layout: 'topRight',
                text: store.config.error_message
            }).show();
        }
    }, false);
</script>
@endsection