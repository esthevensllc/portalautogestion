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
            <th>Fecha</th>
            <th>Tipo Documento</th>
            <th>Documento</th>
            <th>Peso</th>
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
            url: config.url,
            type: "GET",
        },
        columns: [
            {data: 'fecha'},
            {render: function(data, type, row){
                let labelsByTipo = {"1": "SIBMED", "2": "DAPU"}
                return labelsByTipo[row['tipo_documento_id']];
            }},
            {render: function(data, type, row){
                if(parseInt(row['tipo_documento_id']) === 2){
                    let url = config.downloadUrl.replace('[id]', row['id']);
                    let html = `<a href="${url}" target="_blank">${row['filename']}</a>`;
                    return html;
                }
                return row['filename'];
            }},
            {render: function(data, type, row){
                let kb = (row['size_bytes']/1024).toFixed(1);
                let html = `<span>${kb}K</span>`;
                return html;
            }}
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: false,
        order: [[0, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection