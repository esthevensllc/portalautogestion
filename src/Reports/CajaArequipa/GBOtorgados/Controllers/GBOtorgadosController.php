<?php

namespace AMovil\Reports\CajaArequipa\GBOtorgados\Controllers;

use AMovil\Reports\CajaArequipa\GBOtorgados\Services\ExportGBOtorgados;
use Illuminate\Http\Request;

class GBOtorgadosController
{
    private $service;
    public function __construct(ExportGBOtorgados $service)
    {
        $this->service = $service;
    }

    public function view(Request $request)
    {
        $config = [
            "title" => "GB Otorgados",
            "url" => asset("caja-arequipa/gb-otorgados/export")
        ];
        return view("caja_arequipa.gb_otorgados", compact("config"));
    }

    public function export(Request $request)
    {
        $reponse = $this->service->__invoke(
            $request->input("num_cuenta"),
            $request->input("periodo")
        )->data();

        return response($reponse["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $reponse["filename"] .'"'
        ]);
    }
}
