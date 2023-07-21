@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <input type="hidden" name="step" value="1" required>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Carta</label>
                    <input type="text" name="ticket" class="form-control form-control-sm" maxlength="20" required>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Tipo reporte</label>
                    <select name="tipo_reporte" class="form-control form-control-sm" required>
                        @foreach ($config["tipo_reporte"] as $row)
                            <option value="{{$row['id']}}">{{$row["label"]}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        document.querySelector("input[name=step]").value = "1";
        utils.downloadHandler({
            url: config.url,
            requestOptions: {headers: {accept: "application/json"}},
            errorCallback: async (error) => {
                if(error.message.includes('Ya se inserto las lineas')){
                    document.querySelector("input[name=step]").value = "2";
                    await utils.downloadHandler({
                        url: config.url,
                        requestOptions: {headers: {accept: "application/json"}}
                    }, e);
                }
            }
        }, e);
    });

    document.querySelector("*[name=ticket]")
    .addEventListener("change", function(e){
        e.target.value = e.target.value.replaceAll(" ", "_");
    });

});
</script>
@endsection