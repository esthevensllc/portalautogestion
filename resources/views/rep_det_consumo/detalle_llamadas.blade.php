@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$data['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select id="tabs_select" class="form-control form-control-sm" name="tipo_input">
                            <option value="tab_lineas">INGRESAR LINEAS</option>
                            <option value="tab_excel">EXCEL</option>
                            <option value="tab_numero_documento">NUMERO DE DOCUMENTO</option>
                            <option value="tab_numero_cuenta">NUMERO DE CUENTA</option>
                            <option value="tab_cod_cliente">CODIGO CLIENTE</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Lineas</label>
                        <textarea class="form-control form-control-sm" name="lineas" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ingrese valores separados por comas y anteponer el codigo 51<br>
                            Ej. 51947123456
                        </div>
                    </div>
                    <div class="form-group tab-item tab_excel">
                        <label for="">Excel</label>
                        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        <div class="invalid-feedback d-block text-dark">
                            Subir en archivo con formato xlsx y sin cabeceras<br>
                            Ingresar las lineas en la primera columna y anteponer el codigo 51<br>
                            Ej. 51947123456
                        </div>
                    </div>
                    <div class="form-group tab-item tab_numero_documento">
                        <label for="">Número de documento</label>
                        <input type="text" class="form-control form-control-sm" name="numero_documento" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 33000111,33111222
                        </div>
                    </div>
                    <div class="form-group tab-item tab_numero_cuenta">
                        <label for="">Número de cuenta</label>
                        <input type="text" class="form-control form-control-sm" name="numero_cuenta" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000, 8.21527968.00.00.100000
                        </div>
                    </div>
                    <div class="form-group tab-item tab_cod_cliente">
                        <label for="">Codigo de cliente</label>
                        <input type="text" class="form-control form-control-sm" name="cod_cliente" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 3848,1333261
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm" name="periodo1" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm" name="periodo2" required>
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
<script>
$(function() {
    const config = @json($data);
    const downloadFile = (blob, fileName) => {
        const url = window.URL.createObjectURL(new Blob([blob]))
        const link = document.createElement('a')
        link.href= url
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();

        link.parentNode.removeChild(link);
    }

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        $(".btn_export").prop('disabled', true);
        $(".btn_export").text('Cargando ...');
        $(".loader_component").show();

        let data = new FormData(document.getElementById("form_export"));

        fetch(`{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}`, {
            method: 'POST',
            body: data
        })
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.blob())
        .then(response => {
            console.log(response);
            let filename = 'reporte.xlsx';
            if(config['filename'] !== undefined){
                filename = config['filename'];
            }
            downloadFile(response , filename);
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false).text('Descargar');
        })
        .catch(error => {
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false).text('Descargar');
            Promise.reject();
            alert(error);
            //throw(error);
        });
    });
    
    // init
    if("{{ isset($data['url_export']) ? $data['url_export'] : '' }}" === ''){
        $(".btn_export").prop('disabled', true);
    }
    //$(".tabs .tab-item").hide();
    //$(".tabs .tab-item."+$("#tabs_select").val()).show();
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item").hide();
        $(".tabs .tab-item."+$("#tabs_select").val()).show();
    });
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item input").prop('disabled', true);
        $(".tabs .tab-item textarea").prop('disabled', true);
        $(".tabs .tab-item."+$("#tabs_select").val()+" input").prop('disabled', false);
        $(".tabs .tab-item."+$("#tabs_select").val()+" textarea").prop('disabled', false);
    });
    $("#tabs_select").trigger('change');

});
</script>
@endsection