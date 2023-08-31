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
                ["id" => "ESTADIO_NACIONAL", "label" => "ESTADIO NACIONAL"]
            ]
        ];
        return view("geomarketing", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->service->__invoke(
            $request->input("fecha_inicio")." ".$request->input("hora_inicio"),
            $request->input("fecha_fin")." ".$request->input("hora_fin")
        )->data();
        return response($response["content"], 200, [
            'Content-Encoding' => 'UTF-8',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
