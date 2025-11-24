<?php

namespace AMovil\Reports\AlertaMml\Controllers;

use AMovil\Reports\AlertaMml\Services\ExportAlertaMml;
use AMovil\Reports\AlertaMml\Services\AlertaMmlFinder;
use Illuminate\Http\Request;

class AlertaMmlController
{
    private $service;
    private $finder;

    public function __construct(ExportAlertaMml $service, AlertaMmlFinder $finder)
    {
        $this->service = $service;
        $this->finder = $finder;
    }

    public function view()
    {
        $config = [
            'title' => 'AlertaMml',
            'url' => url('AlertaMml/export'),
            "bases" => [
                ["id" => "1", "label" => "ESTADIO NACIONAL"],
                ["id" => "2", "label" => "ESTADIO MONUMENTAL"],
                ["id" => "3", "label" => "ESTADIO MONUMENTAL DE LA UNSA"],
                ["id" => "4", "label" => "ESTACION LOS JARDINES"],
                ["id" => "5", "label" => "ESTACION VILLA EL SALVADOR"],
                ["id" => "6", "label" => "GOLF LOS INCAS"],
                ["id" => "7", "label" => "GOLF SAN ISIDRO COUNTRY"],
                ["id" => "8", "label" => "GOLF LA PLANICIE"],
                ["id" => "9", "label" => "COUNTRY CLUB VILLA DE GOLF"],
                ["id" => "10", "label" => "PARQUE DE LA EXPOSICION - ANFITEATRO"],
                ["id" => "11", "label" => "GRAN TEATRO NACIONAL"],
                ["id" => "12,13,14,15", "label" => "MALL PLAZA AREQUIPA"],
                // ["id" => "13", "label" => "NAT CRISTO REY"],
                // ["id" => "14", "label" => "HIPODROMO AREQUIPA"],
                // ["id" => "15", "label" => "CAC PORONGOCHE AQP"],
            ],
            "maxDays" => ExportAlertaMml::MAX_DAYS,
            "whiteBlackList" => $this->finder->getBlackAndWhiteListSummary()
        ];
        return view("alertaMml.export_alerta_mml", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->service->__invoke(
            $request->input("base"),
            $request->input("mensaje")
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
            'title' => 'AlertaMml LOGS',
            'url' => url('AlertaMml/logs/search'),
            "bases" => [
                ["id" => "1", "label" => "ESTADIO NACIONAL"],
                ["id" => "2", "label" => "ESTADIO MONUMENTAL"],
                ["id" => "3", "label" => "ESTADIO MONUMENTAL DE LA UNSA"],
                ["id" => "4", "label" => "ESTACION LOS JARDINES"],
                ["id" => "5", "label" => "ESTACION VILLA EL SALVADOR"],
                ["id" => "6", "label" => "GOLF LOS INCAS"],   
                ["id" => "7", "label" => "GOLF SAN ISIDRO COUNTRY"],
                ["id" => "8", "label" => "GOLF LA PLANICIE"],
                ["id" => "9", "label" => "COUNTRY CLUB VILLA DE GOLF"], 
                ["id" => "10", "label" => "PARQUE DE LA EXPOSICION - ANFITEATRO"],
                ["id" => "11", "label" => "GRAN TEATRO NACIONAL"],            
            ]
        ];
        return view("alertaMml.alerta_mml_log", compact("config"));
    }

    public function logSearch()
    {
        $data = $this->finder->getLogs();
        return response()->json(["data" => $data]);
    }
}
