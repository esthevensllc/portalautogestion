@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}">
@include('includes.noty_css')
@endsection

@section('content')
    <div class="d-flex mb-2">
        <h4 class="mb-0 mr-3">{{ $config['title'] }}</h4>
        <a href="{{ asset('admin/usuarios') }}" class="btn btn-sm btn-primary"><li class="la la-list"></li> Listado</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form id="form_usuario">
                @csrf
                <input type="hidden" name="id" value="{{ $user->id ?? '' }}">
                <div class="row">
                    <div class="col-12 form-group" data-name="username">
                        <label for="">Usuario</label>
                        <input type="text" class="form-control form-control-sm" name="username" value="{{ $user->username ?? '' }}">
                        <span class="invalid-feedback" data-name="username"></span>
                    </div>
                    <div class="col-12 form-group" data-name="name">
                        <label for="">Nombres</label>
                        <input type="text" class="form-control form-control-sm" name="name" value="{{ $user->name ?? '' }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="last_name">
                        <label for="">Apellidos</label>
                        <input type="text" class="form-control form-control-sm" name="last_name" value="{{ $user->last_name ?? '' }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="direccion">
                        <label for="">Dirección</label>
                        <input type="text" class="form-control form-control-sm" name="direccion" value="{{ $user->direccion ?? '' }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="area">
                        <label for="">Area</label>
                        <input type="text" class="form-control form-control-sm" name="area" value="{{ $user->area ?? '' }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="roles_id">
                        <label for="">Roles</label>
                        <select
                            class="form-control form-control-sm"
                            multiple="multiple"
                            name="roles_id[]"
                            data-value="{{ json_encode($user !== null ? $user->roles_id : []) }}"
                            >
                            @foreach ($roles as $row)
                                @if ((int) $row->status === 1 || in_array($row->id, $user->roles_id??[]))
                                    <option value="{{ $row->id }}">{{ $row->name }} @if ((int) $row->status !== 1) (Desactivado) @endif</option>
                                @endif
                            @endforeach
                        </select>
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="col-12 form-group" data-name="status">
                        <label for="">Status</label>
                        <input type="text" class="form-control form-control-sm" value="{{ (int) ($user->status ?? 0) === 1 ? 'Activo' : 'Desactivado' }}" disabled>
                    </div>
                    <div class="col-12 form-group">
                        <button class="btn btn-primary btn-sm">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.noty_js')
<script src="{{asset('packages/select2/dist/js/select2.min.js')}}"></script>
<script>
    let _select = $("select").val(JSON.parse($("select").attr('data-value')));
    $("select").select2();

    let store = {
        mode: '{{ $config["mode"] }}',
        config_by_mode: {
            'create': {
                url: `{{ asset('admin/usuarios/create') }}`,
                method: 'POST',
                success_message: 'Se registro correctamente',
                error_message: 'Ocurrió un error al registrar',
            },
            'update': {
                url: `{{ asset('admin/usuarios/edit') }}/{id}`,
                method: 'POST',
                success_message: 'Se actualizo correctamente',
                error_message: 'Ocurrió un error al actualizar'
            },
        }
    }
    store.config = store.config_by_mode[store.mode];

    if(store.mode === 'create'){
        document.querySelector(`.form-group[data-name=status]`).style.display = 'none';
    }


    const onSubmitHandler = (e) => {
        e.preventDefault();
        const data = new FormData(e.target);
        //const string_params = new URLSearchParams(data).toString();
        //console.log(string_params);
        fetch(store.config.url.replace('{id}', data.get('id')), {method: store.config.method, body: data})
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            console.log(response);
            document.querySelectorAll('.form-control').forEach(el => {
                el.classList.remove('is-invalid');
            });
            if(response.passes){
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.innerHTML = '';
                });
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: store.config.success_message
                }).show();

                if(store.mode === 'create'){
                    document.querySelectorAll('.form-control').forEach(el => {
                        el.value = '';
                    });
                }
            }else{
                Object.keys(response.errors).forEach(name => {
                    document.querySelector(`.form-group[data-name=${name}] .form-control`).classList.add('is-invalid');
                    document.querySelector(`.form-group[data-name=${name}] .invalid-feedback`).innerHTML = response.errors[name];
                });
            }
        })
        .catch(error => {
            alert(error);
        });
    }

    document.querySelector('#form_usuario').addEventListener('submit', onSubmitHandler, false);;
</script>
@endsection