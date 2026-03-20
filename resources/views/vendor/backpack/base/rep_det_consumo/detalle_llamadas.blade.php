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
                        <input type="text" class="form-control form-control-sm" name="cod_cliente" required>
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
                        <label for="">Consumo sin cargo</label>
                        <select class="form-control form-control-sm" name="consumo_sin_cargo" required>
                            <option value="0">NO</option>
                            <option value="1">SI</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <div class="min_periodo">
                            Periodo minimo: yyyymm
                        </div>
                    </div>
                </div>
                <div class="col-lg-3" style="display: flex; align-items: end;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
            <div class="text-danger">
                *Toda consulta que se realice se registrara en un log
                <br>*Si el periodo a consultar no se entra en el rango mostrado comunicarse con el área correspondiente
                <br>*El equipo de facturación cuenta con 5 dias de plazo luego del ciclo de cierre para cargar información del recibo
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

    let p_min = "";
    let p_max = "";

    function yyyymmFromDateInput(dateStr) {
        if (!dateStr || dateStr.length < 7) return NaN; // "yyyy-mm-dd"
        return Number(dateStr.slice(0, 4) + dateStr.slice(5, 7)); // "yyyymm"
    }

    function stopWithError(msg) {
        alert(msg);
        $(".btn_export").prop('disabled', false);
        $(".loader_component").hide();
    }

    $("#form_export").on('submit', function(e){
        e.preventDefault();
        $(".btn_export").prop('disabled', true);
        $(".loader_component").show();
        const cod_cliente = $("#form_export input[name=cod_cliente]").val();
        const periodo = $("#form_export input[name=periodo]").val();
        const tipo_input = $("#form_export select[name=tipo_input]").val();
        const fecha1 = $("#form_export input[name=fecha1]").val();
        const fecha2 = $("#form_export input[name=fecha2]").val();
        const consumo_sin_cargo = $("#form_export select[name=consumo_sin_cargo]").val();

        // ✅ VALIDACIÓN POR AÑO+MES (ignorando día)
        const minPeriod = Number(p_min);                 // yyyymm
        const maxPeriod = Number(p_max);                 // yyyymm
        const yyyymmFecha1 = yyyymmFromDateInput(fecha1);
        const yyyymmFecha2 = yyyymmFromDateInput(fecha2);

        /* let msg_error = `La factura para el numero de cuenta ${cod_cliente} en los periodos ${fecha1} y ${fecha2} no cuenta con registros en las siguientes tablas TEMP_TAG1480, TEMP_TAG1460, TEMP_TAG1470. Por favor registrar el Nintex solicitando el restore de las tablas e indicando el periodo necesario a traves del siguiente link http://wfportalnintex/dir/red/soporte/_layouts/15/start.aspx#/Solicitud%20BR/Forms/AllItems.aspx y enviar correo a Orlando Caurino con el asunto "Restore TEMP_TAG".
                Detalle de requerimiento
                Nombre del servidor: scan-dbto.tim.com.pe
                Dirección IP: 172.20.193.21
                Motor de la Base de datos: ORACLE
                Nombre de base de datos / instancia: DBTO.TEMP_TAG_1460, DBTO.TEMP_TAG_1470, DBTO.TEMP_TAG_1480`; */
                
        let msg_error = `La factura para el numero de cuenta ${cod_cliente} en los periodos ${fecha1} y ${fecha2} no cuenta con registros en las siguientes tablas TEMP_TAG_1480, TEMP_TAG_1460 y TEMP_TAG_1470. Por favor comunicarse con soporte facturación - FacturacionPostpago@claro.com.pe con copia Carlos Eduardo Farfan Castro - carlos.farfan@claro.com.pe - COLOCAR EN EL ASUNTO LA PALABRA CLAVE "TEMP_TAG_1480,TEMP_TAG_1460,TEMP_TAG_1470"`;

        if (![minPeriod, maxPeriod, yyyymmFecha1, yyyymmFecha2].every(Number.isFinite)) {
            return stopWithError("Fechas inválidas o rango (min/max) inválido.");
        }

        /* if (yyyymmFecha1 < minPeriod) {            
            return stopWithError(msg_error);
        }

        if (yyyymmFecha2 < minPeriod) {
            return stopWithError(msg_error);
        } */

        // (opcional recomendado) coherencia: fecha1 <= fecha2
        if (yyyymmFecha1 > yyyymmFecha2) {
            return stopWithError("Rango inválido: la fecha inicial no puede ser mayor que la fecha final.");
        }

        utils.fetch("{{asset($data['url_validator'])}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}`, {
            method: 'GET',
            headers: {"Accept": "application/json"}
        })
        .then(response => {
            if(!response.ok){
                throw new Error(response.statusText);
            }
            return response;
        })
        .then(response => response.json())
        .then(response => {
            if(response.passes === true){
                utils.fetch("{{asset(isset($data['url_export']) ? $data['url_export'] : '')}}"+`?cod_cliente=${cod_cliente}&periodo=${periodo}&tipo_input=${tipo_input}&fecha1=${fecha1}&fecha2=${fecha2}&consumo_sin_cargo=${consumo_sin_cargo}`, {
                    method: 'GET'
                })
                .then(response => {
                    if(!response.ok){
                        throw new Error(response.statusText);
                    }
                    return response;
                })
                //.then(response => response.blob())
                .then(async(response) => {
                    const content_disp = response.headers.get('Content-Disposition');
                    let filename = config['filename'];

                    const header_parts = content_disp.replaceAll('"', '').split(";");
                    header_parts.forEach(row => {
                        if(row.split("=")[1] !== undefined){
                            filename = row.split("=")[1];
                        }
                    });
                    const response_content = await response.blob();
                    downloadFile(response_content , filename);
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
            $(".loader_component").hide();
            $(".btn_export").prop('disabled', false);
            alert(error);
        });
    });

    if("{{ isset($data['url_export']) ? $data['url_export'] : '' }}" === ''){
        $(".btn_export").prop('disabled', true);
    }

    $("input[name=cod_cliente]").on('change', function(){
        const cod_cliente = $("#form_export input[name=cod_cliente]").val();
        utils.fetch("{{ $data['url_cliente_validator'] }}"+`?cliente=${cod_cliente}`, {
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
            p_min = resp.min_periodo;
            p_max = resp.max_periodo;

            if(resp.min_periodo === undefined){
                $(".min_periodo").html("Periodo mínimo: yyyymm<br>Periodo máximo: yyyymm <br>*Verificar que el numero de cuenta sea correcto");
            }else{
                $(".min_periodo").html(`Periodo mínimo: ${resp.min_periodo}<br>Periodo máximo: ${resp.max_periodo}`);
            }
        })
        .catch(error => {
            $(".min_periodo").html("Periodo mínimo: yyyymm<br>Periodo máximo: yyyymm");
        });
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
