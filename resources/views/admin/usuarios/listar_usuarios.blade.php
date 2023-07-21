@extends(backpack_view('blank'))

@section('after_styles')
  {{-- DATA TABLES --}}
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    tbody tr td, thead, tr th{
        padding: 0.4rem !important;
    }
</style>
@endsection

@section('content')
<div class="d-flex mb-2">
    <h4 class="mb-0 mr-3">Usuarios</h4>
    <a href="{{ asset('admin/usuarios/create') }}" class="btn btn-sm btn-primary"><li class="la la-plus"></li> Registrar</a>
</div>
<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            <th>Id</th>
            <th>Usuario</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Dirección</th>
            <th>Area</th>
            <th>Roles</th>
            <th>Estado</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
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
    let store = {};
    const _datatable = $(".table").DataTable({
        ajax: {
            url: "{{ asset('admin/usuarios/search') }}",
            type: "POST",
            dataSrc: function(resp){
                //console.log(resp);
                store.relationships = resp.relationships;
                return resp.data;
            }
        },
        columns: [
            {data: 'id'},
            {data: 'username'},
            {data: 'name'},
            {data: 'last_name'},
            {data: 'direccion'},
            {data: 'area'},
            {render: function(data, type, row){
                return row.rols.map(rol_id => store.relationships.rols[rol_id].name).join(', ');
            }},
            {render: function(data, type, row){
                return parseInt(row['status']) === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Desactivado</span>';
            }},
            {render: function(data, type, row){
                return `<a href="{{asset('admin/usuarios')}}/${row['id']}" class="btn btn-sm btn-info pt-0 pb-0 mr-1"><li class="la la-eye"></li> Ver</a>
                <a href="{{asset('admin/usuarios/edit')}}/${row['id']}" class="btn btn-sm btn-warning pt-0 pb-0 mr-1"><li class="la la-pencil"></li> Editar</a>
                <button
                    class="btn btn-sm btn-secondary pt-0 pb-0"
                    data-id="${row['id']}"
                    data-value="${row['status']}">${(parseInt(row['status']) === 1 ? 'Desactivar':'Activar')}</button>`;
            }},
        ],
        //serverSide: true
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
            
            fetch(`{{ asset('admin/usuarios') }}/${id}/status/${status === 1 ? 0 : 1}`, {
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
</script>
@endsection