@extends(backpack_view('blank'))

@section('content')
<h4>{{ $config['title'] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="form-group col-lg-3 col-md-4">
                    <label for="">CLIENTE</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $config['codigo_cliente'] }}" readonly>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label for="">A&Ntilde;O</label>
                    <input type="number" class="form-control form-control-sm" name="anio" min="2000" max="2100" required>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label for="">SEMANA</label>
                    <input type="number" class="form-control form-control-sm" name="semana" min="1" max="53" required>
                </div>
                <div class="form-group col-lg-2 col-md-4 d-flex align-items-end">
                    <button type="submit" style="background: darkcyan; color: white;" class="btn btn-primary btn-sm btn_export">Descargar</button>
                </div>
            </div>

            <div class="alert alert-info py-2 mb-0">
                @if (!empty($config['available_range']['min']) && !empty($config['available_range']['max']))
                    Informaci&oacute;n disponible desde el a&ntilde;o {{ $config['available_range']['min']['y'] }} semana {{ $config['available_range']['min']['semana'] }}
                    hasta el a&ntilde;o {{ $config['available_range']['max']['y'] }} semana {{ $config['available_range']['max']['semana'] }}.
                @else
                    No se encontr&oacute; informaci&oacute;n disponible para el cliente {{ $config['codigo_cliente'] }}.
                @endif
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
<script>
$(function () {
    const config = @json($config);
    const form = document.querySelector('#form_export');
    const button = document.querySelector('.btn_export');
    const loader = document.querySelector('.loader_component');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = new FormData(form);
        const anio = String(data.get('anio') || '').trim();
        const semana = String(data.get('semana') || '').trim();

        if (!/^\d{4}$/.test(anio)) {
            showError('Debe ingresar un a\u00f1o v\u00e1lido');
            return;
        }

        if (!/^\d{1,2}$/.test(semana) || Number(semana) < 1 || Number(semana) > 53) {
            showError('Debe ingresar una semana v\u00e1lida');
            return;
        }

        toggleLoading(true);

        try {
            const response = await utils.fetch(config.url_export, {
                method: 'POST',
                body: data
            });

            if (!response.ok) {
                const contentType = response.headers.get('Content-Type') || '';
                if (contentType.includes('application/json')) {
                    const json = await response.json();
                    throw new Error(json.message || 'Ocurri\u00f3 un error al generar el archivo');
                }

                throw new Error(response.statusText || 'Ocurri\u00f3 un error al generar el archivo');
            }

            const filename = getFilename(response.headers.get('Content-Disposition'));
            const blob = await response.blob();
            utils.downloadBlob(blob, filename);
        } catch (error) {
            showError(error.message || 'Ocurri\u00f3 un error al generar el archivo');
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(isLoading) {
        if (button) {
            button.disabled = isLoading;
            button.innerHTML = isLoading ? 'Cargando ...' : 'Descargar';
        }

        if (loader) {
            loader.style.display = isLoading ? 'block' : 'none';
        }
    }

    function getFilename(contentDisposition) {
        if (!contentDisposition) {
            return 'OSINFOR.xlsx';
        }

        const match = contentDisposition.match(/filename=\"?([^"]+)\"?/i);
        return match && match[1] ? match[1] : 'OSINFOR.xlsx';
    }

    function showError(message) {
        new Noty({
            type: 'error',
            layout: 'topRight',
            text: message,
            timeout: 8000,
            progressBar: true
        }).show();
    }
});
</script>
@endsection
