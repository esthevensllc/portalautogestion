<?php

namespace AMovil\Reports\BloqueoControlReg\Controllers;

use AMovil\Reports\BloqueoControlReg\Services\GetBloqueoControlRegLog;

class BloqueoControlRegLogController
{
    private $getLogs;
    public function __construct(GetBloqueoControlRegLog $getLogs)
    {
        $this->getLogs = $getLogs;
    }

    public function view()
    {
        $config = [
            "title" => "Bloqueo / Desbloqueo Control Regulatorio Log",
            "url" => url("bloqueo-control-regulatorio/logs/search"),
            "downloadUrl" => url("bloqueo-control-regulatorio/logs/[id]/download"),
            "downloadEir" => url("bloqueo-control-regulatorio/logs/[id]/download-eir"),
            "tiposOperacion" => $this->getLogs->getTiposOperacion(),
            "tiposDocumento" => $this->getLogs->getTiposDocumento(),
        ];
        return view("bloqueo_control_reg.document_log", compact("config"));
    }

    public function getData()
    {
        $data = $this->getLogs->__invoke()->data();
        return response()->json(["data" => $data]);
    }
}
