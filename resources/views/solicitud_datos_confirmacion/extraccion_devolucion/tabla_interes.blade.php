@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha</label>
                        <input type="date" class="form-control form-control-sm" name="fecha" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tasa</label>
                        <input type="number" class="form-control form-control-sm" name="tasa" step="any" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Factor diario</label>
                        <input type="number" class="form-control form-control-sm" name="factorDiario" step="any" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Factor acumulado</label>
                        <input type="number" class="form-control form-control-sm" name="factorAcumulado" step="any" required>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Registrar</button>
                    </div>
                </div>
                <div class="col-12">
                    <p id="max_fecha" class="d-inline pr-3">Fecha maxima:</p>
                    <a href="https://www.sbs.gob.pe/app/pp/EstadisticasSAEEPortal/Paginas/TILegalEfectiva.aspx" target="_blank" class="d-inline">Link para actualizar</a>
                    <table id="tabla_interes" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Factor Acumulado</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
    console.log(config);

    function renderTable()
    {
        fetch(config.api, {method: 'GET', headers: {"Accept": "application/json"}})
        .then(async(response) => {
            if(!response.ok){
                let json_response = await response.json();
                throw new Error(json_response.message);
            }
            return response.json();
        })
        .then(async(response) => {
            let htmlBody = response.data.map(row => `<tr>
                <td>${row.fecha}</td>
                <td>${row.factoracumulado}</td>
            </tr>`).join('');

            document.querySelector("#max_fecha").innerHTML = "Fecha maxima: "+response.maxFecha;
            document.querySelector("#tabla_interes tbody").innerHTML = htmlBody;
        })
        .catch(error => {
            console.log(error);
            alert(error);
            /*new Noty({
                type: 'error',
                layout: 'topRight',
                //autoHideDelay: 5000,
                //autoHide: false,
                text: error.message
            }).show();*/
        });
    }

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const data = new FormData(e.target);
        fetch(config.api, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            if(!response.ok){
                let json_response = await response.json();
                throw new Error(json_response.message);
            }
            renderTable();
            new Noty({
                type: 'success',
                layout: 'topRight',
                text: "Registrado correctamente"
            }).show();
        })
        .catch(error => {
            alert(error);
        });
    });

    renderTable();
});
</script>
@endsection