<?php

namespace AMovil\Reports\BloqueoControlReg\Controllers;

use AMovil\Reports\BloqueoControlReg\Services\DownloadBloqueoDocument;
use AMovil\Reports\BloqueoControlReg\Services\GetBloqueoControlRegLog;
use AMovil\Reports\BloqueoControlReg\Services\ImportBloqueoControlReg;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BloqueoControlRegController
{
    private $import;
    private $downloadReport;
    private $getLog;

    public function __construct(ImportBloqueoControlReg $import, DownloadBloqueoDocument $downloadReport, GetBloqueoControlRegLog $getLog)
    {
        $this->import = $import;
        $this->downloadReport = $downloadReport;
        $this->getLog = $getLog;
    }

    public function cargarView()
    {
        $config = [
            "title" => "Bloqueo / Desbloqueo Control Regulatorio",
            "url" => url("bloqueo-control-regulatorio/import"),
            "tiposOperacion" => $this->getLog->getTiposOperacion(),
            "tiposDocumento" => $this->getLog->getTiposDocumento(),
        ];
        return view("bloqueo_control_reg.cargar", compact("config"));
    }

    public function import(Request $request)
    {
        $this->import->__invoke(
            $request->input("tipo_operacion_id"),
            $request->input("tipo_documento_id"),
            $request->file("documento"),
            $request->input("imei"),
        );
        return response()->json(["file" => []]);
    }

    public function downloadDocument($id){
        $response = $this->downloadReport->__invoke($id)->data();
        $headersByType = [
            "pdf" => [
                "Content-Type" => "application/pdf",
                "Content-Disposition" => 'inline; filename="'. $response["filename"] .'"',
            ],
            "xlsx" => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]
        ];
        return response($response["content"], 200, $headersByType[$response["type"]]);
    }
}
