<?php

namespace AMovil\Reports\ClientesPlanos\Controllers;

use AMovil\Reports\ClientesPlanos\Services\ExportClientesPlanosReport;
use AMovil\Reports\ClientesPlanos\Services\ClientesPlanosFinder;
use DateTime;
use Illuminate\Http\Request;

class ClientesPlanosController
{
    private $finder;
    private $exporter;

    public function __construct(ClientesPlanosFinder $finder, ExportClientesPlanosReport $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }
    
    public function view()
    {
        $config = [
            'title' => 'CLIENTES POR PLANOS',
            'url' => url('clientes-planos/export'),
            'findValuesApi' => url('clientes-planos/find-values'),
            "types" => $this->finder->getReportTypes(),
            "values" => [],
            "oltSummary" => $this->finder->getOltListSummary(),
        ];
        return view("clientes-planos.clientes-planos", compact("config"));
    }

    public function findValues(Request $request)
    {
        $fecha = DateTime::createFromFormat("Y-m-d", $request->get("fecha"));
        $data = [];
        switch ($request->get("type_id")) {
            case '1':
                $data = $this->finder->getOltsValues($fecha);
                break;
            case '2':
                $data = $this->finder->getCmtsValues($fecha);
                break;
            default:
                break;
        }
        return response()->json([
            "data" => $data
        ]);
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '3600');
        $response = $this->exporter->__invoke(
            $request->input("type_id"),
            $request->input("fecha"),
            $request->input("planos", [])
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
