@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-fixedheader-bs4/css/fixedHeader.bootstrap4.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('packages/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}">
<style>
    table.dataTable td, table.dataTable th{
        padding: 0.4rem;
    }
</style>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Archivo</label>
                        <input type="file" name="comprobantes_file" accept=".xlsx" required>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {"Accept": "application/json"}}
        }, e);
    });
});
</script>
@endsection