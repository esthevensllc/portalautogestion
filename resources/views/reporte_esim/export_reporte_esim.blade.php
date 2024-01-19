@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$config['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Nintex</label>
                        <input type="text" class="form-control form-control-sm" name="nintex" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ingresar un número de cinco digitos como máximo
                            Ej. 00001
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="form-group">
                        <label for="">Archivo csv</label>
                        <input type="file" accept=".csv" name="archivo" required>
                        <div class="invalid-feedback d-block text-dark">
                            Cargar un archivo csv delimitado por comas ",".<br>
                            Cargar cabeceras validas<br>
                            Ej. FECHA,N_LINEA,ICCID,IMSI_NUEVO,TAC,DESCRIPCION_GSMA
                        </div>
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
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
let config = @json($config);
document.querySelector("form")
.addEventListener("submit", function(e){
    e.preventDefault();
    const data = new FormData(e.target);

    let cellExpression = /^[0-9]/;
    if(data.get("nintex").length !== 5 || !data.get("nintex").match(cellExpression)){
        alert("El nintex debe ser un numero y contener como maximo 5 digitos");
        return;
    }
    utils.downloadHandler({
        url: config.url_export,
        requestOptions: {
            headers: {"Accept": "application/json"}
        }
    }, e);
});
</script>
@endsection