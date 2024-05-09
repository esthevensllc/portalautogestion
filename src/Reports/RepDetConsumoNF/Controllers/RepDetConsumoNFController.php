<?php

namespace AMovil\Reports\RepDetConsumoNF\Controllers;

use AMovil\Reports\RepDetConsumoNF\Services\ExportConsumoDetalladoNF;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class RepDetConsumoNFController
{
    private $exporter;
    
    public function __construct(ExportConsumoDetalladoNF $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Detalle Consumo / Detallado (Tráfico No Facturado)',
            'url' => url('rep-det-consumo/nf/detallado/export'),
        ];
        return view("rep_det_consumo.det_consumo_nf", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $excel = $request->file("excel");
        if($excel !== null){
            $excel = new FileInput(
                $excel->getPathname(),
                $excel->getClientOriginalName()
            );
        }
        $response = $this->exporter->__invoke(
            $request->input("tipo_input"),
            $excel,
            $request->input("num_cuenta"),
            $request->input("fecha_ini"),
            $request->input("fecha_fin")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
