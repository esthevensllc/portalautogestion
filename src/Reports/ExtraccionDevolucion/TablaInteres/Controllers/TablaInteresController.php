<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Controllers;

use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Services\GetFactorAcumuladoByFecha;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Services\RegisterTasaInteres;
use Illuminate\Http\Request;

class TablaInteresController
{
    private $registerTasaInteres;
    private $getFactorAcumulado;

    public function __construct(
        RegisterTasaInteres $registerTasaInteres,
        GetFactorAcumuladoByFecha $getFactorAcumulado,
    )
    {
        $this->registerTasaInteres = $registerTasaInteres;
        $this->getFactorAcumulado = $getFactorAcumulado;
    }

    public function view()
    {
        $config = [
            "title" => "ACTUALIZAR TABLA INTERES",
            "api" => asset("extraccion-devolucion/tabla-interes/api"),
        ];
        return view("extraccion_devolucion.tabla_interes", compact("config"));
    }

    public function create(Request $request)
    {
        $this->registerTasaInteres->__invoke(
            $request->input("fecha"),
            $request->input("tasa"),
            $request->input("factorDiario"),
            $request->input("factorAcumulado")
        );
        return response()->json([]);
    }
    
    public function api()
    {
        $maxFecha = $this->getFactorAcumulado->getMaxFechaInteres();
        $data = $this->getFactorAcumulado->__invoke()->data();

        return response()->json([
            "maxFecha" => $maxFecha,
            "data" => $data,
        ]);
    }
}
