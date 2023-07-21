<?php

namespace AMovil\Reports\RepCursado\Controllers;

use AMovil\Reports\RepCursado\Services\ExportReporteCursado;
use AMovil\Reports\RepCursado\Services\GetPeriodosDisponibles;
use AMovil\Shared\Application\Response;
use Illuminate\Http\Request;

class ReporteCursadoController
{
    private $service;
    private $getPeriodosDisponibles;

    public function __construct(ExportReporteCursado $service, GetPeriodosDisponibles $getPeriodosDisponibles)
    {
        $this->service = $service;
        $this->getPeriodosDisponibles = $getPeriodosDisponibles;
    }

    public function view(){
        $data = [
            'title' => 'Reporte detalle datos cursados',
            'url_export' => asset('reporte-cursados/export')
        ];

        $tipo_input = [
            ['id' => 'num_cuenta', 'label' => 'Numero cuenta'],
            ['id' => 'cod_cliente', 'label' => 'Codigo cliente'],
            ['id' => 'num_documento', 'label' => 'Numero documento'],
            ['id' => 'lineas', 'label' => 'Lineas'],
        ];
        $periodosDisponibles = $this->getPeriodosDisponibles->__invoke();
        return view('rep_cursado.rep_cursado', compact('data', 'tipo_input', 'periodosDisponibles'));
    }

    public function export(Request $request)
    {
        $tipo_input_by_id = ['num_cuenta' => 'numero_cuenta', 'cod_cliente' => 'cod_cliente', 'num_documento' => 'num_documento'];

        $value = null;
        $tipo_input = $request->input('tipo_input');
        if(array_key_exists($tipo_input, $tipo_input_by_id)){
            $value = $request->input($tipo_input_by_id[$tipo_input]);
        }
        if($tipo_input === 'lineas'){
            $excel = $request->file('excel');
            $value = [
                'pathname' => $excel->getPathname(),
                'filename' => $excel->getClientOriginalName()
            ];
        }
        
        $response = $this->service->__invoke(
            $request->input('tipo_input'),
            $value,
            $request->input('fecha1'),
            $request->input('fecha2')
        )->data();

        return response($response['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
