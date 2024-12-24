@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
    .dataTables_scrollBody{
        position: unset !important;
    }
</style>
@endsection

@section('header')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>

<table
class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-documentlog" cellspacing="0">
    <thead class="bg-danger">
        <tr>
            <th>Usuario</th>
            <th>Archivo</th>
            <th>Fecha Creación</th>
            <th>Estado</th>
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

    let _datatable = $(".tbl-documentlog").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.getApi,
            type: "GET",
        },
        columns: [
            {data: 'usuario'},
            {render: function(data, type, row){
                if(row['archivo'] == 'null' || row['archivo'] == undefined){
                    return '';
                }
                let url = config.downloadApi.replace("[file]", row['archivo']);
                let html = `<a href="${url}" target="_blank">${row['archivo']}</a>`;
                return html;
            }},
            {data: 'fecha_creacion'},
            {render: function(data, type, row){
                if(row['estado'] == '0'){
                    html_estado = 'En Cola';
                }
                if(row['estado'] == '1'){
                    html_estado = 'Procesando...';
                }
                if(row['estado'] == '2'){
                    html_estado = 'Terminado';
                }
                return html_estado;
            }}
        ],
        "fnDrawCallback": function() {
            $(".btn-download").off("click");
            $(".btn-download").on("click", function(e){
                window.open(config.downloadApi.replace("[file]", archivo), '_blank');
            });
        },
        lengthChange: false,
        searching: true,
        order: [[3, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection