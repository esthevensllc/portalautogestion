<?php

namespace AMovil\Reports\General\BloqueoImei\Controllers;

use AMovil\Reports\General\BloqueoImei\Services\DeleteReportLog;
use AMovil\Reports\General\BloqueoImei\Services\ExportBloqueoImei;
use AMovil\Reports\General\BloqueoImei\Services\GetReportLog;
use Illuminate\Http\Request;

class BloqueoImeiController
{
    private $export;
    private $getReportLog;
    private $deleteReportLog;

    public function __construct(ExportBloqueoImei $export, GetReportLog $getReportLog, DeleteReportLog $deleteReportLog)
    {
        $this->export = $export;
        $this->getReportLog = $getReportLog;
        $this->deleteReportLog = $deleteReportLog;
    }

    public function view()
    {
        $config = [
            'title' => 'Búsqueda IMEIs - CDR',
            'url' => url('bloqueo-imei/export'),
            'deleteUrl' => url('bloqueo-imei/reportlog/[id]'),
        ];
        return view("bloqueo_imei", compact("config"));
    }

    public function search()
    {
        $response = $this->getReportLog->__invoke([["delete_flag", 0]])->data();
        return response()->json([
            "data" => $response
        ]);
    }

    public function export(Request $request)
    {
        $file = $request->file("base_imei");
        $date_range = $request->input("date_range", "");
        $dates = explode(" - ", $date_range);

        $response = $this->export->__invoke($file, $dates[0], $dates[1])->data();

        return response($response['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }

    public function deleteReportlog($id)
    {
        $this->deleteReportLog->__invoke($id);
        return response()->json([]);
    }
}
