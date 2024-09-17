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
                <div class="col-lg-12 form-group">
                    <span>Combinaciones:</span>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">Pk</label>
                    <select name="pk" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["pks"] as $row)
                            <option>{{ $row->pk }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Operador</label>
                    <select name="operador" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["operadores"] as $row)
                            <option>{{ $row->operador }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Flag_dpto</label>
                    <select name="flag_dpto" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["flag_dptos"] as $row)
                            <option>{{ $row->flag_dpto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Flag</label>
                    <select name="flag" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["flags"] as $row)
                            <option>{{ $row->flag }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Target</label>
                    <select name="target" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["targets"] as $row)
                            <option>{{ $row->target }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Decil</label>
                    <select name="decil" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["deciles"] as $row)
                            <option>{{ $row->decil }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 form-group">
                    <label for="">Callcenter</label>
                    <select name="callcenter" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["callcenters"] as $row)
                            <option>{{ $row->callcenter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">Final</label>
                    <select name="final" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["finales"] as $row)
                            <option>{{ $row->final }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-12 form-group">
                    <span>Rutas:</span>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">Callcenter</label>
                    <select name="callcenter_cc" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["callcenters_cc"] as $row)
                            <option>{{ $row->callcenter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">Operadorfinal</label>
                    <select name="operadorfinal" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["operadorfinales"] as $row)
                            <option>{{ $row->operadorfinal }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">FileName</label>
                    <select name="fileName" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["fileNames"] as $row)
                            <option>{{ $row->fileName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 form-group">
                    <label for="">PathName</label>
                    <select name="pathName" class="form-control form-control-sm" required>
                        <option value="">Seleccione</option>
                        @foreach ($config["pathNames"] as $row)
                            <option>{{ $row->pathName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-12 col-md-12 form-group">
                    <button type="submit" class="btn btn-danger btn-sm btn_export">Aplicar</button>
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
    // inicio
    const config = @json($config);

    $("select[name=pk]").select2({width: '100%'});

    $('#form_export').on('submit', function (e) {
        e.preventDefault(); // Evitar el envío del formulario por defecto

        $.ajax({
            url: "{{ route('retenciones-store') }}", // La URL del controlador
            method: 'POST',
            data: $(this).serialize(), // Serializa todos los campos del formulario
            success: function (response) {
                // Si el insert es exitoso
                new Noty({
                    text: '¡Los datos se han guardado exitosamente!',
                    type: 'success',
                    timeout: 3000,
                    layout: 'topRight'
                }).show();

                // Reiniciar los valores del formulario
                $('#form_export')[0].reset();
                $('select').val(null).trigger('change');
            },
            error: function (xhr, status, error) {
                // Si hubo un error
                new Noty({
                    text: 'Hubo un error al guardar los datos, por favor intente nuevamente.',
                    type: 'error',
                    timeout: 3000,
                    layout: 'topRight'
                }).show();
            }
        });
    });
});
</script>
@endsection