@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_process">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">N° De Reporte:</label>
                    <input type="text" class="form-control" name="numero_reporte" placeholder="Ingrese número de reporte" required/>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Subir Excel</label>
                    <input type="file" class="form-control-file" name="excel" accept=".xlsx" required>
                </div>
                <div class="col-12"></div>
                <div class="col-lg-3 col-md-4 form-group d-none">
                    <label for="">Ticket</label>
                    <input type="text" class="form-control form-control-sm" name="ticket">
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Corte inicio</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha1_date" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha1_time" placeholder="00:00:00" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha Corte fin</label>
                        <input type="date" class="form-control form-control-sm corte_calcular_diff" name="corte_fecha2_date" required>
                        <input type="text" class="form-control form-control-sm corte_calcular_diff clean_white_space" name="corte_fecha2_time" placeholder="00:00:00" required>
                        <div class="invalid-feedback d-block text-dark corte_diff_label" data-min="" style="font-weight: bold;">
                            DIFENCIA EN MINUTOS: 
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm">Procesar</button>
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
@include('includes.select2_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_process")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        const body = new FormData(e.target);

        utils.fetch(`${config.api}`, {
            method: "POST",
            headers: {
                // "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: body
        })
        .then(utils.fetchErrorMiddleware)
        .then(response => response.json())
        .then(response => {
            new Noty({
                type: 'success',
                layout: 'topRight',
                text: "Procesado correctamente"
            }).show();
            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            let jsonError = JSON.parse(error.message);
            alert(jsonError.message);
        });
    });

    document.querySelectorAll(".corte_calcular_diff")
    .forEach(el => {
        el.addEventListener("change", function(e){
            let fecha1_date = document.querySelector("input[name=corte_fecha1_date]").value;
            let fecha1_time = document.querySelector("input[name=corte_fecha1_time]").value;
            let fecha1 = fecha1_date+" "+fecha1_time;
            let fecha2_date = document.querySelector("input[name=corte_fecha2_date]").value;
            let fecha2_time = document.querySelector("input[name=corte_fecha2_time]").value;
            let fecha2 = fecha2_date+" "+fecha2_time;

            fecha1 = new Date(fecha1);
            fecha2 = new Date(fecha2);
            let diff = ((fecha2.getTime() - fecha1.getTime())/1000/60).toFixed(0);
            console.log(diff);

            document.querySelector(".corte_diff_label").setAttribute("data-min", diff);
            document.querySelector(".corte_diff_label").innerHTML = "DIFENCIA EN MINUTOS: "+diff+" minutos";
        });
    });

    document.querySelector("input[name=corte_fecha1_date]").addEventListener("change", function(e){
        let fecha2 = document.querySelector("input[name=corte_fecha2_date]");
        fecha2.min = e.target.value;
    });
    document.querySelector("input[name=corte_fecha2_date]").addEventListener("change", function(e){
        let fecha1 = document.querySelector("input[name=corte_fecha1_date]");
        fecha1.max = e.target.value;
    });
});
</script>
@endsection