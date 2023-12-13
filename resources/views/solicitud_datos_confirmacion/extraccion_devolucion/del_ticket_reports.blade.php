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
                    <label for="">Ticket_osiptel</label>
                    <select name="ticket" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["tickets"] as $row)
                            <option>{{ $row->ticket }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Departamento</label>
                    <select name="departamento" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                    </select>
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
    $("select[name=ticket]").select2({width: '100%'});
    $("select[name=departamento]").select2({width: '100%'});

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        let text = "Esta seguro que desea eliminar el ticket?";
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
            })
            .catch(error => {
                alert(error);
            });
        }
    });

    $("select[name=ticket]")
    .on("change", function(e){
        console.log(e.target.value);
        let _html = config.departamentos
        .filter(r => `${r.ticket}` === e.target.value)
        .map(r => `<option data-ticket="${r.ticket}">${r.departamento}</option>`)
        .join("");

        document.querySelector("select[name=departamento]").innerHTML = `<option value="">Seleccione</option>` + _html;
        $("select[name=departamento]").select2({width: '100%'});
    });
});
</script>
@endsection