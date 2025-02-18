<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Controllers;

use AMovil\Reports\ListaExcepcionesArt25\Services\ExportListaExcepcionesArt25;
use AMovil\Reports\ListaExcepcionesArt25\Services\ListaExcepcionesArt25Finder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ListaExcepcionesArt25Controller
{
    private $finder;
    private $exporter;

    public function __construct(ListaExcepcionesArt25Finder $finder, ExportListaExcepcionesArt25 $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Consulta Lista Excepciones Art 25',
            'url' => url('lista-excepciones-art25/search'),
            'exportApi' => url('lista-excepciones-art25/export'),
        ];
        return view("lista_excepciones.lista_excepciones_art25", compact("config"));
    }

    public function search(Request $request)
    {
        $results = [];
        $tipoInput = (int) $request->input("tipo_input");
        $value = $request->input("value");

        if(($tipoInput == 1 && $value === null) || ($tipoInput === 2 && $request->file("file_value") === null)){
            $results = $this->finder->findLast();
        }else if ($tipoInput == 1){
            $values = explode(",", str_replace(" ", "", $request->input('value')));
            $results = $this->finder->findByImei($values);
        } else if($tipoInput === 2) {
            $file = new FileInput(
                $request->file("file_value")->getPathname(),
                $request->file("file_value")->getClientOriginalName()
            );
            $results = $this->finder->fromFile($file);
        }


        return response()->json(["data" => $results]);
    }

    public function export(Request $request)
    {
        $tipoInput = (int) $request->input("tipo_input");
        $exportType = $request->input('type');
        $inputValue = $request->input('value');
        $response = [];

        if(($tipoInput == 1 && $inputValue === null) || ($tipoInput === 2 && $request->file("file_value") === null)){
            $response = $this->exporter->__invoke(null, $exportType)->data();
        }else if ($tipoInput == 1){
            $values = explode(",", str_replace(" ", "", $request->input('value')));
            $response = $this->exporter->__invoke($values, $exportType)->data();
        } else if($tipoInput === 2) {
            $file = new FileInput(
                $request->file("file_value")->getPathname(),
                $request->file("file_value")->getClientOriginalName()
            );
            $response = $this->exporter->fromFile($file, $exportType)->data();
        }

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
