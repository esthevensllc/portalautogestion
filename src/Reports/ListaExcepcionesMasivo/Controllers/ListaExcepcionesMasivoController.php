<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Controllers;

use AMovil\Reports\ListaExcepcionesMasivo\Services\ImportListaEir;
use AMovil\Reports\ListaExcepcionesMasivo\Services\ListaExcepcionesMasivoFinder;
use AMovil\Reports\ListaExcepcionesMasivo\Services\MasivoEirResponseExporter;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ListaExcepcionesMasivoController
{
    private $importer;
    private $finder;
    private $exporter;

    public function __construct(ImportListaEir $importer, ListaExcepcionesMasivoFinder $finder, MasivoEirResponseExporter $exporter)
    {
        $this->importer = $importer;
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Lista Excepciones Masivo',
            'url' => url('lista-excepciones-masivo/import'),
            'tipos_operacion' => $this->finder->getTiposOperacion(),
        ];
        return view("lista_excepciones.lista_excepciones_masivo", compact("config"));
    }

    public function viewLog(){
        $config = [
            "title" => "Lista Excepciones Masivo Log",
            "url" => url("lista-excepciones-masivo/logs/search"),
            // "downloadUrl" => url("lista-excepciones-masivo/logs/[id]/download"),
            "downloadEir" => url("lista-excepciones-masivo/logs/[id]/download-eir"),
            "tiposOperacion" => $this->finder->getTiposOperacion(),
        ];
        return view("lista_excepciones.lista_excepciones_masivo_log", compact("config"));
    }

    public function get()
    {
        return response()->json([
            "data" => $this->finder->getByCriteria([])
        ]);
    }

    public function import(Request $request){
        $file = new FileInput(
            $request->file("archivo")->getPathname(),
            $request->file("archivo")->getClientOriginalName()
        );
        $response = $this->importer->__invoke(
            $request->input("tipo_operacion_id"),
            $file,
        );
        if(count($response->errors())>0){
            return response()->json($response->toArray(), 400);
        }
        return response()->json($response->toArray());
    }

    public function downloadEirResponse($id)
    {   
        $response = $this->exporter->__invoke($id);

        if($response->fails()){
            return abort(404, $response->errors()["message"]);
        }

        $response = $response->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
