<?php

namespace AMovil\Reports\BloqueoControlReg\Controllers;

use AMovil\Reports\BloqueoControlReg\Services\DownloadBloqueoDocument;
use AMovil\Reports\BloqueoControlReg\Services\ImportBloqueoControlReg;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BloqueoControlRegController
{
    private $import;
    private $downloadReport;

    public function __construct(ImportBloqueoControlReg $import, DownloadBloqueoDocument $downloadReport)
    {
        $this->import = $import;
        $this->downloadReport = $downloadReport;
    }

    public function cargarView()
    {
        $config = [
            "title" => "Bloqueo Control Regulatorio",
            "url" => url("bloqueo-control-regulatorio/import"),
            "tiposDocumento" => [
                ["id" => 1, "label" => "SIBMED"],
                ["id" => 2, "label" => "DAPU"]
            ]
        ];
        return view("bloqueo_control_reg.cargar", compact("config"));
    }

    public function import(Request $request)
    {
        $this->import->__invoke(
            $request->input("tipo_documento_id"),
            $request->file("documento"),
            $request->input("imei"),
        );
        return response()->json(["file" => []]);
    }

    public function downloadDocument($id){
        $response = $this->downloadReport->__invoke($id)->data();
        return response($response["content"], 200, [
            "Content-Type" => "application/pdf",
            "Content-Disposition" => 'inline; filename="'. $response["filename"] .'"',
        ]);
    }
}
