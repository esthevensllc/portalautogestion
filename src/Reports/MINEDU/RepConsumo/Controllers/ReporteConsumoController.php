<?php

namespace AMovil\Reports\MINEDU\RepConsumo\Controllers;

use AMovil\Reports\MINEDU\RepConsumo\Services\ExportReporteConsumo;
use Illuminate\Http\Request;

class ReporteConsumoController
{
    private $service;

    public function __construct(ExportReporteConsumo $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "Reporte consumo y activación",
            "url" => asset("minedu/consumo-activacion/export"),
            "tipo_input" => [
                ["id" => "1", "label" => "EXCEL"],
                ["id" => "2", "label" => "NÚMERO DE CUENTA"]
            ],
            "reportes" => [
                ['id' => '1', 'label' => 'FreeZone'],
                ['id' => '2', 'label' => 'PeriodoFacturado'],
                ['id' => '3', 'label' => 'FreeZone Y PeriodoFacturado'],
            ]
        ];
        return view('minedu.rep_consumo', compact('config'));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->service->__invoke(
            $request->input("tipo_input"),
            $request->hasFile('excel') ? $request->file('excel')->getPathname() : null,
            $request->input("num_cuenta"),
            $request->input("fecha1"),
            $request->input("fecha2"),
            $request->input("tipo_reporte")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
