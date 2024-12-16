<?php

namespace AMovil\Reports\Retenciones\Controllers;

use AMovil\Reports\Retenciones\Services\ExportRetenciones;
use AMovil\Shared\Exports\Domain\WriterType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use DB;

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
        $result = $this->service->__invoke($request->file("excel_c"));
        return $result;
    }

    public function rutas(Request $request)
    {
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

    public function viewCentrales()
    {
        $config = [
            "title" => "Centrales",
            'getCentrales' => url('retenciones/centrales/consulta'),
            'setCentrales' => url('retenciones/centrales/registro')
        ];
        return view('retenciones.centrales', compact('config'));
    }

    public function getCentrales(Request $request)
    {        
        $msisdn = $request->input("msisdn");
        $data = DB::connection('mysql')->table("XDRS.CENTRALES")
        ->select("numero","operador","tipo_llamada","dia","tipo_central")
        ->where("numero", "{$msisdn}")
        ->get();

        return response()->json(["data" => $data]);
    }

    public function setCentrales(Request $request)
    {        
        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'msisdn' => 'required|numeric|digits:9',
            'operador' => 'required|in:BITEL,ENTEL,MOVISTAR',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        // Registrar los datos en la tabla
        try {
            \DB::connection('mysql')->table("XDRS.CENTRALES")->insert([
                'numero' => $request->msisdn,
                'operador' => $request->operador,
                'tipo_llamada' => 'ENTRANTE', // Valor predeterminado
                'fecha' => now(),             // Fecha actual
                'tipo_central' => 'RETENCIONES', // Valor predeterminado
                'dia' => Carbon::now()->toDateString(),               // Fecha actual
                'validate' => null,           // Valor nulo
            ]);

            return response()->json(['message' => 'Número registrado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar los datos', 'error' => $e->getMessage()], 500);
        }
    }
    
}
