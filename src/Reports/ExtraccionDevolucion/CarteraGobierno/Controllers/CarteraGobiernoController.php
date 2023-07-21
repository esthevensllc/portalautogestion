<?php

namespace AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Controllers;

use AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Services\CargarCarteraGobierno;
use Illuminate\Http\Request;

class CarteraGobiernoController
{
    private $service;

    public function __construct(CargarCarteraGobierno $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "ACTUALIZAR CARTERA GOBIERNO",
            "api" => asset("extraccion-devolucion/cartera-gobierno/import")
        ];
        return view("extraccion_devolucion.cartera_gobierno", compact("config"));
    }

    public function import(Request $request)
    {
        $this->service->__invoke($request->file("excel"));
        return response()->json([]);
    }
}
