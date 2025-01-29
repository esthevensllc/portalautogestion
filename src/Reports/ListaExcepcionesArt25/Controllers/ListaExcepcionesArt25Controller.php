<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Controllers;

use AMovil\Reports\ListaExcepcionesArt25\Services\ExportListaExcepcionesArt25;
use AMovil\Reports\ListaExcepcionesArt25\Services\ListaExcepcionesArt25Finder;
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
        $value = $request->input("value");
        if($value === null){
            $results = $this->finder->findLast();
        }else{
            $inputValue = $request->input('value');
            $values = $inputValue !== null ? explode(",", str_replace(" ", "", $inputValue)) : null;
            $results = $this->finder->findByImei($values);
        }
        return response()->json(["data" => $results]);
    }

    public function export(Request $request)
    {
        $inputValue = $request->input('value');
        $values = $inputValue !== null ? explode(",", str_replace(" ", "", $inputValue)) : null;
        $response = $this->exporter->__invoke($values)->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
