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
            <th>Id</th>
            <th>Username</th>
            <th>Filename</th>
            <th>Fecha Creación</th>
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
            {data: 'id'},
            {data: 'codigo_c'},
            {render: function(data, type, row){
                let url = config.downloadApi.replace("[file]", row['filename']);
                let html = `<a href="${url}" target="_blank">${row['filename']}</a>`;
                return html;
            }},
            {data: 'ini'},
        ],
        "fnDrawCallback": function() {
            $(".btn-download").off("click");
            $(".btn-download").on("click", function(e){
                let id = $(this).attr("data-id");
                window.open(config.downloadApi.replace("[file]", filename), '_blank');
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