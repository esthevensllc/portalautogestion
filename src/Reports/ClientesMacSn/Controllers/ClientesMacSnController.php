<?php

namespace AMovil\Reports\ClientesMacSn\Controllers;

use AMovil\Reports\ClientesMacSn\Services\ClientesMacSnFinder;
use AMovil\Reports\ClientesMacSn\Services\ExportClientesMacSn;
use AMovil\Reports\OltCmts\Services\OltCmtsFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ClientesMacSnController
{
    private $finder;
    private $exporter;

    public function __construct(ClientesMacSnFinder $finder, ExportClientesMacSn $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }
    
    public function view()
    {
        $config = [
            'title' => 'Clientes Mac SN',
            'url' => url('clientes-mac-sn/export'),
            "values" => [],
            "summary" => $this->finder->getSummary(),
        ];
        return view("clientes_mac_sn.search_clientes_mac_sn", compact("config"));
    }

    public function export(Request $request)
    {
        $file = null;
        $inputType = $request->get("tipo_input");
        if($inputType === "tab_excel"){
            $file = new FileInput(
                $request->file("excel")->getPathname(),
                $request->file("excel")->getClientOriginalName()
            );
        }
        $response = $this->exporter->__invoke(
            $inputType,
            $file,
            $request->get("mac"),
            $request->get("fecha"),
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
