<?php

namespace AMovil\Reports\ListaExcepciones\Controllers;

use AMovil\Reports\ListaExcepciones\Services\ExportListaExcepciones;
use AMovil\Reports\ListaExcepciones\Services\ListaExcepcionesFinder;
use Illuminate\Http\Request;

class ListaExcepcionesController
{
    private $finder;
    private $exporter;

    public function __construct(ListaExcepcionesFinder $finder, ExportListaExcepciones $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Consulta Lista Excepciones',
            'url' => url('lista-excepciones/search'),
            'exportApi' => url('lista-excepciones/export'),
        ];
        return view("lista_excepciones.busqueda_excepciones", compact("config"));
    }

    public function search(Request $request)
    {
        $results = [];
        $value = $request->input("value");
        if($value === null){
            $results = $this->finder->findLast();
        }else{
            $results = $this->finder->findByImei($request->input("value"));
        }
        return response()->json(["data" => $results]);
    }

    public function export(Request $request)
    {
        $value = $request->input("value");
        $response = $this->exporter->__invoke($value)->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
