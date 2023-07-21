<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Controllers;

use AMovil\Reports\General\FacturacionAdelantada\Services\ExportFacturacionAdelantada;
use AMovil\Reports\General\FacturacionAdelantada\Services\ExportFacturacionAdelantadaConsolidado;
use Illuminate\Http\Request;

class FacturacionAdelantadaController
{
    private $export;
    private $exportConsolidado;

    public function __construct(ExportFacturacionAdelantada $export, ExportFacturacionAdelantadaConsolidado $exportConsolidado)
    {
        $this->export = $export;
        $this->exportConsolidado = $exportConsolidado;
    }

    public function view()
    {
        $config = [
            "title" => "FACTURACIÓN ADELANTADA",
            "url" => asset("rep-det-consumo/detalle-consumo/facturacion/export"),
        ];
        return view("rep_det_consumo.facturacion_adelantada", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->export->__invoke(
            $request->input("tipo_input"),
            $request->input("periodo"),
            $request->input("fecha_ini"),
            $request->input("fecha_fin"),
            $request->input("cuenta"),
            $request->input("num_factura"),
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }

    public function consolidadoView()
    {
        $config = [
            "title" => "FACTURACIÓN ADELANTADA CONSOLIDADA",
            "url" => asset("rep-det-consumo/facturacion-consolidado/export"),
        ];
        return view("rep_det_consumo.fac_adelantada_consolidado", compact("config"));
    }

    public function exportConsolidado(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->exportConsolidado->__invoke(
            $request->input("tipo_input"),
            $request->input("periodo"),
            $request->input("fecha_ini"),
            $request->input("fecha_fin"),
            $request->input("cuenta"),
            $request->input("num_factura"),
            $request->input("unidad_trafico_id"),
            $request->input("unidad_consumo_id"),
            $request->input("consumo_sin_cargo")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
