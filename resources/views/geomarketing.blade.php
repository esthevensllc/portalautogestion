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
                        <label for="">INGRESE NINTEX</label>
                        <input type="text" class="form-control form-control-sm primary-input" name="nintex" placeholder="Ingrese Nintex" required>
                        <div class="invalid-feedback d-block text-dark">
                            Es obligatorio ingresar el req de nintex debido a que es información sensible
                        </div>
                    </div>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <select class="form-control form-control-sm primary-input" name="base" required>
                            <option value="">Seleccione Base</option>
                            @foreach ($config["bases"] as $row)
                                <option value="{{ $row["id"] }}">{{ $row["label"] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3 row form-section-2" style="display: none;">
                <div class="col-12">
                    <div class="form-group">
                        <div class="d-inline-block pr-3">
                            <input type="radio" class="" id="without_white_list" name="white_list" value="0" required>
                            <label for="without_white_list">Base Completa</label>
                        </div>
                        <div class="d-inline-block pr-3">
                            <input type="radio" class="" id="white_list" name="white_list" value="1" required>
                            <label for="white_list">Base White List</label>
                        </div>
                    </div>
                </div>
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
    
    document.querySelectorAll(".primary-input")
    .forEach(inputEl => {
        inputEl.addEventListener("change", function(e){
            let passes = true;
            document.querySelectorAll(".primary-input")
            .forEach(input => {
                if(input.value === ''){
                    passes = false;
                }
            });
            document.querySelector(".form-section-2").style.display = passes ? '': 'none';
        });
    });

    function modifyDate(strDateTime, minute){
        let timestamp = (new Date(strDateTime)).getTime() + 1000*60*minute;
        let _date = new Date(timestamp);
        return _date;
    }

    document.querySelector("input[name=fecha_inicio]")
    .addEventListener("change", function(e){
        let fechaInicio = document.querySelector("input[name=fecha_inicio]");
        let fechaFin = document.querySelector("input[name=fecha_fin]");
        
        let maxDate = modifyDate(fechaInicio.value + "T00:00:00", (3*24*60) - 1);
        fechaFin.min = fechaInicio.value;
        fechaFin.max = maxDate.toLocaleDateString("sv-SE");
        // console.log('maxDate', maxDate);
    });
    document.querySelector("input[name=fecha_fin]")
    .addEventListener("change", function(e){
        let fechaInicio = document.querySelector("input[name=fecha_inicio]");
        let fechaFin = document.querySelector("input[name=fecha_fin]");
        
        let minDate = modifyDate(fechaFin.value + "T00:00:00", -(3*24*60) + 1);
        fechaInicio.max = fechaFin.value;
        fechaInicio.min = minDate.toLocaleDateString("sv-SE");
        // console.log('minDate', minDate);
    });

    let now = (new Date()).toLocaleDateString("sv-SE");

    document.querySelector("input[name=fecha_inicio]").value = now;
    document.querySelector("input[name=fecha_fin]").value = now;
    document.querySelector("input[name=fecha_inicio]").dispatchEvent(new Event("change"));
    document.querySelector("input[name=fecha_fin]").dispatchEvent(new Event("change"));

});
</script>
@endsection
