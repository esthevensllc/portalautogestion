<?php

namespace AMovil\Reports\DAPU\UltimoTrafico\Controllers;

use AMovil\Reports\DAPU\UltimoTrafico\Services\UltimoTraficoExporter;
use AMovil\Reports\DAPU\UltimoTrafico\Services\UltimoTraficoFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class UltimoTraficoController
{
    private $finder;
    private $export;

    public function __construct(UltimoTraficoFinder $finder, UltimoTraficoExporter $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "ULTIMO TRAFICO",
            "url" => asset("dapu/ultimo-trafico/json"),
            "url_export" => asset("dapu/ultimo-trafico/export"),
            "fields" => [
                "msisdn" => ["label" => "MSISDN"],
                "imsi" => ["label" => "IMSI"],
                "imei" => ["label" => "IMEI"],
                "fec_ultimo_trafico" => ["label" => "FEC_ULTIMO_TRAFICO"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.ultimo_trafico_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $input = $this->getInputFromRequest($request);
        $response = null;
        if($input["array"] !== null){
            $response = $this->finder->__invoke($request->input('tipo_input'), $input["array"])->data();
        } else {
            $response = $this->finder->fromFile($request->input('tipo_input'), $input["file"])->data();
        }
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $input = $this->getInputFromRequest($request);
        $response = null;
        if($input["array"] !== null){
            $response = $this->export->__invoke(
                $request->input('tipo_input'),
                $input["array"],
                $request->input('type'),
            )->data();
        } else {
            $response = $this->export->fromFile(
                $request->input('tipo_input'),
                $input["file"],
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

    private function getInputFromRequest(Request $request){
        $tipoInput = (int) $request->input('tipo_input');
        $values = null;
        $file = null;
        if($tipoInput === 1){
            $values = explode(",", str_replace(" ", "", $request->input('msisdn')));
        } else if ($tipoInput === 2){
            $values = explode(",", str_replace(" ", "", $request->input('imei')));
        } else if ($tipoInput === 3){
            $file = new FileInput(
                $request->file("file_msisdn")->getPathname(),
                $request->file("file_msisdn")->getClientOriginalName()
            );
        } else if ($tipoInput === 4){
            $file = new FileInput(
                $request->file("file_imei")->getPathname(),
                $request->file("file_imei")->getClientOriginalName()
            );
        }
        return ["array" => $values, "file" => $file];
    }
}
