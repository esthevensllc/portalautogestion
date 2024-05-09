<?php

namespace AMovil\Reports\ListaExcepcionesEliminar\Controllers;

use AMovil\Reports\ListaExcepcionesEliminar\Services\ExportListaExcepcionesEliminar;
use AMovil\Reports\ListaExcepcionesEliminar\Services\ListaExcepcionesEliminarFinder;
use Illuminate\Http\Request;

class ListaExcepcionesEliminarController
{
    private $finder;
    private $exporter;

    public function __construct(ListaExcepcionesEliminarFinder $finder, ExportListaExcepcionesEliminar $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Busqueda Lista Excepciones Eliminar',
            'url' => url('lista-excepciones-eliminar/search'),
            'exportApi' => url('lista-excepciones-eliminar/export'),
        ];
        return view("lista_excepciones.busqueda_excepciones_eliminar", compact("config"));
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
