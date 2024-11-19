<?php

namespace AMovil\Reports\Retenciones\Controllers;

use AMovil\Reports\Retenciones\Services\ExportRetenciones;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Illuminate\Http\Request;
use DB;
use Exception;

class ListadoController
{
    private ExportRetenciones $service;

    public function __construct(ExportRetenciones $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "Retenciones",                      
        ];
        return view('retenciones.index', compact('config'));
    }

    public function store(Request $request)
    {
        $now = new DateTime();
        $minuto = (int) $now->format('i');
        if(45 < $minuto && $minuto <= 59){
            return response()->json(['message' => 'Solo es posible cargar en el rango de minutos de 00 hasta el minuto 45'], 400);
        }
        $result = $this->service->__invoke($request->file("excel_c"));
        return $result;
    }

    public function rutas(Request $request)
    {
        $now = new DateTime();
        $minuto = (int) $now->format('i');
        if(45 < $minuto && $minuto <= 59){
            return response()->json(['message' => 'Solo es posible cargar en el rango de minutos de 00 hasta el minuto 45'], 400);
        }
        $result = $this->service->__invokeRutas($request->file("excel_r"));
        return $result;
    }

    public function historicoCombinaciones(){
        $config = [
            'title' => 'Historial de combinaciones',
            'api_combinaciones' => url('retenciones/historico/combinaciones')
        ];
        return view('retenciones.historico-combinaciones', compact('config'));
    }

    public function historicoRutas(){
        $config = [
            'title' => 'Historial de rutas',
            'api_rutas' => url('retenciones/historico/rutas')
        ];
        return view('retenciones.historico-rutas', compact('config'));
    }

    public function getCombinaciones()
    {
        $data = DB::connection('mysql')->table("retenciones_red.cargas_eventos_red_post_final")
        ->select("dia_load",
                "operador",
                "departamento",
                "zic",
                "flag",
                "target",
                "decil",
                "callcenter",
                "final",
                "actividad_de_carga",
                "ruta")
        ->get();

        return response()->json(["data" => $data]);
    }

    public function getRutas()
    {
        $data = DB::connection('mysql')->table("retenciones_red.rutas_cc_postpago_final")
        ->select("dia_load",
                "callcenter",
                "operadorfinal",
                "fileName",
                "pathName")
        ->get();

        return response()->json(["data" => $data]);
    }
    
}
