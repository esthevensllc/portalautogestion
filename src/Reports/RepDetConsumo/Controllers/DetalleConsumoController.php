<?php

namespace AMovil\Reports\RepDetConsumo\Controllers;

use AMovil\Reports\RepDetConsumo\Services\ClienteValidator;
use AMovil\Reports\RepDetConsumo\Services\ExportDetalleConsumoConsolidado;
use AMovil\Reports\RepDetConsumo\Services\ExportDetalleConsumoDetallado;
use AMovil\Reports\RepDetConsumo\Services\RecordsValidator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DetalleConsumoController
{
    private $exportConsolidado;
    private $exportDetallado;
    private $validator;
    private $clienteValidator;

    public function __construct(
        ExportDetalleConsumoConsolidado $exportConsolidado,
        ExportDetalleConsumoDetallado $exportDetallado,
        RecordsValidator $validator,
        ClienteValidator $clienteValidator
    )
    {
        $this->exportConsolidado = $exportConsolidado;
        $this->exportDetallado = $exportDetallado;
        $this->validator = $validator;
        $this->clienteValidator = $clienteValidator;
    }

    public function detallado(){
        $data = [
            'title' => 'Detalle consumo / detallado (Tráfico Facturado)',
            'url_export' => asset('rep-det-consumo/detalle-consumo/detallado/export'),
            'url_validator' => asset('rep-det-consumo/detalle-consumo/detallado/validator'),
            'url_cliente_validator' => asset('rep-det-consumo/detalle-consumo/detallado/clientes'),
            'filename' => 'REPORTE_CONSUMO_DETALLADO.xlsx',
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function consolidado(){
        $data = [
            'title' => 'Detalle consumo / consolidado (Tráfico Facturado)',
            'url_export' => asset('rep-det-consumo/detalle-consumo/consolidado/export'),
            'url_validator' => asset('rep-det-consumo/detalle-consumo/consolidado/validator'),
            'url_cliente_validator' => asset('rep-det-consumo/detalle-consumo/consolidado/clientes'),
            'filename' => 'CONSOLIDADO_DE_CONSUMO.xlsx',
        ];
        return view('rep_det_consumo.det_consumo_consolidado', compact('data'));
    }

    public function exportDetallado(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        $export = $this->exportDetallado->__invoke(
            $request->get('cod_cliente'),
            $request->get('periodo'),
            $request->get('tipo_input'),
            $request->get('fecha1'),
            $request->get('fecha2'),
            $request->get('consumo_sin_cargo'),
        );
        // return $export;
        $headers_by_type = [
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="REPORTE_CONSUMO_DETALLADO.xlsx"'
            ],
            'zip' => [
                'Content-Type' => 'application/zip',
                'Content-Transfer-Encoding' => 'Binary',
                'Content-Disposition' => 'attachment;filename="REPORTE_CONSUMO_DETALLADO.zip"'
            ]
        ];
        return response($export['content'], 200, $headers_by_type[$export['type']]);
    }

    public function exportConsolidado(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        $export = $this->exportConsolidado->__invoke(
            $request->get('cod_cliente'),
            $request->get('periodo'),
            $request->get('unidad_trafico_id'),
            $request->get('unidad_consumo_id'),
            $request->get('consumo_sin_cargo'),
            $request->get('tipo_input'),
            $request->get('fecha1'),
            $request->get('fecha2'),
        );

        return response($export, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="CONSOLIDADO_DE_CONSUMO.xlsx"'
        ]);
    }

    public function validation(Request $request)
    {
        $response = $this->validator->__invoke(
            $request->input('cod_cliente'),
            $request->get('periodo'),
            $request->get('tipo_input'),
            $request->get('fecha1'),
            $request->get('fecha2'),
        );
        return response()->json($response);
    }

    public function clienteValidator(Request $request)
    {
        $data = $this->clienteValidator->__invoke($request->input('cliente'))->data();
        return response()->json($data);
    }

}
