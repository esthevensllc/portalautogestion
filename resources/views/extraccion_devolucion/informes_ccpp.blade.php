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
<div class="card">
    <div class="card-body">
        <form id="import_form">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Archivo</label>
                    <input type="file" name="archivo" accept=".xlsx">
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Cargar</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
            {data: 'username'},
            {render: function(data, type, row){
                let url = config.downloadApi.replace("[id]", row['id']);
                let html = `<a href="${url}" target="_blank">${row['filename']}</a>`;
                return html;
            }},
            {data: 'created_at'},
        ],
        "fnDrawCallback": function() {
            $(".btn-download").off("click");
            $(".btn-download").on("click", function(e){
                let id = $(this).attr("data-id");
                window.open(config.downloadApi.replace("[id]", id), '_blank');
            });
        },
        lengthChange: false,
        searching: true,
        order: [[0, 'desc']],
        scrollX: true
        //serverSide: true
    });

    $('#import_form').on("submit", function(e){
        e.preventDefault();
        const data = new FormData(e.target);
        
        utils.fetch(config.importApi, {
            headers: {"Accept": "application/json"},
            method: 'POST',
            body: data
        })
        .then(async(response) => {
            let isOK = response.ok;
            let json = await response.json();
            if(!isOK){
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: json.message
                }).show();
            }else{
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: 'Se cargo correctamente'
                }).show();
            }
        })
        .catch(error => {
            new Noty({
                type: 'error',
                layout: 'topRight',
                text: "Ocurrió un error al cargar el archivo"
            }).show();
        });
    });
});
</script>
@endsection