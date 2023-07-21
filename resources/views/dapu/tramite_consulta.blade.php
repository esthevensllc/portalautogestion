@extends(backpack_view('blank'))

@section('content')

<h4 style="">TRAMITE Y CONSULTA</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select name="tipo_reporte" class="form-control form-control-sm" required>
                            <option value="">Seleccione</option>
                            @foreach ($reportes as $row)
                                <option value="{{ $row['id'] }}">{{ $row['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">CSV</label>
                        <input type="file" name="file" accept=".csv" required>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
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
    document.getElementById('form_export').addEventListener('submit', function(e){
        utils.downloadHandler(@json($config), e);
    }, false);
</script>
@endsection