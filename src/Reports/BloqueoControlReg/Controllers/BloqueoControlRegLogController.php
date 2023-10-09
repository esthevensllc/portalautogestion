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
            "title" => "Bloqueo Control Regulatorio Log",
            "url" => url("bloqueo-control-regulatorio/logs/search"),
            "downloadUrl" => url("bloqueo-control-regulatorio/logs/[id]/download"),
        ];
        return view("bloqueo_control_reg.document_log", compact("config"));
    }

    public function getData()
    {
        $data = $this->getLogs->__invoke()->data();
        return response()->json(["data" => $data]);
    }
}
