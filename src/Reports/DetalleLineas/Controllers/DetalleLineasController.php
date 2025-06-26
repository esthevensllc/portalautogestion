<?php

namespace AMovil\Reports\DetalleLineas\Controllers;

use AMovil\Reports\DetalleLineas\Services\DetalleLineasExporter;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class DetalleLineasController
{
    private $exporter;

    public function __construct(DetalleLineasExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "DETALLE LINEAS",
            "url" => url("detalle-lineas/export"),
        ];
        return view("detalle_lineas.detalle_lineas", compact("config"));
    }

    public function export(Request $request){
        if ($request->file("excel")) {
            $file = new FileInput(
                $request->file("excel")->getPathname(),
                $request->file("excel")->getClientOriginalName()
            );
            $response = $this->exporter->__invoke($file);
            if ($response->fails()) {
                return response()->json($response->errors(), 400);
            }
            $response = $response->data();
            return response()->download($response['content'], $response['filename']);
        }
        return response()->json([
            "message" => "El archivo excel es obligatorio"
        ], 400);
    }
}
