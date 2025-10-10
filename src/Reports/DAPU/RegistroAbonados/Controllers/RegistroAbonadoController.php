<?php

namespace AMovil\Reports\DAPU\RegistroAbonados\Controllers;

use AMovil\Reports\DAPU\RegistroAbonados\Services\RegistroAbonadoExporter;
use AMovil\Reports\DAPU\RegistroAbonados\Services\RegistroAbonadoFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class RegistroAbonadoController
{
    private $finder;
    private $export;

    public function __construct(RegistroAbonadoFinder $finder, RegistroAbonadoExporter $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CONSULTA REGISTRO ABONADOS",
            "url" => asset("dapu/registro-abonados/json"),
            "url_export" => asset("dapu/registro-abonados/export"),
            "fields" => [
                "msisdn" => ["label" => "MSISDN"],
                "nombres" => ["label" => "NOMBRES"],
                "ape_paterno" => ["label" => "APE_PATERNO"],
                "ape_materno" => ["label" => "APE_MATERNO"],
                // "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
                "nro_documento" => ["label" => "NRO_DOCUMENTO"],
                "razon_social" => ["label" => "RAZON_SOCIAL"],
                "imsi" => ["label" => "IMSI"],
                "imei" => ["label" => "IMEI"],
                "fecha_actualizacion" => ["label" => "FECHA_ACTUALIZACION"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.registro_abonados_form",
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
            $values = explode(",", str_replace(" ", "", $request->input('num_documento')));
        } else if ($tipoInput === 3){
            $file = new FileInput(
                $request->file("file_msisdn")->getPathname(),
                $request->file("file_msisdn")->getClientOriginalName()
            );
        } else if ($tipoInput === 4){
            $file = new FileInput(
                $request->file("file_num_documento")->getPathname(),
                $request->file("file_num_documento")->getClientOriginalName()
            );
        } else if ($tipoInput === 5){
            $values = explode(",", str_replace(" ", "", $request->input('imei')));
        } else if ($tipoInput === 6){
            $file = new FileInput(
                $request->file("file_imei")->getPathname(),
                $request->file("file_imei")->getClientOriginalName()
            );
        }

        return ["array" => $values, "file" => $file];
    }
}
