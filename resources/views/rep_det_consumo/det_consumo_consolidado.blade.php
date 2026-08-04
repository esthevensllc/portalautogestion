@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{$data['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Número de cuenta</label>
                        <textarea type="text" class="form-control form-control-sm" name="cod_cliente" required></textarea>
                        <div class="invalid-feedback d-block text-dark">
                            Ej. 8.22104233.00.00.100000
                        </div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Tipo input</label>
                        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
                            {{-- <option value="1">Periodos</option> --}}
                            <option value="2">Rango de fechas</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 tabs">
                    <div class="row">
                        <div class="col-lg-12 tab-item 1">
                            <div class="form-group">
                                <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Periodo</label>
                                <input type="text" class="form-control form-control-sm" name="periodo" placeholder="YYYYMM" required>
                                <div class="invalid-feedback d-block text-dark">
                                    Para Ingresar mas de un periodo separar por comas<br>
                                    Ej. 202208,202209
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 form-group tab-item 2">
                            <label for="">Fecha Inicio</label>
                            <input type="date" class="form-control form-control-sm" name="fecha1" required>
                        </div>
                        <div class="col-lg-12 form-group tab-item 2">
                            <label for="">Fecha Fin</label>
                            <input type="date" class="form-control form-control-sm" name="fecha2" required>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Unidad trafico</label>
                        <select class="form-control form-control-sm" name="unidad_trafico_id" required>
                            <option value="1">KB</option>
                            <option value="2">MB</option>
                            <option value="3">GB</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="" data-toggle="tooltip" data-placement="top" title="Tooltip on top">Unidad consumo</label>
                        <select class="form-control form-control-sm" name="unidad_consumo_id" required>
                            <option value="1">SEGUNDOS (SS)</option>
                            <option value="2">MINUTOS (MM:SS)</option>
                            <option value="3">HORAS (HH:MM:SS)</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Consumo sin cargo</label>
                        <select class="form-control form-control-sm" name="consumo_sin_cargo" required>
                            <option value="0">NO</option>
                            <option value="1">SI</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <p class="min_periodo">
                            Periodo mínimo: yyyymm
                            <br>
                            Periodo máximo: yyyymm 
                        </p>
                    </div>
                </div>
                <div class="col-12" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
                <br>*Si el periodo a consultar no se encuentra en el rango mostrado comunicarse con el área correspondiente.
                <br>*El equipo de facturación cuenta con 5 dias de plazo luego del ciclo de cierre para cargar información del recibo
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="reportResultModal" tabindex="-1" role="dialog" aria-labelledby="reportResultModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg report-modal-top" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" id="reportResultModalHeader">
                <h5 class="modal-title d-flex align-items-center mb-0" id="reportResultModalTitle">
                    <span class="mr-2" id="reportResultModalIcon" aria-hidden="true"></span>
                    <span id="reportResultModalTitleText"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <p class="mb-0" id="reportResultModalMessage"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    #reportResultModalMessage {
        white-space: pre-line;
        word-break: break-word;
        line-height: 1.45;
    }

    #reportResultModalHeader.modal-success {
        background-color: #dc3545;
    }

    #reportResultModalHeader.modal-error {
        background-color: #dc3545;
    }

    .report-result-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1040;
    }

    #reportResultModal {
        z-index: 1065;
        pointer-events: none;
        padding-top: 0 !important;
    }

    #reportResultModal .modal-dialog {
        pointer-events: auto;
    }

    #reportResultModal .report-modal-top {
        margin-top: 70px;
        margin-left: auto;
        margin-right: auto;
    }
