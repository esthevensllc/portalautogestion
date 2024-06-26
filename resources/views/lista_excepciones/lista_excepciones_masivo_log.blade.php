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
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Tipo Operación</th>
            <th>Documento</th>
            <th>Peso</th>
            {{-- <th>EIR</th> --}}
            <th>Cantidad Registros</th>
            <th>Imeis Unicos</th>
            <th>Ejecuciones Exitosas</th>
            <th>Ejecuciones Fallidas</th>
            <th>Log Response</th>
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
    const tiposOperacionById = {};
    const tiposDocumentoById = {};

    config.tiposOperacion.forEach(r => {
        tiposOperacionById[r.id] = r;
    });

    let _datatable = $(".tbl-documentlog").DataTable({
        language: {url: "{{ url('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.url,
            type: "GET",
        },
        columns: [
            {data: 'username'},
            {data: 'fecha'},
            {render: function(data, type, row){
                return tiposOperacionById[row['tipo_operacion_id']].label;
            }},
            {render: function(data, type, row){
                return row['filename'];
            }},
            {render: function(data, type, row){
                let kb = (row['size_bytes']/1024).toFixed(1);
                let html = `<span>${kb}K</span>`;
                return html;
            }},
            {data: 'cant_registros'},
            {data: 'cant_unicos'},
            {data: 'exec_ok'},
            {data: 'exec_fail'},
            {render: function(data, type, row){
                let url = config.downloadEir.replace('[id]', row['eir_id']);
                let html = `<a href="${url}" target="_blank">Descargar</a>`;
                return html;
            }},
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