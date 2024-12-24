<?php

namespace AMovil\Reports\BaseTipificaciones\Controllers;

use AMovil\Reports\BaseTipificaciones\Services\ImportListaBaseTipificaciones;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class BaseTipificacionesController
{
    private $importer;

    public function __construct(ImportListaBaseTipificaciones $importer)
    {
        $this->importer = $importer;
    }

    public function view()
    {
        $config = [
            'title' => 'Base Tipificaciones',
            'url' => url('base-tipificaciones/import')
        ];
        return view("base_tipificaciones.base_tipificaciones_carga", compact("config"));
    }

    public function import(Request $request){
        $file = new FileInput(
            $request->file("archivo")->getPathname(),
            $request->file("archivo")->getClientOriginalName()
        );
        $response = $this->importer->__invoke(
            $file
        );
        if(count($response->errors())>0){
            return response()->json($response->toArray(), 400);
        }

        return response()->json($response->toArray());
    }
}
