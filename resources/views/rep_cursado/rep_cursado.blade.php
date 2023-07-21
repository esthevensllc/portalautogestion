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
                            @foreach ($tipo_input as $row)
                                <option value="{{ $row['id'] }}">{{ $row['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="form-group tab-item tab_num_cuenta">
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
                    <div class="form-group tab-item tab_num_documento">
                        <label for="">Número de documento</label>
                        <input type="text" class="form-control form-control-sm" name="numero_documento" required>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 33000111,33111222
                        </div>
                    </div>
                    <div class="form-group tab-item tab_lineas">
                        <label for="">Excel</label>
                        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        <div class="invalid-feedback d-block text-dark">
                            Subir en archivo con formato xlsx y sin cabeceras<br>
                            Ingresar las lineas en la primera columna y anteponer el codigo 51<br>
                            Ej. 51947123456
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha inicio</label>
                        <input type="date" class="form-control form-control-sm" name="fecha1" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Fecha fin</label>
                        <input type="date" class="form-control form-control-sm" name="fecha2" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        PERIODO DISPONIBLE:<br>
                        FECHA MAXIMA: {{$periodosDisponibles->max_date}}<br>
                        FECHA MINIMA: {{$periodosDisponibles->min_date}}
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
$(function() {
    const config = @json($data);

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        $(".btn_export").prop('disabled', true);
        $(".btn_export").text('Cargando ...');
        $(".loader_component").show();

        let data = new FormData(document.getElementById("form_export"));

        utils.fetch(`{{ asset($data['url_export']) }}`, {
            method: 'POST',
            body: data,
            headers: {
                'Accept': "application/json"
            }
        })
        .then(async(response) => {
            if(!response.ok){
                const json_response = await response.json();
                throw new Error(json_response.message);
            }
            return response;
        })
        //.then(response => response.blob())
        .then(async(response) => {
            const h_message = response.headers.get('Custom-message');
            if(h_message !== null){
                alert(h_message);
            }
            
            let filename = 'reporte.csv';
            const content_disp = response.headers.get('Content-Disposition');
            const header_parts = content_disp.replaceAll('"', '').split(";");
            header_parts.forEach(row => {
                if(row.split("=")[1] !== undefined){
                    filename = row.split("=")[1];
                }
            });
            const blob = await response.blob();
            utils.downloadFile(blob, filename, 'default');
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false).text('Descargar');
        })
        .catch(error => {
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false).text('Descargar');
            alert(error);
        });
    });

    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item").hide();
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()).show();
    });
    $("#tabs_select").on('change', function(){
        $(".tabs .tab-item input").prop('disabled', true);
        $(".tabs .tab-item textarea").prop('disabled', true);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" input").prop('disabled', false);
        $(".tabs .tab-item.tab_"+$("#tabs_select").val()+" textarea").prop('disabled', false);
    });
    $("#tabs_select").trigger('change');

});
</script>
@endsection