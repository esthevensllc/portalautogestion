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
                    <label for="">Ticket</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Nro Reporte</label>
                    <select name="num_reporte" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Departamento</label>
                    <select name="departamento" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-secondary btn-sm btn_export">Calcular usuarios</button>
                </div>
                <div class="col-lg-12 mb-2 list-minutos">
                </div>
                <div class="col-lg-12">
                    <button type="button" class="btn btn-primary btn-sm btn-process">Procesar</button>
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
$(function() {
    let departamentosFinded = [];

    let tickets = {};
    config.reports.forEach(row => {
        tickets[row.ticket] = row;
    });
    let htmlTickets = Object.keys(tickets)
    .map(ticket => `<option value="${ticket}">${ticket}</option>`)
    .join("");
    htmlTickets = `<option value="">Seleccione</option>${htmlTickets}`;

    $("select[name=ticket]").html(htmlTickets).select2({width: '100%'});
    $("select[name=num_reporte]").select2({width: '100%'});
    $("select[name=departamento]").select2({width: '100%'});
    $(".btn-process").hide();

    $("select[name=ticket]")
    .on("change", function(e){
        ;
        let htmlReports = config.reports.filter(row => row.ticket === e.target.value)
        .map(row =>  `<option>${row.numero_de_reporte}</option>`);
        htmlReports = `<option value="">Seleccione</option>${htmlReports}`;
        $("select[name=num_reporte]").html(htmlReports).select2({width: '100%'});
        $("select[name=num_reporte]").trigger("change");
    });

    $("select[name=num_reporte]")
    .on("change", function(e){
        if(e.target.value === ""){
            document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>`;
            $("select[name=departamento]").select2({width: '100%'});
        }else{
            utils.fetch(`${config.getDepartamentosApi}?num_reporte=${e.target.value}`)
            .then(utils.fetchErrorMiddleware)
            .then(response => response.json())
            .then(response => {
                departamentosFinded = response;
                let _html = response.map(r => `<option data-num_reporte="${r.num_reporte}">${r.departamento}</option>`)
                .join("");
                document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>` + _html;
                $("select[name=departamento]").select2({width: '100%'});
            })
            .catch(error => {
                let jsonError = JSON.parse(error.message);
                alert(jsonError.message);
            });
        }
    });

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let num_reporte = $("select[name=num_reporte]").val();
        let departamento = $("select[name=departamento]").val();
        
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';

        utils.fetch(`${config.getUsuariosApi}?num_reporte=${num_reporte}&departamento=${departamento}`)
        .then(utils.fetchErrorMiddleware)
        .then(response => response.json())
        .then(response => {
            let html = response.usuarios.map(row => `<div class="form-check">
                <input class="form-check-input" type="radio" name="num_minutos" id="radio-minutos${row.minutos}" value="${row.minutos}">
                <label class="form-check-label" for="radio-minutos${row.minutos}">${row.minutos}min ${row.num_usuarios} Abonados</label>                
            </div>`).join('');
            html = html + `<div class="form-group form-inline">
                    <label for="numberInput">Ingrese la cantidad de minutos a calcular: </label>
                    <div class="input-group ml-2">
                        <input type="number" class="form-control" id="numberInput" placeholder="Ingrese minutos" name="minutos_calc">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary btn-calcular">Calcular</button>
                        </div>
                    </div>
                </div>`;
            $(".list-minutos").html(html);
            $(".btn-process").show();

            loader_component.style.display = 'none';
        })
        .catch(error => {
            loader_component.style.display = 'none';
            let jsonError = JSON.parse(error.message);
            alert(jsonError.message);
        });

    });

    document.querySelector(".btn-process")
    .addEventListener("click", function(e){
        let num_minutos = $("*[name=num_minutos]:checked").val();
        if(num_minutos === undefined){
            alert("Debe seleccionar una opcion antes de continuar");
            return;
        }else{
            let body = {
                _token: $("*[name=_token]").val(),
                num_reporte: $("select[name=num_reporte]").val(),
                departamento: $("select[name=departamento]").val(),
                minutos_usuarios: num_minutos
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
            .then(utils.fetchErrorMiddleware)
            .then(response => response.json())
            .then(response => {
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Procesado correctamente"
                }).show();
                loader_component.style.display = 'none';

                let informeInput = departamentosFinded[0];
                if(informeInput !== undefined && Number(informeInput.tipo_reporte) === 2){
                    utils.downloadHandler({
                        url: config.exportRepMsisdnApi,
                        btn_export: '.btn-process',
                        requestOptions: {
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({
                                _token: document.querySelector("input[name=_token]").value,
                                ticket: $("select[name=ticket]").val()
                            })
                        }
                    }, {});
                }
            })
            .catch(error => {
                loader_component.style.display = 'none';
                let jsonError = JSON.parse(error.message);
                alert(jsonError.message);
            });
        }
    });

    $('body').on('click','.btn-calcular', function(e){
        e.preventDefault();
        let minutos_calc = $("input[name=minutos_calc]").val();
        if(minutos_calc === undefined || minutos_calc == ''){
            alert("Debe ingresar los minutos antes de volver a calcular");
            return;
        }else{
            let num_reporte = $("select[name=num_reporte]").val();
            let departamento = $("select[name=departamento]").val();
            
            const loader_component = document.querySelector('.loader_component');
            loader_component.style.display = 'block';

            utils.fetch(`${config.getUsuariosByMinutosApi}?num_reporte=${num_reporte}&departamento=${departamento}&minutos_calc=${minutos_calc}`)
            .then(utils.fetchErrorMiddleware)
            .then(response => response.json())
            .then(response => {
                let html = response.usuarios.map(row => `<div class="form-check">
                    <input class="form-check-input" type="radio" name="num_minutos" id="radio-minutos${row.minutos}" value="${row.minutos}">
                    <label class="form-check-label" for="radio-minutos${row.minutos}">${row.minutos}min ${row.num_usuarios} Abonados</label>                
                </div>`).join('');
                html = html + `<div class="form-group form-inline">
                        <label for="numberInput">Ingrese la cantidad de minutos a calcular: </label>
                        <div class="input-group ml-2">
                            <input type="number" class="form-control" id="numberInput" placeholder="Ingrese minutos" name="minutos_calc">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary btn-calcular">Calcular</button>
                            </div>
                        </div>
                    </div>`;
                $(".list-minutos").html(html);
                $(".btn-process").show();

                loader_component.style.display = 'none';
            })
            .catch(error => {
                loader_component.style.display = 'none';
                let jsonError = JSON.parse(error.message);
                alert(jsonError.message);
            });
        }
    });
});
</script>
@endsection