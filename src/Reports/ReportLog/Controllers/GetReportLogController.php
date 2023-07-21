<?php

namespace AMovil\Reports\ReportLog\Controllers;

use AMovil\Reports\ReportLog\Services\GetReporteLog;
use Illuminate\Http\Request;

class GetReportLogController
{
    private $service;
    public function __construct(GetReporteLog $service)
    {
        $this->service = $service;
    }

    public function view(){
        return view('logs.reporte_log');
    }

    public function search(Request $request)
    {
        $dt_criteria = $request->all();
        $i = $dt_criteria['order'][0]['column'];
        $sortBy = [$dt_criteria['columns'][$i]['data'], $dt_criteria['order'][0]['dir']];
        $data = $this->service->__invoke(
            $request->input('filter')??[],
            $sortBy,
            $dt_criteria['start'],
            $dt_criteria['length']
        )->data();
        $data['request'] = $request->all();
        return response()->json($data);
    }
}
