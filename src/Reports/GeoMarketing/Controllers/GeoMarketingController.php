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
                ["id" => "7", "label" => "GOLF SAN ISIDRO COUNTRY"],
                ["id" => "8", "label" => "GOLF LA PLANICIE"],
                ["id" => "9", "label" => "COUNTRY CLUB VILLA DE GOLF"],
                ["id" => "10", "label" => "PARQUE DE LA EXPOSICION - ANFITEATRO"],
                ["id" => "11", "label" => "GRAN TEATRO NACIONAL"],
                ["id" => "12,13,14,15", "label" => "MALL PLAZA AREQUIPA"],
                ["id" => "16", "label" => "PLAZA NORTE"],
                ["id" => "17", "label" => "PLAYAS SUR"],
                ["id" => "18", "label" => "MALL SANTA ANITA"],
                ["id" => "19", "label" => "PLAZA SAN MIGUEL"],                
                ["id" => "21", "label" => "AEROPUERTO JORGE CHAVEZ"],
                ["id" => "22", "label" => "REAL PLAZA CENTRO CIVICO"],
                ["id" => "23", "label" => "REAL PLAZA SALAVERRY"],
                ["id" => "24", "label" => "JOCKEY PLAZA"],
                ["id" => "25", "label" => "Mall Aventura SJL"],
                ["id" => "26", "label" => "EMPORIO COMERCIAL DE GAMARRA"],
                ["id" => "27", "label" => "Real Plaza Huancayo"],
                ["id" => "28", "label" => "Open Plaza Piura"],
                ["id" => "29", "label" => "REAL PLAZA JULIACA"],
                ["id" => "30", "label" => "Mall Aventura Plaza Trujillo"]
                // ["id" => "13", "label" => "NAT CRISTO REY"],
                // ["id" => "14", "label" => "HIPODROMO AREQUIPA"],
                // ["id" => "15", "label" => "CAC PORONGOCHE AQP"],
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
                ["id" => "7", "label" => "GOLF SAN ISIDRO COUNTRY"],
                ["id" => "8", "label" => "GOLF LA PLANICIE"],
                ["id" => "9", "label" => "COUNTRY CLUB VILLA DE GOLF"], 
                ["id" => "10", "label" => "PARQUE DE LA EXPOSICION - ANFITEATRO"],
                ["id" => "11", "label" => "GRAN TEATRO NACIONAL"],            
                ["id" => "18", "label" => "MALL SANTA ANITA"],
                ["id" => "19", "label" => "PLAZA SAN MIGUEL"],                
                ["id" => "21", "label" => "AEROPUERTO JORGE CHAVEZ"],
                ["id" => "22", "label" => "REAL PLAZA CENTRO CIVICO"],
                ["id" => "23", "label" => "REAL PLAZA SALAVERRY"],
                ["id" => "24", "label" => "JOCKEY PLAZA"],
                ["id" => "25", "label" => "Mall Aventura SJL"],
                ["id" => "26", "label" => "EMPORIO COMERCIAL DE GAMARRA"],
                ["id" => "27", "label" => "Real Plaza Huancayo"],
                ["id" => "28", "label" => "Open Plaza Piura"],
                ["id" => "29", "label" => "REAL PLAZA JULIACA"],
                ["id" => "30", "label" => "Mall Aventura Plaza Trujillo"]
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
