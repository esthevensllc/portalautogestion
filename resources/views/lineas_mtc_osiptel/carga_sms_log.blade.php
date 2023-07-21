@extends(backpack_view('blank'))

@section('after_styles')
<link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}">
@include('includes.noty_css')
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <input type="hidden" name="step" value="1" required>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Tipo Plan</label>
                    <select name="tipo_plan" class="form-control form-control-sm reload_ticket" required>
                        @foreach ($config["tipo_plan"] as $row)
                            <option value="{{$row['id']}}">{{$row["label"]}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Tipo Solicitud</label>
                    <select name="tipo_solicitud" class="form-control form-control-sm reload_ticket" required>
                        @foreach ($config["tipo_solicitud"] as $row)
                            <option value="{{$row['id']}}">{{$row["label"]}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Carta</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Archivo</label>
                    <input type="file" class="d-block" name="file" accept=".xls,.xlsx,.txt,.csv" required>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Cargar Archivo</button>
                </div>
                <div class="col-lg-12 form-group">
                    <p class="mb-0">*Mantener el formato ya establecido para evitar errores <a href="{{ asset("resources/CSR_28-03-2023_14-16-23_19153_1680031046151_1_M1.csv") }}">archivo.csv<a></p>
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
<script src="{{asset('packages/select2/dist/js/select2.min.js')}}"></script>
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const data = new FormData(e.target);
        fetch(config.url, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            if(!response.ok){
                let json_response = await response.json();
                throw new Error(json_response.message);
            }
            
            new Noty({
                type: 'success',
                layout: 'topRight',
                text: "Cargado correctamente"
            }).show();
            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error);
        });
    });

    function renderTickets(){
        let tipo_plan = document.querySelector("select[name=tipo_plan]").value;
        let tipo_solicitud = document.querySelector("select[name=tipo_solicitud]").value;
        fetch(config.ticket_url+"/"+tipo_plan+"/"+tipo_solicitud)
        .then(resp => resp.json())
        .then(resp => {
            let _html = resp.map(row => `<option value="${row["fecha"].substr(0,10)}<>${row["ticket"]}">
            ${row["fecha"].substr(0,10)} ${row["ticket"]}
            </option>`).join('');
            document.querySelector("select[name=ticket]").innerHTML = _html;
            $(document.querySelector("select[name=ticket]")).select2({width: '100%'});
        });
    }

    document.querySelectorAll(".reload_ticket")
    .forEach(e => {
        e.addEventListener("change", function(){
            renderTickets();
        });
    });

    renderTickets();

});
</script>
@endsection