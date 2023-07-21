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
                    <label for="">Tipo</label>
                    <select name="tipo" class="form-control form-control-sm reload_ticket" required>
                        @foreach ($config["tipo_solicitud"] as $row)
                            <option value="{{ $row["id"] }}">{{ $row["label"] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Carta</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Fecha</label>
                    <input type="date" class="form-control form-control-sm" name="fecha" readonly>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Eliminar</button>
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

    // inicio
    //$("select[name=ticket]").select2({width: '100%'});
    //$("select[name=departamento]").select2({width: '100%'});

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let text = "Esta seguro que desea eliminar el registro?";
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';
        if (confirm(text) == true) {
            const data = new FormData(e.target);
            fetch(config.api, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
            //.then(resp => response.json())
            .then(async(response) => {
                if(!response.ok){
                    let json_response = await response.json();
                    throw new Error(json_response.message);
                }
                
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: "Eliminado correctamente"
                }).show();
                loader_component.style.display = 'none';
            })
            .catch(error => {
                loader_component.style.display = 'none';
                alert(error);
            });
        }
    });


    function renderTickets(){
        let tipo_plan = "POSTPAGO";
        let tipo_solicitud = document.querySelector("select[name=tipo]").value;
        fetch(config.ticket_url+"/"+tipo_plan+"/"+tipo_solicitud)
        .then(resp => resp.json())
        .then(resp => {
            let _html = resp.map(row => `<option value="${row["ticket"]}" data-fecha="${row["fecha"].substr(0,10)}">
            ${row["fecha"].substr(0,10)} ${row["ticket"]}
            </option>`).join('');
            document.querySelector("select[name=ticket]").innerHTML = _html;
            $(document.querySelector("select[name=ticket]")).select2({width: '100%'});
            if(resp.length > 0){
                document.querySelector("input[name=fecha]").value = resp[0]["fecha"].substr(0,10);
            }else{
                document.querySelector("input[name=fecha]").value = '';
            }
        });
    }

    document.querySelectorAll(".reload_ticket")
    .forEach(e => {
        e.addEventListener("change", function(e){
            renderTickets();
        });
    });
    renderTickets();

    $("select[name=ticket]")
    .on("change", function(e){
        document.querySelectorAll("select[name=ticket] option")
        .forEach(option => {
            if(option.value === e.target.value){
                document.querySelector("input[name=fecha]").value = option.attributes["data-fecha"].value;
            }
        });
    });

    /*$("select[name=ticket]")
    .on("change", function(e){
        console.log(e.target.value);
        let _html = config.departamentos
        .filter(r => `${r.ticket}` === e.target.value)
        .map(r => `<option data-ticket="${r.ticket}">${r.departamento}</option>`)
        .join("");

        document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>` + _html;
        $("select[name=departamento]").select2({width: '100%'});
    });*/
});
</script>
@endsection