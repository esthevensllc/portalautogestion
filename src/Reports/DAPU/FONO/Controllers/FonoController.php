<?php

namespace AMovil\Reports\DAPU\FONO\Controllers;

use AMovil\Reports\DAPU\FONO\Services\ExportDataDni;
use AMovil\Reports\DAPU\FONO\Services\GetDataDni;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class FonoController
{
    private $getDataDni;
    private $export;

    public function __construct(GetDataDni $getDataDni, ExportDataDni $export)
    {
        $this->getDataDni = $getDataDni;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CONSULTA IMSI",
            "url" => asset("dapu/consulta-fono/json"),
            "url_export" => asset("dapu/consulta-fono/export"),
            "fields" => [
                "msisdn" => ["label" => "MSISDN"],
                "imsi" => ["label" => "IMSI"],
                "tecnologia_red_chip" => ["label" => "TECNOLOGIA_RED_CHIP"],
                "tplname" => ["label" => "TPLNAME"],
                "provi_volte" => ["label" => "PROVI_VOLTE"],
                "simcard_3g_sinvolte" => ["label" => "SIMCARD_3G_SINVOLTE"],
                "simcard_3g_convolte" => ["label" => "SIMCARD_3G_CONVOLTE"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.consulta_dni_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = null;
        if((int) $request->input("tipo_input") === 1){
            $values = explode(",", str_replace(" ", "", $request->input('linea')));
            $response = $this->getDataDni->__invoke($values)->data();
        } else {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->getDataDni->fromFile($file)->data();
        }
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = null;
        if((int) $request->input("tipo_input") === 1){
            $values = explode(",", str_replace(" ", "", $request->input('linea')));
            $response = $this->export->__invoke(
                $values,
                $request->input('type'),
            )->data();
        } else {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->export->fromFile(
                $file,
                $request->input('type'),
            )->data();
        }

        $headers_type = [
            'csv' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        $headers = $headers_type[$response['type']];
        
        return response($response['content'], 200, $headers);
    }
}
