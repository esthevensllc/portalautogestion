@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@include('includes.imr_css')
<style>
    .chart-shell {
        position: relative;
    }

    .history-scroll.is-scrollable {
        max-height: 318px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .history-scroll.is-scrollable thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .chart-shell.is-empty canvas {
        opacity: 0;
    }

    .chart-empty-state {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #6b7280;
        pointer-events: none;
    }

    .chart-shell.is-empty .chart-empty-state {
        display: flex;
    }

    .chart-shell.is-empty .percent {
        display: none;
    }
</style>
@endsection

@section('content')
<section class="imr-card" aria-label="{{ $config['title'] }}">
    <div class="consulta-header">
        <h1>{{ $config['title'] }}</h1>
        <div class="claro-logo" aria-label="Claro">Claro</div>
    </div>

    <div class="content">
        <section class="customer-strip" aria-label="Consulta e información del cliente">
            <form class="search-panel" id="imrimfSearchForm" action="{{ $config['search_url'] }}" method="get">
                <label for="customer-id">{{ $config['input_label'] }}</label>
                <div class="search-row">
                    <input id="customer-id" name="telefono" type="text" placeholder="{{ $config['input_placeholder'] }}" autocomplete="off" />
                    <button class="action-btn primary" id="searchButton" type="submit" title="Buscar" aria-label="Buscar">⌕</button>
                    <button class="action-btn" id="clearSearch" type="button" title="Borrar texto" aria-label="Borrar texto">↻</button>
                </div>
                <small id="searchMessage" style="display:block;margin-top:8px;color:#b91c1c;"></small>
            </form>

            <div class="info-scroll">
                <table class="info-table" aria-label="Información de cliente">
                    <thead>
                        <tr>
                            <th>Customer ID / Teléfono</th>
                            <th>Plan Tarifario</th>
                            <th>Rango Antigüedad</th>
                            <th>Segmento de Valor</th>
                            <th>Cargo Fijo</th>
                            <th>Tipo Abonado</th>
                        </tr>
                    </thead>
                    <tbody id="customerInfoBody">
                        <tr>
                            <td colspan="6">Ingrese un valor para consultar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-grid">
            <section class="usage-area">
                <p class="usage-title">{{ $config['title_percent'] }}</p>

                <div class="badge">
                    <span id="productLabelText">{{ $config['product_label'] }}</span>
                    <span id="imrTotalText">S/ 0</span>
                </div>

                <div class="chart-shell is-empty" id="chartShell" role="img" aria-label="Sin datos de {{ $config['product_label'] }}">
                    <span class="percent used" id="usedPercentText">--</span>
                    <canvas id="imfChart"></canvas>
                    <div class="chart-empty-state" id="chartEmptyState">Sin datos</div>
                    <span class="percent free" id="freePercentText">--</span>
                </div>

                <div class="cards">
                    <article class="metric-card">
                        <strong id="cantidadAccionesText">0</strong>
                        <span>Cantidad de Acciones</span>
                    </article>
                    <article class="metric-card">
                        <strong id="montoConsumidoText">S/ 0</strong>
                        <span>Monto Consumido</span>
                    </article>
                </div>
            </section>

            <section class="history-area">
                <h2 class="section-title">Historial de acciones de fidelización otorgadas a la línea:</h2>
                <div class="history-scroll" id="historyScroll">
                    <table class="history-table" aria-label="Historial de acciones de fidelización">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Interacción</th>
                                <th>Total</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            <tr>
                                <td colspan="5">Sin Datos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="summary-title">Resumen por acciones:</h3>
                <table class="summary-table" aria-label="Resumen por acciones">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Cantidad de Fidelizaciones</th>
                            <th>Importe</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody">
                        <tr>
                            <td colspan="3">Sin Datos.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </section>
    </div>
</section>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.select2_js')
@include('includes.utils_js')
@include('includes.imr_js')
<script>
    const imrimfConfig = @json($config);
    const imfJson = imrimfConfig.initial_chart || {
        hasData: false,
        total: 0,
        saldo: 0,
        cantidadAcciones: 0,
        montoConsumido: 0
    };

    let imfChartInstance = null;
    let colorSaldo = '#7ee95f';
    let colorConsumido = '#eeeeee';
    let colorBorder = '#eeeeee';

    const centerTextPlugin = {
        id: 'centerTextPlugin',
        afterDraw(chart, args, options) {
            const { ctx, chartArea } = chart;
            const centerX = (chartArea.left + chartArea.right) / 2;
            const centerY = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.fillStyle = options.valueColor || colorSaldo;
            ctx.font = '700 28px Arial, Helvetica, sans-serif';
            ctx.fillText(options.valueText || '', centerX, centerY - 8);

            ctx.fillStyle = options.labelColor || colorConsumido;
            ctx.font = '700 12px Arial, Helvetica, sans-serif';
            ctx.fillText(options.labelText || '', centerX, centerY + 20);

            ctx.restore();
        }
    };

    Chart.register(centerTextPlugin);

    function formatSoles(value, decimals = 2) {
        const amount = Number(value) || 0;
        return `S/ ${amount.toFixed(decimals)}`;
    }

    function limitarValor(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function calcularTopSegmento(startAngleDeg, sweepDeg) {
        const midAngleDeg = startAngleDeg + (sweepDeg / 2);
        const midAngleRad = (midAngleDeg * Math.PI) / 180;
        const center = 50;
        const radiusPercent = 47;
        const top = center + (Math.sin(midAngleRad) * radiusPercent);
        return limitarValor(top, 18, 82);
    }

    function actualizarPosicionPorcentajes(porcentajeLibreNumero, porcentajeUsadoNumero) {
        const freeLabel = document.getElementById('freePercentText');
        const usedLabel = document.getElementById('usedPercentText');

        const freeSweep = (porcentajeLibreNumero / 100) * 360;
        const usedSweep = (porcentajeUsadoNumero / 100) * 360;

        const freeTop = porcentajeLibreNumero <= 30 ? calcularTopSegmento(-90, freeSweep) : 50;
        const usedTop = porcentajeUsadoNumero <= 30 ? calcularTopSegmento(-90 + freeSweep, usedSweep) : 50;

        freeLabel.style.top = `${freeTop}%`;
        usedLabel.style.top = `${usedTop}%`;
    }

    function resetGraficoSinDatos() {
        if (imfChartInstance) {
            imfChartInstance.destroy();
            imfChartInstance = null;
        }

        const chartShell = document.getElementById('chartShell');
        chartShell.classList.add('is-empty');
        chartShell.setAttribute('aria-label', `Sin datos de ${imrimfConfig.product_label}`);

        document.getElementById('imrTotalText').textContent = formatSoles(0, 2);
        document.getElementById('usedPercentText').textContent = '--';
        document.getElementById('freePercentText').textContent = '--';
        document.getElementById('cantidadAccionesText').textContent = '0';
        document.getElementById('montoConsumidoText').textContent = formatSoles(0, 2);
    }

    function llenarCuadroImf(data) {
        const importeTotal = Number(data?.montoConsumido ?? data?.total ?? 0) || 0;
        const cantidadAcciones = Number(data?.cantidadAcciones) || 0;

        if (!data?.hasData || importeTotal <= 0) {
            resetGraficoSinDatos();
            return;
        }

        const porcentajeUsadoNumero = 100;
        const porcentajeLibreNumero = 0;
        const porcentajeUsado = porcentajeUsadoNumero.toFixed(2);
        const porcentajeLibre = porcentajeLibreNumero.toFixed(2);

        document.getElementById('imrTotalText').textContent = formatSoles(importeTotal, 2);
        document.getElementById('usedPercentText').textContent = `${porcentajeUsado}%`;
        document.getElementById('freePercentText').textContent = `${porcentajeLibre}%`;
        document.getElementById('cantidadAccionesText').textContent = cantidadAcciones;
        document.getElementById('montoConsumidoText').textContent = formatSoles(importeTotal, 2);

        actualizarPosicionPorcentajes(porcentajeLibreNumero, porcentajeUsadoNumero);

        const chartShell = document.getElementById('chartShell');
        chartShell.classList.remove('is-empty');
        chartShell.setAttribute(
            'aria-label',
            `Importe total de ${imrimfConfig.product_label}: ${importeTotal} soles`
        );

        const ctx = document.getElementById('imfChart');

        if (imfChartInstance) {
            imfChartInstance.destroy();
        }

        imfChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Importe total'],
                datasets: [
                    {
                        data: [importeTotal],
                        backgroundColor: [colorSaldo],
                        borderColor: colorBorder,
                        borderWidth: 5,
                        hoverOffset: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                rotation: 0,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    },
                    centerTextPlugin: {
                        valueText: formatSoles(importeTotal, 0),
                        labelText: 'IMPORTE',
                        valueColor: colorSaldo,
                        labelColor: colorConsumido
                    }
                }
            }
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setMessage(message, isError = true) {
        const element = document.getElementById('searchMessage');
        element.textContent = message || '';
        element.style.color = isError ? '#b91c1c' : '#166534';
    }

    function displayValue(value, fallback = '-') {
        if (value === null || value === undefined || String(value).trim() === '') {
            return fallback;
        }

        return escapeHtml(value);
    }

    function renderCustomerInfo(customerInfo, searchedValue) {
        const info = customerInfo && typeof customerInfo === 'object'
            ? customerInfo
            : null;

        if (!info) {
            document.getElementById('customerInfoBody').innerHTML = `
                <tr>
                    <td>${escapeHtml(searchedValue)}</td>
                    <td colspan="5">No se encontró información del cliente en la partición vigente.</td>
                </tr>
            `;
            return;
        }

        document.getElementById('customerInfoBody').innerHTML = `
            <tr>
                <td>${displayValue(info.customer_id_telefono, searchedValue)}</td>
                <td>${displayValue(info.plan_tarifario)}</td>
                <td>${displayValue(info.rango_antiguedad)}</td>
                <td>${displayValue(info.segmento_valor)}</td>
                <td>${displayValue(info.cargo_fijo)}</td>
                <td>${displayValue(info.tipo_abonado)}</td>
            </tr>
        `;
    }

    function renderHistory(rows) {
        const tbody = document.getElementById('historyBody');
        const historyRows = Array.isArray(rows) ? rows : [];
        const historyScroll = document.getElementById('historyScroll');

        historyScroll.classList.toggle('is-scrollable', historyRows.length > 5);

        if (historyRows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">No se encontraron resultados.</td></tr>';
            return;
        }

        tbody.innerHTML = historyRows.map(row => `
            <tr>
                <td>${escapeHtml(row.fecha)}</td>
                <td>${escapeHtml(row.tipo)}</td>
                <td>${escapeHtml(row.interaccion)}</td>
                <td>${formatSoles(row.total, 2)}</td>
                <td>${escapeHtml(row.detalle)}</td>
            </tr>
        `).join('');
    }

    function renderSummary(rows) {
        const tbody = document.getElementById('summaryBody');
        const summaryRows = Array.isArray(rows) ? rows : [];

        if (summaryRows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3">No hay resumen para mostrar.</td></tr>';
            return;
        }

        const totalCantidad = summaryRows.reduce((acc, row) => acc + (Number(row.cantidad) || 0), 0);
        const totalImporte = summaryRows.reduce((acc, row) => acc + (Number(row.importe) || 0), 0);

        tbody.innerHTML = summaryRows.map(row => `
            <tr>
                <td>${escapeHtml(row.tipo)}</td>
                <td>${Number(row.cantidad) || 0}</td>
                <td>${formatSoles(row.importe, 2)}</td>
            </tr>
        `).join('') + `
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>${totalCantidad}</strong></td>
                <td><strong>${formatSoles(totalImporte, 2)}</strong></td>
            </tr>
        `;
    }

    function calcularDataGraficoDesdeResumen(totals) {
        const montoConsumido = Number(totals?.importe_total) || 0;
        const cantidadAcciones = Number(totals?.cantidad_acciones) || 0;

        return {
            hasData: montoConsumido > 0 && cantidadAcciones > 0,
            total: montoConsumido,
            saldo: 0,
            montoConsumido,
            cantidadAcciones
        };
    }

    async function buscarAcciones(telefono) {
        const button = document.getElementById('searchButton');
        const url = new URL(imrimfConfig.search_url, window.location.origin);
        url.searchParams.set('telefono', telefono);

        button.disabled = true;
        setMessage('Consultando...', false);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'No se pudo completar la consulta.');
            }

            const data = payload.data;
            const historyRows = Array.isArray(data.history) ? data.history : [];
            const summaryRows = Array.isArray(data.summary) ? data.summary : [];

            const customerInfo = data.customer_info && typeof data.customer_info === 'object'
                ? data.customer_info
                : null;

            renderCustomerInfo(customerInfo, data.telefono);
            renderHistory(historyRows);
            renderSummary(summaryRows);

            if (historyRows.length === 0 && summaryRows.length === 0) {
                resetGraficoSinDatos();
                setMessage(
                    customerInfo
                        ? 'Se encontró información del cliente, pero no registra acciones.'
                        : 'No se encontraron resultados.',
                    false
                );
                return;
            }

            llenarCuadroImf(calcularDataGraficoDesdeResumen(data.totals));
            setMessage(`Registros encontrados: ${historyRows.length}.`, false);
        } catch (error) {
            setMessage(error.message || 'No se pudo completar la consulta.');
        } finally {
            button.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        resetGraficoSinDatos();

        const form = document.getElementById('imrimfSearchForm');
        const input = document.getElementById('customer-id');
        const clearButton = document.getElementById('clearSearch');

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const telefono = input.value.trim();

            if (!telefono) {
                setMessage('Ingrese un teléfono o Customer ID.');
                return;
            }

            buscarAcciones(telefono);
        });

        clearButton.addEventListener('click', function () {
            input.value = '';
            input.focus();
            setMessage('');
            document.getElementById('customerInfoBody').innerHTML = '<tr><td colspan="6">Ingrese un valor para consultar.</td></tr>';
            document.getElementById('historyBody').innerHTML = '<tr><td colspan="5">Sin Datos.</td></tr>';
            document.getElementById('historyScroll').classList.remove('is-scrollable');
            document.getElementById('summaryBody').innerHTML = '<tr><td colspan="3">Sin Datos.</td></tr>';
            resetGraficoSinDatos();
        });

        const initialTelefono = new URLSearchParams(window.location.search).get('telefono');
        if (initialTelefono) {
            input.value = initialTelefono;
            buscarAcciones(initialTelefono);
        }
    });
</script>
@endsection
