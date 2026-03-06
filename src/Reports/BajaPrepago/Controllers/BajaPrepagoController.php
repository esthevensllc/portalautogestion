<?php

namespace AMovil\Reports\BajaPrepago\Controllers;

use AMovil\Reports\BajaPrepago\Services\BajaPrepagoFinder;
use AMovil\Reports\BajaPrepago\Services\ImportBajasPrepago;
use AMovil\Shared\Application\FileInput;
use DateTime;
use Illuminate\Http\Request;

class BajaPrepagoController
{
    private $service;
    private $finder;

    public function __construct(ImportBajasPrepago $service, BajaPrepagoFinder $finder)
    {
        $this->service = $service;
        $this->finder = $finder;
    }

    public function view()
    {
        $config = [
            "title" => "BAJAS PREPAGO",
            "preImportApi" => asset("bajas-prepago/pre-import"),
            "importApi" => asset("bajas-prepago/import"),
            "downloadApi" => asset("bajas-prepago/[id]/[estado]/download"),
        ];
        return view("bajas_prepago.bajas_prepago", compact("config"));
    }

    public function search(Request $request)
    {
        return response()->json([
            "data" => $this->finder->getLogs()
        ]);
    }

    public function preImport(Request $request)
    {
        $file = new FileInput(
            $request->file("archivo")->getPathname(),
            $request->file("archivo")->getClientOriginalName()
        );
        $response = $this->service->__invoke(
            $request->input("clase"),
            $request->input("sub_clase"),
            $request->input("notas"),
            $file,
            $request->ip(),
        );
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        return response()->json($response->data());
    }

    public function importFinal(Request $request)
    {
        $response = $this->service->insertFinal(
            $request->post("baseId"),
            $request->ip(),
        );
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        return response()->json([]);
    }

    public function export($id, $estado)
    {
        $response = $this->finder->getBasePreimportByBaseIdAndEstado($id, $estado);
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        $dataStream = $response->data();
        $strNow = (new DateTime())->format("Ymd");
        $filename = "EXPORT_{$estado}_{$strNow}.txt";
        return response()->stream(function() use ($dataStream, $filename) {
            foreach($dataStream as $line){
                echo $line.PHP_EOL;
            }
        }, 200, [
            "Content-Type" => "text/plain: charset=UTF-8",
            'Content-Disposition' => 'attachment;filename="'.$filename.'"'
        ]);
    }
}
