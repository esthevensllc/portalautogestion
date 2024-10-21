@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')

<!-- Ajustar el z-index del modal para estar delante del backdrop -->
<style>
    .modal {
        z-index: 1050;
    }
    .modal-dialog{
        max-width: 800px;
    }
    .modal-body{
        word-wrap: break-word;
        max-height: 70vh;
        overflow-y: auto;
    }
    .modal-backdrop {
        z-index: 1040;
    }
    table {
        font-size: 0.8rem;;
    }
</style>
@endsection

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export_c">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Combinaciones:</label>
                    <input type="file" id="file-c" class="d-block file" name="excel_c" required>
                </div>
                <div class="col-auto form-group mt-auto mr-3 p-0">
                    <button type="button" id="descargaCombinaciones" class="btn btn-sm btn-secondary">Descargar Ejemplo</button>
                </div>
                <div class="col-lg-3 col-md-4 form-group mt-auto p-0">
                    <button type="submit" id="btn-submit-c" class="btn btn-danger btn-sm btn_export">Importar</button>
                </div>
            </div>
        </form>
       <!--  <hr>
        <form id="form_export_r">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Rutas:</label>
                    <input type="file" id="file-r" class="d-block file" name="excel_r" required>
                </div>
                <div class="col-auto form-group mt-auto mr-3 p-0">
                    <button type="button" id="descargaRutas" class="btn btn-sm btn-secondary">Descargar Ejemplo</button>
                </div>
                <div class="col-lg-3 col-md-4 form-group mt-auto p-0">
                    <button type="submit" id="btn-submit-r" class="btn btn-danger btn-sm btn_export">Importar</button>
                </div>
            </div>
        </form> -->
    </div>
</div>

@include('includes.spinner_loader')
@endsection

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirmar Datos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <!-- Tabla para mostrar los datos -->
                <table class="table table-bordered table-hover">
                    <thead class="bg-danger text-white" id="tableHeaders">
                        <!-- Aquí se agregarán los headers -->
                    </thead>
                    <tbody id="tableBody">
                        <!-- Aquí se agregarán los datos del Excel -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-cancel" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-confirm" data-file="" class="btn btn-danger">Aplicar</button>
            </div>
        </div>
    </div>
</div>

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
$(function() {

    let excelData = null;  // Guardar la data del Excel    
    var data,route;
    let routeCombinaciones = '{{ route('retenciones-store') }}';
    let routeRutas = '{{ route('retenciones-store-rutas') }}';

    // Leer el archivo Excel cuando se seleccione
    $('.file').on('change', function(e) {
        const file = e.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function(event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, {type: 'array'});

                // Leer la primera hoja del archivo
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

                // Convertir la hoja a formato JSON
                excelData = XLSX.utils.sheet_to_json(firstSheet, {header: 1});

                // Limpiar el contenido anterior
                $('#tableHeaders').empty();
                $('#tableBody').empty();

                // Si hay datos en el Excel
                if (excelData.length > 0) {
                    // Generar los encabezados (primera fila)
                    let headers = '<tr>';
                    excelData[0].forEach(header => {
                        headers += `<th>${header}</th>`;
                    });
                    headers += '</tr>';
                    $('#tableHeaders').html(headers);

                    // Generar las filas de datos (omitimos la primera fila que son los headers)
                    excelData.slice(1).forEach(row => {
                        let rowHtml = '<tr>';
                        row.forEach(cell => {
                            rowHtml += `<td>${cell !== undefined ? cell : ''}</td>`;
                        });
                        rowHtml += '</tr>';
                        $('#tableBody').append(rowHtml);
                    });
                }
            };

            reader.readAsArrayBuffer(file);
        }
    });

    // Al hacer clic en el botón "Aplicar" (abrir modal)
    $('#form_export_c').on('submit', function(e) {
        e.preventDefault();
        data = new FormData(e.target);
        $('#btn-confirm').data('file','file-c');
        // Verificar si se ha seleccionado un archivo
        if ($('#file-c').val()) {
            $('#confirmationModal').modal('show');  // Mostrar el modal
        } else {
            alert('Por favor, seleccione un archivo Excel.');
        }
    });

    // Al hacer clic en el botón "Aplicar" (abrir modal)
    $('#form_export_r').on('submit', function(e) {
        e.preventDefault();
        data = new FormData(e.target);
        $('#btn-confirm').data('file','file-r');
        // Verificar si se ha seleccionado un archivo
        if ($('#file-r').val()) {
            $('#confirmationModal').modal('show');  // Mostrar el modal
        } else {
            alert('Por favor, seleccione un archivo Excel.');
        }
    });

    $('#btn-cancel').on('click', function(e) {
        $('#form_export_c')[0].reset();
        $('#file-c').val("");
        $('#form_export_r')[0].reset();
        $('#file-r').val("");
    });

    // Al confirmar el modal, enviar los datos del formulario
    $('#btn-confirm').on('click', function(e) {
        $('#confirmationModal').modal('hide'); // Ocultar el modal
        if($(this).data('file') == 'file-c'){
            route = routeCombinaciones;
        }
        if($(this).data('file') == 'file-r'){
            route = routeRutas;
        }
        fetch(route, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            console.log(response);
            if(response.ok){
                new Noty({
                    text: '¡Los datos se han guardado exitosamente!',
                    type: 'success',
                    timeout: 3000,
                    layout: 'topRight'
                }).show();
                $('#form_export_c')[0].reset();
                $('#file-c').val("");
                //$('#form_export_r')[0].reset();
                //$('#file-r').val("");
            }else{
                let statusCode = response.status;
                let json_response = await response.json();
                let message = 'Hubo un error al guardar los datos, por favor intente nuevamente.';
                if(statusCode < 500){
                    message = json_response.message;
                }
                throw new Error(message);
            }            
        })
        .catch(error => {
            console.log(error);
            new Noty({
                text: error.message,
                type: 'error',
                timeout: 3000,
                layout: 'topRight'
            }).show();
        });
    });    
    $('#descargaCombinaciones').on('click', function() {
        // Aquí se especifica el nombre del archivo que quieres descargar
        const filename = 'PLANTILLA AUTOMATIZACION CARGAS_EVENTOS RED POST.xlsx'; // Cambia esto por el nombre del archivo que quieres descargar

        // Redirige al usuario para descargar el archivo
        window.location.href = `retenciones/historico/descargar-plantilla/${filename}`;
    });
    $('#descargaRutas').on('click', function() {
        // Aquí se especifica el nombre del archivo que quieres descargar
        const filename = 'rutas.xlsx'; // Cambia esto por el nombre del archivo que quieres descargar

        // Redirige al usuario para descargar el archivo
        window.location.href = `retenciones/historico/descargar-plantilla/${filename}`;
    });
});
</script>
@endsection