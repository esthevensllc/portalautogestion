@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="num_reporte">Nro Reporte</label>
                    <select name="num_reporte" class="form-control form-control-sm" id="num_reporte" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="ticket">Ticket</label>
                    <select name="ticket" class="form-control form-control-sm" id="ticket" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-primary btn-sm btn-process">Procesar</button>
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
    const config = @json($config);
    let ticketsByNumReporte = {};
$(function() {
    config.informesFalla.forEach(row => {
        let key = row.numero_reporte;
        if(ticketsByNumReporte[key] === undefined){
            ticketsByNumReporte[key] = [];
        }
        ticketsByNumReporte[key].push(row);
    });
    let htmlInformes = Object.keys(ticketsByNumReporte)
    .map(numero_reporte => `<option value="${numero_reporte}">${numero_reporte}</option>`).join("");
    htmlInformes = `<option value="">Seleccione</option>${htmlInformes}`;

    $("select[name=num_reporte]").html(htmlInformes).select2({width: '100%'});
    $("select[name=ticket]").select2({width: '100%'});
    // $(".btn-process").hide();

    $("select[name=num_reporte]")
    .on("change", function(e){
        let htmlReports = (ticketsByNumReporte[e.target.value]??[])
        .map(row =>  `<option value="${row.ticket}">${row.ticket}</option>`);
        htmlReports = `<option value="">Seleccione</option>${htmlReports}`;
        $("select[name=ticket]").html(htmlReports).select2({width: '100%'});
        $("select[name=ticket]").trigger("change");
    });

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let body = {
            _token: $("*[name=_token]").val(),
            num_reporte: $("select[name=num_reporte]").val(),
            ticket: $("select[name=ticket]").val(),
        };
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';
        utils.fetch(`${config.processApi}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(body)
        })
        .then(async(response) => {
            let isOk = response.ok;
            let json = await response.json();
            if(isOk){
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Procesado correctamente"
                }).show();
            }else{
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: json.message
                }).show();
            }
            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            alert(error.message);
        });
    });
});
</script>
@endsection