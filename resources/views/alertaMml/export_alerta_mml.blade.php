@extends(backpack_view('blank'))

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
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
                <div class="col-lg-3">
                </div>
                <div class="col-lg-6">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-secondary">
                            <tr>
                                <th>Fecha Actualización</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($config["whiteBlackList"] as $row)
                                <tr>
                                    <td>{{ $row["fecha_actualizacion"] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mb-3 row form-section-2" style="display: none;">
                <div class="col-12">
                    <div class="form-group">
                        <label for="mensaje">Mensaje</label>
                        <textarea id="mensaje" class="form-control" rows="4" placeholder="Escribe aquí..."></textarea>
                    </div>                      
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Enviar</button>
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
    let now = (new Date()).toLocaleDateString("sv-SE");
    let strMinDate = modifyDate(now+"T00:00:00", - (config.maxDays*24*60) + 1).toLocaleDateString("sv-SE");
    let strMaxDate = now;

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

});
</script>
@endsection
