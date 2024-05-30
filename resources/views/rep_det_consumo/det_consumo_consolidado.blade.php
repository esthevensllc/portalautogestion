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
                <br>*Si el periodo a consultar no se entra en el rango mostrado comunicarse con el área de Facturación a Clientes
                <br>Ingresar al siguiente link para ver los reportes <a href="http://172.19.192.170/portalautogestion_Detalle_Consolidado/" target="_blank">http://172.19.192.170/portalautogestion_Detalle_Consolidado/<a>
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

        let cod_cliente = $("#form_export textarea[name=cod_cliente]").val();
        const periodo = $("#form_export input[name=periodo]").val();
        const unidad_trafico_id = $("#form_export select[name=unidad_trafico_id]").val();
        const unidad_consumo_id = $("#form_export select[name=unidad_consumo_id]").val();
        const consumo_sin_cargo = $("#form_export select[name=consumo_sin_cargo]").val();
        const tipo_input = $("#form_export select[name=tipo_input]").val();
        const fecha1 = $("#form_export input[name=fecha1]").val();
        const fecha2 = $("#form_export input[name=fecha2]").val();

        // Si cod_cliente contiene comas, dividirlo en un array
        if (cod_cliente.includes(',')) {
            cod_cliente = cod_cliente.split(',').map(item => item.trim());
        } else {
            cod_cliente = [cod_cliente];
        }

        // Función para procesar un solo cod_cliente
        const processClient = async (client) => {
            try {
                const validateResponse = await fetch(`{{asset($data['url_validator'])}}?cod_cliente=${client}&periodo=${periodo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}`, {
                    method: 'GET',
                    redirect: 'manual'
                }).then(utils.fetchAuthMiddleware);

                if (!validateResponse.ok) {
                    throw new Error(validateResponse.statusText);
                }

                const validateData = await validateResponse.json();

                if (validateData.passes === true) {
                    const exportResponse = await utils.fetch(`{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}?cod_cliente=${client}&periodo=${periodo}&unidad_trafico_id=${unidad_trafico_id}&unidad_consumo_id=${unidad_consumo_id}&consumo_sin_cargo=${consumo_sin_cargo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}`, {
                        method: 'GET'
                    });

                    if (!exportResponse.ok) {
                        throw new Error(exportResponse.statusText);
                    }

                    const exportData = await exportResponse.json();

                    if (!exportData.success) {
                        throw new Error('Error al exportar el archivo');
                    }

                    console.log('File saved to:', exportData.path);

                } else {
                    console.log(validateData.errors.message);
                }
            } catch (error) {
                console.log(error);
            } finally {
                $(".loader_component").hide();
                $(".btn_export").prop('disabled', false);
            }
        };

        // Función para iterar sobre todos los clientes secuencialmente
        const processAllClients = async () => {
            for (const client of cod_cliente) {
                await processClient(client);
            }
        };

        // Iniciar el procesamiento
        processAllClients().then(() => {
            console.log('Todos los clientes han sido procesados.');
        }).catch(error => {
            console.log(error);
        });

        /*fetch("{{asset($data['url_validator'])}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}`, {
            method: 'GET',
            redirect: 'manual'
        })
        .then(utils.fetchAuthMiddleware)
        .then(async response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            if(response.passes === true){
                utils.fetch("{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}&unidad_trafico_id=${unidad_trafico_id}&unidad_consumo_id=${unidad_consumo_id}&consumo_sin_cargo=${consumo_sin_cargo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}`, {
                    method: 'GET'
                })
                .then(response => {
                    if(!response.ok){
                        throw new Error(response.statusText);
                    }
                    return response;
                })
                .then(response => response.blob())
                .then(response => {
                    let filename = 'reporte.xlsx';
                    if(config['filename'] !== undefined){
                        filename = config['filename'];
                    }
                    downloadFile(response , filename);
                    $(".loader_component").hide();
                    $(".btn_export").prop('disabled', false);
                })
                .catch(error => {
                    $(".loader_component").hide();
                    $(".btn_export").prop('disabled', false);
                    Promise.reject();
                    alert(error);
                    //throw(error);
                });
            }else{
                $(".loader_component").hide();
                $(".btn_export").prop('disabled', false);
                alert(response.errors.message);
            }
        })
        .catch(error => {
            console.log(error);
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false);
            alert(error);
        });*/
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
