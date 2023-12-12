<?php

namespace AMovil\Reports\General\BloqueoImei\Controllers;

use AMovil\Reports\General\BloqueoImei\Services\TripletaFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class TripletaController
{
    private $tripletaFinder;

    public function __construct(TripletaFinder $tripletaFinder)
    {
        $this->tripletaFinder = $tripletaFinder;
    }

    public function view()
    {
        $config = [
            'title' => 'Búsqueda Tripleta',
            'searchApi' => url('tripleta/search'),
            'searchApiByFile' => url('tripleta/search-by-file'),
            "fields" => [
                ["id" => "fecha_registro", "label" => "FECHA_REGISTRO"],
                ["id" => "imsi", "label" => "IMSI", "isFilter" => true],
                ["id" => "msisdn", "label" => "MSISDN", "isFilter" => true],
                ["id" => "imei", "label" => "IMEI", "isFilter" => true],
                // ["id" => "last_update", "label" => "LAST_UPDATE"],
            ],
        ];
        return view("imei_cdr.tripleta", compact("config"));
    }

    public function search(Request $request)
    {
        $filter = $request->get("filter");
        $value = str_replace(" ", "", $request->get("value"));
        $values = explode(",", $value);
        $data = $this->tripletaFinder->getBy($filter, $values);
        return response()->json(["data" => $data]);
    }

    public function searchByFile(Request $request)
    {
        $filter = $request->get("filter");
        $fileValues = new FileInput(
            $request->file("value")->getPathname(),
            $request->file("value")->getClientOriginalName()
        );
        $response = $this->tripletaFinder->getByFile($filter, $fileValues);
        $statusCode = $response->passes() ? 200 : 400;
        if($statusCode === 200){
            return response()->json($response->toArray(), $statusCode);
        }
        return response()->json($response->errors(), $statusCode);
    }
}
