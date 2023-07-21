@extends(backpack_view('blank'))

@section('content')

<h4 style="">Mtc suspensiones</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="mb-3 row">
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select name="tipo_reporte" class="form-control form-control-sm">
                            @foreach ($reportes as $row)
                                <option value="{{ $row['id'] }}">{{ $row['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label for="">Txt</label>
                        <input type="file" name="file1" required>
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
    const onSubmitHandler = async (e) => {
        e.preventDefault();
        const button = document.querySelector('.btn_export');
        const loader_component = document.querySelector('.loader_component');
        button.disabled = true;
        button.innerHTML = 'Cargando ...';
        loader_component.style.display = 'block';
        try {
            const data = new FormData(e.target);
            const response = await utils.fetch("{{ asset('mtc/suspensiones/export') }}", {method: 'POST', body: data});
            
            const h_message = response.headers.get('Custom-message');
            if(h_message !== null){
                alert(h_message);
            }
            
            //console.log(blob);
            let filename = 'reporte';
            const content_disp = response.headers.get('Content-Disposition');
            const header_parts = content_disp.replaceAll('"', '').split(";");
            header_parts.forEach(row => {
                if(row.split("=")[1] !== undefined){
                    filename = row.split("=")[1];
                }
            });
            
            const blob_text = await response.blob();
            if(filename.includes('.zip')){
                utils.downloadFile(blob_text, filename, 'default');
            }else{
                utils.downloadFile(blob_text, filename, 'default');
                // utils.downloadFile(blob_text, filename);
            }
        } catch (error) {
            console.log(error);
            alert(error);
        }
        button.disabled = false;
        button.innerHTML = 'Descargar';
        loader_component.style.display = 'none';
    }
    document.getElementById('form_export').addEventListener('submit', onSubmitHandler, false);
</script>
@endsection