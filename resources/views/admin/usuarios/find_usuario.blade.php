@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}">
@endsection

@section('content')
    <div class="d-flex mb-2">
        <h4 class="mb-0 mr-3">Detalle de usuario</h4>
        <a href="{{asset('admin/usuarios/edit')}}/{{ $user->id ?? '' }}" class="btn btn-sm btn-warning mr-2"><li class="la la-pencil"></li> Editar</a>
        <a href="{{ asset('admin/usuarios') }}" class="btn btn-sm btn-primary"><li class="la la-list"></li> Listado</a>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-12 form-group">
                    <label for="">Usuario</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->username ?? '' }}" disabled>
                </div>
                <div class="col-12 form-group">
                    <label for="">Nombres</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->name ?? '' }}" disabled>
                </div>
                <div class="col-12 form-group">
                    <label for="">Apellidos</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->last_name ?? '' }}" disabled>
                </div>
                <div class="col-12 form-group">
                    <label for="">Dirección</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->direccion ?? '' }}" disabled>
                </div>
                <div class="col-12 form-group">
                    <label for="">Area</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $user->area ?? '' }}" disabled>
                </div>
                <div class="col-12 form-group">
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
                </div>
                <div class="col-12 form-group">
                    <label for="">Status</label>
                    <input type="text" class="form-control form-control-sm" value="{{ (int)($user->status ?? 0) === 1 ? 'Activo' : 'Desactivado' }}" disabled>
                </div>
            </div>
        </div>
    </div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
<script src="{{asset('packages/select2/dist/js/select2.min.js')}}"></script>
<script>
    let _select = $("select").val(JSON.parse($("select").attr('data-value')));
    $("select").select2();
</script>
@endsection