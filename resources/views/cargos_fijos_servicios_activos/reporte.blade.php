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
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Lineas</label>
                        <textarea class="form-control form-control-sm" name="lineas" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ingrese valores separados por comas y anteponer el codigo 51<br>
                            Ej. 51914278520,51914278521
                        </div>
                    </div>
                    <div class="form-group tab-item tab_excel">
                        <label for="">Excel</label>
                        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        <div class="invalid-feedback d-block text-dark">
                            Subir en archivo con formato xlsx y sin cabeceras<br>
                            Ingresar las lineas en la primera columna y anteponer el codigo 51<br>
                            Ej. 51914278520
                        </div>
                    </div>
                    <div class="form-group tab-item tab_numero_documento">
                        <label for="">Número de documento</label>
                        <input type="text" class="form-control form-control-sm" name="numero_documento" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 20544004973
                        </div>
                    </div>
                    <div class="form-group tab-item tab_numero_cuenta">
                        <label for="">Número de cuenta</label>
                        <input type="text" class="form-control form-control-sm" name="numero_cuenta" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 7.4119331.00.00.100065
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">INGRESAR EL SN</label>
                        <input type="text" class="form-control form-control-sm" name="sn" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 27
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Generar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
                <br>
                *Responsabilidad del área de Atención Empresarial.
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($data);
    const loader_component = document.querySelector('.loader_component');
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
        

        var formData = $(this).serialize();

        $.ajax({
            url: `{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}`,
            method: 'POST',
            data: formData,
            beforeSend: function() {
                loader_component.style.display = 'block';
            },
            success: function(response) {
                new Noty({
                    type: 'success',
                    text: 'Archivo generado con éxito, puede descargarlo en el menu de reportes',
                    timeout: 3000
                }).show();
                $(".btn_export").prop('disabled', false);
                $(".btn_export").text('Generar');
            },
            error: function(xhr, status, error){
                new Noty({
                    type: 'error',
                    text: 'Hubo un error al generar el archivo',
                    timeout: 3000
                }).show();
                $(".btn_export").prop('disabled', false);
                $(".btn_export").text('Generar');
            },
            complete: function() {
                loader_component.style.display = 'none';
                $(".btn_export").prop('disabled', false);
                $(".btn_export").text('Generar');
                $("#form_export")[0].reset();
            }
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