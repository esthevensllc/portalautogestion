<?php

namespace AMovil\Reports\GeoMarketing\Controllers;

use AMovil\Reports\GeoMarketing\Services\ExportGeoMarketing;
use Illuminate\Http\Request;

class GeoMarketingController
{
    private $service;

    public function __construct(ExportGeoMarketing $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            'title' => 'GEOMARKETING',
            'url' => url('geomarketing/export'),
            "bases" => [
                ["id" => "1", "label" => "ESTADIO NACIONAL"],
                ["id" => "2", "label" => "ESTADIO MONUMENTAL"],
            ]
        ];
        return view("geomarketing", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->service->__invoke(
            $request->input("nintex"),
            $request->input("base"),
            $request->input("fecha_inicio")." ".$request->input("hora_inicio"),
            $request->input("fecha_fin")." ".$request->input("hora_fin"),
            $request->input("white_list")
        )->data();
        return response($response["content"], 200, [
            'Content-Encoding' => 'UTF-8',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
