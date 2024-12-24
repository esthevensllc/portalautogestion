<?php

namespace AMovil\Reports\GeoMarketing\Controllers;

use AMovil\Reports\GeoMarketing\Services\ExportGeoMarketing;
use AMovil\Reports\GeoMarketing\Services\GeoMarketingFinder;
use Illuminate\Http\Request;

class GeoMarketingController
{
    private $service;
    private $finder;

    public function __construct(ExportGeoMarketing $service, GeoMarketingFinder $finder)
    {
        $this->service = $service;
        $this->finder = $finder;
    }

    public function view()
    {
        $config = [
            'title' => 'GEOMARKETING',
            'url' => url('geomarketing/export'),
            "bases" => [
                ["id" => "1", "label" => "ESTADIO NACIONAL"],
                ["id" => "2", "label" => "ESTADIO MONUMENTAL"],
                ["id" => "3", "label" => "ESTADIO MONUMENTAL DE LA UNSA"],
                ["id" => "4", "label" => "ESTACION LOS JARDINES"],
                ["id" => "5", "label" => "ESTACION VILLA EL SALVADOR"],
                ["id" => "6", "label" => "GOLF LOS INCAS"],
            ],
            "maxDays" => ExportGeoMarketing::MAX_DAYS,
            "whiteBlackList" => $this->finder->getBlackAndWhiteListSummary()
        ];
        return view("geomarketing.export_geomarketing", compact("config"));
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

    public function logView()
    {
        $config = [
            'title' => 'GEOMARKETING LOGS',
            'url' => url('geomarketing/logs/search'),
            "bases" => [
                ["id" => "1", "label" => "ESTADIO NACIONAL"],
                ["id" => "2", "label" => "ESTADIO MONUMENTAL"],
                ["id" => "3", "label" => "ESTADIO MONUMENTAL DE LA UNSA"],
                ["id" => "4", "label" => "ESTACION LOS JARDINES"],
                ["id" => "5", "label" => "ESTACION VILLA EL SALVADOR"],
                ["id" => "6", "label" => "GOLF LOS INCAS"],                
            ]
        ];
        return view("geomarketing.geomarketing_log", compact("config"));
    }

    public function logSearch()
    {
        $data = $this->finder->getLogs();
        return response()->json(["data" => $data]);
    }
}
