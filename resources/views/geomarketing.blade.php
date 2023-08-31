@extends(backpack_view('blank'))

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <select name="" class="form-control form-control-sm" required>
                            <option value="">Seleccione Base</option>
                            @foreach ($config["bases"] as $row)
                                <option value="{{ $row["id"] }}">{{ $row["label"] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Inicio</label>
                        <input type="date" class="form-control form-control-sm" name="fecha_inicio" required>
                        <input type="time" class="form-control form-control-sm" name="hora_inicio" placeholder="00:00:00" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Fin</label>
                        <input type="date" class="form-control form-control-sm" name="fecha_fin" required>
                        <input type="time" class="form-control form-control-sm" name="hora_fin" placeholder="00:00:00" required>
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
