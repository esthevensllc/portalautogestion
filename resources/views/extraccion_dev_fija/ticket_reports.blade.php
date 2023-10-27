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
            <th>Ticket</th>
            <th>Departamento</th>
            <th>Fecha</th>
            <th>Servicio Afectado</th>
            <th>Abonados Afectados</th>
            <th>Acreditados</th>
            <th>No Acreditados</th>
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
            {data: 'ticket'},
            {data: 'departamento'},
            {data: 'fecha'},
            {data: 'servicio_afectado'},
            {data: 'abonados_afectados'},
            {data: 'acreditados'},
            {data: 'no_acreditados'}
        ],
        "fnDrawCallback": function() {
            // $(".btn-delete").on("click", deleteFilenameHandler);
        },
        lengthChange: false,
        searching: true,
        order: [[2, 'desc']],
        scrollX: true
        //serverSide: true
    });
});
</script>
@endsection