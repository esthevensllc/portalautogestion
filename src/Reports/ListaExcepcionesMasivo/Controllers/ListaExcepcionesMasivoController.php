<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Controllers;

use AMovil\Reports\ListaExcepcionesMasivo\Services\DownloadListaExcepcionesMasivo;
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
    private $downloader;

    public function __construct(ImportListaEir $importer, ListaExcepcionesMasivoFinder $finder, MasivoEirResponseExporter $exporter, DownloadListaExcepcionesMasivo $downloader)
    {
        $this->importer = $importer;
        $this->finder = $finder;
        $this->exporter = $exporter;
        $this->downloader = $downloader;
    }

    public function view()
    {
        $config = [
            'title' => 'Lista Excepciones Masivo',
            'url' => url('lista-excepciones-masivo/import'),
            'tipos_operacion' => $this->finder->getTiposOperacion(),
            "search" => url("lista-excepciones-masivo/search"),
            'downloadFile' => url("lista-excepciones-masivo/[filename]/download"),
            "downloadEir" => url("lista-excepciones-masivo/[id]/download-eir"),
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

    public function getUserData()
    {
        return response()->json([
            "data" => $this->finder->getUserData()
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

    public function downloadFile($filename)
    {   
        $response = $this->downloader->__invoke($filename)->data();

        if($response["type"] === "csv" || $response["type"] === "txt"){
            return response($response["content"], 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]);
        }

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
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