</style>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
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
        $(".loader_component").show();

        const periodo = $("#form_export input[name=periodo]").val();
        const unidad_trafico_id = $("#form_export select[name=unidad_trafico_id]").val();
        const unidad_consumo_id = $("#form_export select[name=unidad_consumo_id]").val();
        const consumo_sin_cargo = $("#form_export select[name=consumo_sin_cargo]").val();
        const tipo_input = $("#form_export select[name=tipo_input]").val();
        const fecha1 = $("#form_export input[name=fecha1]").val();
        const fecha2 = $("#form_export input[name=fecha2]").val();

        const cod_clientes = $("#form_export textarea[name=cod_cliente]").val()
            .split(',')
            .map(item => item.trim())
            .filter(item => item !== '');

        const buildUrl = (baseUrl, params) => {
            return `${baseUrl}?${new URLSearchParams(params).toString()}`;
        };

        const getResponseErrorMessage = async (response, defaultMessage) => {
            try {
                const data = await response.clone().json();
                return data?.errors?.message || data?.message || defaultMessage;
            } catch (e) {
                return defaultMessage;
            }
        };

        const getPayloadErrorMessage = (payload, defaultMessage) => {
            return payload?.errors?.message || payload?.message || defaultMessage;
        };

        const finishRequest = () => {
            const loader = $("#spinnerData.loader_component, .loader_component");

            if (typeof loader.modal === 'function') {
                try {
                    loader.modal('hide');
                } catch (e) {
                    // Si el loader no fue inicializado como modal, se oculta manualmente.
                }
            }

            loader
                .hide()
                .removeClass('show')
                .attr('aria-hidden', 'true')
                .removeAttr('aria-modal')
                .css('display', 'none');

            $(".btn_export").prop('disabled', false);
        };

        const clearModalBlockingState = () => {
            $('.modal-backdrop').remove();
            $('body')
                .removeClass('modal-open')
                .css({
                    overflow: '',
                    paddingRight: ''
                });
        };

        const showReportBackdrop = () => {
            $('.report-result-backdrop').remove();
            $('body').append('<div class="report-result-backdrop"></div>');
        };

        const hideReportBackdrop = () => {
            $('.report-result-backdrop').remove();
        };

        const cleanLoaderOverlay = () => {
            finishRequest();
            clearModalBlockingState();
        };

        const showReportModal = (type, message) => {
            cleanLoaderOverlay();

            const isSuccess = type === 'success';
            const title = isSuccess ? 'Reporte solicitado correctamente' : 'No se pudo generar el reporte';
            const icon = isSuccess ? '✓' : '⚠';

            const modal = $('#reportResultModal');
            const modalHeader = $('#reportResultModalHeader');

            // Evita que el backdrop del loader o del layout quede encima del modal.
            // Además deja el modal sin backdrop para no bloquear la página completa.
            modal.appendTo('body');
            clearModalBlockingState();
            showReportBackdrop();

            modalHeader
                .removeClass('modal-success modal-error')
                .addClass(isSuccess ? 'modal-success' : 'modal-error');

            $('#reportResultModalIcon').text(icon);
            $('#reportResultModalTitleText').text(title);
            $('#reportResultModalMessage').text(message);

            if (typeof modal.modal === 'function') {
                modal.modal({
                    backdrop: false,
                    keyboard: true,
                    show: true
                });
                return;
            }

            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                const bootstrapModal = new window.bootstrap.Modal(document.getElementById('reportResultModal'), {
                    backdrop: false,
                    keyboard: true
                });
                bootstrapModal.show();
                return;
            }

            alert(`${title}\n\n${message}`);
        };

        $('#reportResultModal').on('hidden.bs.modal', function () {
            hideReportBackdrop();
            clearModalBlockingState();
        });

        if (cod_clientes.length === 0) {
            finishRequest();
            showReportModal('error', 'Debe ingresar al menos un número de cuenta.');
            return;
        }

        const processClient = async (client) => {
            const validateUrl = buildUrl(`{{asset($data['url_validator'])}}`, {
                cod_cliente: client,
                periodo,
                tipo_input,
                fecha1,
                fecha2,
            });

            const validateResponse = await fetch(validateUrl, {
                method: 'GET',
                redirect: 'manual',
                headers: { 'Accept': 'application/json' },
            }).then(utils.fetchAuthMiddleware);

            if (!validateResponse.ok) {
                const message = await getResponseErrorMessage(validateResponse, validateResponse.statusText || 'Error al validar los registros.');
                throw new Error(message);
            }

            const validateData = await validateResponse.json();

            if (validateData.passes !== true) {
                throw new Error(getPayloadErrorMessage(validateData, `No se encontraron registros para la cuenta ${client}.`));
            }

            const exportUrl = buildUrl(`{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}`, {
                cod_cliente: client,
                periodo,
                unidad_trafico_id,
                unidad_consumo_id,
                consumo_sin_cargo,
                tipo_input,
                fecha1,
                fecha2,
            });

            const exportResponse = await utils.fetch(exportUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            });

            if (!exportResponse.ok) {
                const message = await getResponseErrorMessage(exportResponse, exportResponse.statusText || 'Error al exportar el archivo.');
                throw new Error(message);
            }

            const exportData = await exportResponse.json();

            if (exportData.success !== true) {
                throw new Error(getPayloadErrorMessage(exportData, 'Error al exportar el archivo.'));
            }

            return exportData;
        };

        const processAllClients = async () => {
            for (const client of cod_clientes) {
                await processClient(client);
            }
        };

        processAllClients()
            .then(() => {
                showReportModal('success', 'Puede descargar el archivo en el menú Descarga de reportes, al finalizar el día se eliminarán todos los archivos generados para este módulo.');
            })
            .catch(error => {
                showReportModal('error', error?.message || error || 'Ocurrió un error al generar el reporte.');
            })
            .finally(() => {
                finishRequest();
            });
    });
    if("{{ isset($data['url_export']) ? $data['url_export'] : '' }}" === ''){
        $(".btn_export").prop('disabled', true);
    }

    $("textarea[name=cod_cliente]").on('change', function(){
        const cod_cliente = $("#form_export textarea[name=cod_cliente]").val();
        if (!cod_cliente.includes(',')) {
            fetch("{{ $data['url_cliente_validator'] }}"+`?cliente=${cod_cliente}`, {
                method: 'GET'
            })
            .then(response => {
                if(!response.ok){
                    throw new Error(response.statusText);
                }
                return response;
            })
            .then(response => response.json())
            .then(resp => {
                if(resp.min_periodo === undefined){
                    $(".min_periodo").html("Periodo mínimo: yyyymm<br>Periodo máximo: yyyymm <br>*Verificar que el numero de cuenta sea correcto");
                }else{
                    $(".min_periodo").html(`Periodo mínimo: ${resp.min_periodo}<br>Periodo máximo: ${resp.max_periodo}`);
                }
            })
            .catch(error => {
                $(".min_periodo").html("Periodo mínimo: yyyymm<br>Periodo máximo: yyyymm");
            });
        }
    });

    document.querySelector("input[name=fecha1]").addEventListener("change", function(e){
        let fecha2 = document.querySelector("input[name=fecha2]");
        fecha2.min = e.target.value;
    });
    document.querySelector("input[name=fecha2]").addEventListener("change", function(e){
        let fecha1 = document.querySelector("input[name=fecha1]");
        fecha1.max = e.target.value;
    });


    // select
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

