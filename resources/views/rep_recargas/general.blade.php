@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$data['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Lineas</label>
                        <textarea class="form-control form-control-sm" name="lineas" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ingrese valores separados por comas y anteponer el codigo 51<br>
                            Ej. 51947123456
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm" name="fecha1" min="" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm" name="fecha2" min="" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
let fecha1 = document.querySelector("input[name=fecha1]");
let fecha2 = document.querySelector("input[name=fecha2]");
fecha1.addEventListener("change", function(e){
    fecha2.min = e.target.value;
});
fecha2.addEventListener("change", function(e){
    fecha1.max = e.target.value;
});
document.querySelector("form")
.addEventListener("submit", function(e){
    utils.downloadHandler({url: "{{ $data['url'] }}"}, e);
});
</script>
@endsection