@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.select2_css')
@include('includes.imr_css')
@endsection

@section('content')
<section class="imr-card" aria-label="Consulta de Acciones IMR">
      <div class="consulta-header">
        <h1>{{$config['title']}}</h1>
        <div class="claro-logo" aria-label="Claro">Claro</div>
      </div>

      <div class="content">
        <section class="customer-strip" aria-label="Consulta e información del cliente">
          <form class="search-panel" action="#" method="get">
            <label for="customer-id">Customer ID</label>
            <div class="search-row">
              <input id="customer-id" name="customer_id" type="text" placeholder="Customer ID" />
              <button class="action-btn primary" type="submit" title="Buscar" aria-label="Buscar">⌕</button>
              <button class="action-btn" id="clearSearch" type="button" title="Borrar texto" aria-label="Borrar texto">↻</button>
            </div>
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
              <tbody>
                <tr>
                  <td>37156588</td>
                  <td>Plan Hfc 3play</td>
                  <td>Mayor a 3 meses</td>
                  <td>A</td>
                  <td>S/ 239.00</td>
                  <td>HFC</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="dashboard-grid">
          <section class="usage-area">
            <p class="usage-title">{{$config['title_percent']}}</p>

            <div class="badge">
              <span>IMR</span>
              <span id="imrTotalText">S/ 0</span>
            </div>

            <div
              class="chart-shell"
              id="chartShell"
              role="img"
              aria-label="Saldo de IMF"
            >
              <span class="percent used" id="usedPercentText">0%</span>
              <canvas id="imfChart"></canvas>
              <span class="percent free" id="freePercentText">0%</span>
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
            <div class="history-scroll">
              <table class="history-table" aria-label="Historial de acciones de fidelización">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Total</th>
                    <th>Asesor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>01/04/2026</td>
                    <td>Nota de Crédito</td>
                    <td>S/ 30.00</td>
                    <td>C15704</td>
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
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Nota de Crédito</td>
                  <td>1</td>
                </tr>
                <tr>
                  <td><strong>Total</strong></td>
                  <td><strong>1</strong></td>
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
    const imfJson = {
      total: 69,
      saldo: 35,
      cantidadAcciones: 1
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

    function formatSoles(value, decimals = 0) {
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

    function llenarCuadroImf(data) {
        const total = Number(data.total) || 0;
        const saldo = Number(data.saldo) || 0;
        const cantidadAcciones = Number(data.cantidadAcciones) || 0;

        const saldoGrafico = Math.min(Math.max(saldo, 0), total);
        const consumido = Math.max(total - saldoGrafico, 0);

        const porcentajeLibreNumero = total > 0 ? (saldoGrafico / total) * 100 : 0;

        const porcentajeUsadoNumero = total > 0 ? (consumido / total) * 100 : 0;

        const porcentajeLibre = porcentajeLibreNumero.toFixed(2);
        const porcentajeUsado = porcentajeUsadoNumero.toFixed(2);

        document.getElementById('imrTotalText').textContent = formatSoles(total);
        document.getElementById('usedPercentText').textContent = `${porcentajeUsado}%`;
        document.getElementById('freePercentText').textContent = `${porcentajeLibre}%`;
        document.getElementById('cantidadAccionesText').textContent = cantidadAcciones;
        document.getElementById('montoConsumidoText').textContent = formatSoles(consumido);

        actualizarPosicionPorcentajes(porcentajeLibreNumero, porcentajeUsadoNumero);

        document.getElementById('chartShell').setAttribute(
            'aria-label',
            `Saldo de IMF: ${saldoGrafico} soles, porcentaje usado ${porcentajeUsado}%, porcentaje libre ${porcentajeLibre}%`
        );

        const ctx = document.getElementById('imfChart');

        if (imfChartInstance) {
            imfChartInstance.destroy();
        }

        imfChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                labels: ['Saldo libre', 'Monto consumido'],
                datasets: [
                    {
                        data: [saldoGrafico, consumido],
                        backgroundColor: [colorSaldo, colorConsumido],
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
                        callbacks: {
                            label(context) {
                                const value = Number(context.raw || 0);
                                const percent = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                                return ` ${formatSoles(value, 2)} (${percent}%)`;
                            }
                        }
                    },
                    centerTextPlugin: {
                        valueText: formatSoles(saldoGrafico, 2),
                        labelText: 'Saldo de IMF',
                        valueColor: colorSaldo,
                        labelColor: colorConsumido
                    }
                }
            }
        });
    }

    llenarCuadroImf(imfJson);

    document.getElementById('clearSearch').addEventListener('click', function () {
      document.getElementById('customer-id').value = '';
      document.getElementById('customer-id').focus();
    });
  </script>
@endsection