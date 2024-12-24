@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="row">
    <div class="mb-0 col-md-6">
        <div class="card pb-2">
            <div class="card-body">
                <h5 class="card-title">Consulta de número</h5>
                <form id="form_search" class="row">
                    @csrf
                    <div class="mb-0 col-6">
                        <div class="form-group">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Ingresa Número" name="msisdn">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-outline-danger" type="button">Buscar</button>
                                </div>
            <!--                     <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-success btn_export" type="button" id="btn_export">Exportar</button>
                                </div> -->
                            </div>
                            <small class="form-text text-muted">Ingrese el número sin el codigo 51<br>
                                Ej. 947123456</small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mb-0 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Registro de número</h5>
                <form id="form_register" class="row">
                @csrf
                    <!-- Campo Número -->
                    <div class="mb-0 col-6">
                        <input type="number" name="msisdn" class="form-control" id="numero" placeholder="Ingrese un número">
                        <small class="form-text text-muted">Ingrese el número sin el codigo 51<br>
                                Ej. 947123456</small>
                    </div>
                    <!-- Campo Operador -->
                    <div class="mb-0 col-6">
                        <label for="operador" class="form-label">Operador: </label>
                        <select class="form-select" name="operador" id="operador">
                            <option value="BITEL">BITEL</option>
                            <option value="ENTEL">ENTEL</option>
                            <option value="MOVISTAR">MOVISTAR</option>
                        </select>
                    </div>
                    <!-- Botón -->
                    <div class="col-12" style="text-align: right;">
                        <button type="submit" class="btn btn-danger">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table id="table_lista_excepciones" class="table table-sm mb-0" style="width: 100%; background: #FFFFFF;">
        <thead class="bg-danger">
            <tr>
                <th>NUMERO</th>
                <th>OPERADOR</th>
                <th>TIPO_LLAMADA</th>
                <th>DIA</th>
                <th>TIPO_CENTRAL</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.datatables_js')
<script>
$(function() {
    const config = @json($config);

    const _datatable = $("table").DataTable({
        language: {url: "{{ asset('packages/datatables-language/spanish.json') }}"},
        ajax: {
            url: config.getCentrales,
            type: "GET",
            data: function(data){
                let value = document.querySelector("form input[name=msisdn]").value;
                if(value == '') { value = 1;}
                return {...data, msisdn: value}
            },
            dataSrc: function(resp){
                return resp.data;
            }
        },
        columns: [
            {data: 'numero'},
            {data: 'operador'},
            {data: 'tipo_llamada'},
            {data: 'dia'},
            {data: 'tipo_central'}
        ],
        //serverSide: true,
        scrollX: true,
        searching: false
    });

    document.querySelector("#form_search")
    .addEventListener("submit", async function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        loader_component.style.display = 'block';
        await _datatable.ajax.reload();
        loader_component.style.display = 'none';
    });

    $('#form_register').on('submit', function (e) {
        e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

        // Obtener datos del formulario
        let formData = {
            msisdn: $('#numero').val(),
            operador: $('#operador').val(),
            _token: $('input[name="_token"]').val() // CSRF token
        };

        // Enviar los datos mediante AJAX
        $.ajax({
            url: "{{ route('centrales.registro') }}", // Ruta definida en Laravel
            method: "POST",
            data: formData,
            success: function (response) {
                // Mostrar mensaje de éxito
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    text: response.message || 'Registro exitoso',
                    timeout: 3000
                }).show();

                // Limpiar el campo número
                $('#numero').val('');
            },
            error: function (xhr) {
                // Mostrar mensaje de error
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: xhr.responseJSON.message || 'Ocurrió un error al registrar',
                    timeout: 3000
                }).show();
            }
        });
    });

});
</script>
@endsection