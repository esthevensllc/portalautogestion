@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>

<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-documentlog" cellspacing="0"
>
    <thead class="bg-danger">
        <tr>
            <th>Fecha Creación</th>
            <th>Username</th>
            <th>Nintex</th>
            <th>Base</th>
            <th>Fecha Inicio</th>
            <th>Fecha Fin</th>
            <th>Filename Exported</th>
            <th>Tipo Base</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.datatables_js')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);
    let baseById = {};

    config.bases.forEach(r => {
        baseById[parseInt(r.id)] = r;
    });
    console.log(baseById);

    let _datatable = $(".tbl-documentlog").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
            type: "GET",
        },
        columns: [
            {data: 'created_at'},
            {data: 'codigo_c'},
            {data: 'nintex'},
            //{data: 'base'},
            {render: function(data, type, row){
                let base = baseById[parseInt(row['base'])];
                if(base){
                    return base.label;
                }
                return '';
            }},
            {data: 'fecha_inicio'},
            {data: 'fecha_fin'},
            {data: 'filename_exported'},
            // {data: 'whitelist_flag'},
            {render: function(data, type, row){
                if(parseInt(row['whitelist_flag']) === 1){
                    return 'Base White List';
                }
                return 'Base Completa';
            }},
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: true,
        order: [[0, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection