<?php

namespace AMovil\Reports\Retenciones\Controllers;

use AMovil\Reports\Retenciones\Services\ExportRetenciones;
use AMovil\Shared\Exports\Domain\WriterType;
use Illuminate\Http\Request;
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
            "pks" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("pk")
            ->distinct()
            ->whereNotNull("pk")
            ->get(),
            "operadores" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("operador")
            ->distinct()
            ->whereNotNull("operador")
            ->get(),
            "flag_dptos" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("flag_dpto")
            ->distinct()
            ->whereNotNull("flag_dpto")
            ->get(),
            "flags" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("flag")
            ->distinct()
            ->whereNotNull("flag")
            ->get(),
            "targets" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("target")
            ->distinct()
            ->whereNotNull("target")
            ->get(),
            "deciles" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("decil")
            ->distinct()
            ->whereNotNull("decil")
            ->get(),
            "callcenters" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("callcenter")
            ->distinct()
            ->whereNotNull("callcenter")
            ->get(),
            "finales" => DB::connection('mysql')->table("retenciones_red.filtros_postpago")
            ->select("final")
            ->distinct()
            ->whereNotNull("final")
            ->get(),
            "callcenters_cc" => DB::connection('mysql')->table("retenciones_red.rutas_cc_postpago")
            ->select("callcenter")
            ->distinct()
            ->whereNotNull("callcenter")
            ->get(),
            "operadorfinales" => DB::connection('mysql')->table("retenciones_red.rutas_cc_postpago")
            ->select("operadorfinal")
            ->distinct()
            ->whereNotNull("operadorfinal")
            ->get(),
            "fileNames" => DB::connection('mysql')->table("retenciones_red.rutas_cc_postpago")
            ->select("fileName")
            ->distinct()
            ->whereNotNull("fileName")
            ->get(),
            "pathNames" => DB::connection('mysql')->table("retenciones_red.rutas_cc_postpago")
            ->select("pathName")
            ->distinct()
            ->whereNotNull("pathName")
            ->get(),            
        ];
        return view('retenciones.index', compact('config'));
    }

    public function store(Request $request)
    {
        DB::connection('mysql')->beginTransaction();

        try {     
            $pk = $request->input('pk');
            $operador = $request->input('operador');
            $flag_dpto = $request->input('flag_dpto');
            $flag = $request->input('flag');
            $target = $request->input('target');
            $decil = $request->input('decil');
            $callcenter = $request->input('callcenter');
            $final = $request->input('final');

            // Inserción en la primera tabla
            DB::connection('mysql')->table('retenciones_red.filtros_postpago_final')->insert([
                'dia_load' => now(),
                'key_hash' => DB::connection('mysql')->raw("SHA2(CONCAT('$pk','$operador','$flag_dpto',if('$flag' is null,'NULL','$flag'),'$target','$decil','$callcenter','$final'), 256)"),
                'pk' => $pk,
                'operador' => $operador,
                'flag_dpto' => $flag_dpto,
                'flag' => $flag,
                'target' => $target,
                'decil' => $decil,
                'callcenter' => $callcenter,
                'final' => $final
            ]);

            $callcenter_cc = $request->input('callcenter_cc');
            $operadorfinal = $request->input('operadorfinal');
            $fileName = $request->input('fileName');
            $pathName = $request->input('pathName');

            // Inserción en la segunda tabla
            DB::connection('mysql')->table('retenciones_red.rutas_cc_postpago_final')->insert([
                'dia_load' => now(),
                'key_hash' => DB::connection('mysql')->raw("SHA2(CONCAT('$callcenter_cc','$operadorfinal','$fileName','$pathName'), 256)"),
                'callcenter' => $callcenter_cc,
                'operadorfinal' => $operadorfinal,
                'fileName' => $fileName,
                'pathName' => $pathName
            ]);

            DB::connection('mysql')->commit(); // Confirmar transacción

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback(); // Revertir transacción en caso de error

            return response()->json(['error' => 'Error al guardar los datos.'], 500);
        }
    }

    public function historico(){
        $config = [
            'title' => 'Historial de combinaciones y rutas',
            'api_combinaciones' => url('retenciones/historico/combinaciones'),
            'api_rutas' => url('retenciones/historico/rutas')
        ];
        return view('retenciones.historico', compact('config'));
    }

    public function combinaciones()
    {
        $data = DB::connection('mysql')->table("retenciones_red.filtros_postpago_final")
        ->select("dia_load",
                "pk",
                "operador",
                "flag_dpto",
                "flag",
                "target",
                "decil",
                "callcenter",
                "final")
        ->get();

        return response()->json(["data" => $data]);
    }

    public function rutas()
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
