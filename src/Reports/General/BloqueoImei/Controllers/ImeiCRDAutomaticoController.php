<?php

namespace AMovil\Reports\General\BloqueoImei\Controllers;

use AMovil\Reports\General\BloqueoImei\Services\DeleteAutomaticReportLog;
use AMovil\Reports\General\BloqueoImei\Services\DeleteBaseImeiAutomatico;
use AMovil\Reports\General\BloqueoImei\Services\ExportImeiCDRReport;
use AMovil\Reports\General\BloqueoImei\Services\GenerateImeiReport;
use AMovil\Reports\General\BloqueoImei\Services\GetAutomaticReportLog;
use AMovil\Reports\General\BloqueoImei\Services\GetImeiCDRReportLog;
use AMovil\Reports\General\BloqueoImei\Services\ImportBaseImeiAutomatico;
use Illuminate\Http\Request;

class ImeiCRDAutomaticoController
{
    private $getReportLog;
    private $generateImeiReport;
    private $deleteReportLog;
    private $importBaseImei;
    private $deleteBaseImei;
    private $getReport;
    private ExportImeiCDRReport $exportImeiReport;

    public function __construct(
        GetAutomaticReportLog $getReportLog,
        GenerateImeiReport $generateImeiReport,
        DeleteAutomaticReportLog $deleteReportLog,
        ImportBaseImeiAutomatico $importBaseImei,
        DeleteBaseImeiAutomatico $deleteBaseImei,
        GetImeiCDRReportLog $getReport,
        ExportImeiCDRReport $exportImeiReport
    ) {
        $this->getReportLog = $getReportLog;
        $this->generateImeiReport = $generateImeiReport;
        $this->deleteReportLog = $deleteReportLog;
        $this->importBaseImei = $importBaseImei;
        $this->deleteBaseImei = $deleteBaseImei;
        $this->getReport = $getReport;
        $this->exportImeiReport = $exportImeiReport;
    }

    public function view()
    {
        $config = [
            'title' => 'IMEIs CDR Automatico',
            'url' => url('imei-cdr-automatico/export'),
            'deleteUrl' => url('imei-cdr-automatico/reportlog/[id]'),
            'importBaseImeiUrl' => url('imei-cdr-automatico/reportlog/base-imei'),
            'deleteBaseImeiUrl' => url('imei-cdr-automatico/reportlog/base-imei/delete'),
        ];
        return view("imei_cdr.imei_cdr_automatico", compact("config"));
    }

    public function search()
    {
        $response = $this->getReportLog->__invoke([["delete_flag", 0]])->data();
        return response()->json([
            "data" => $response
        ]);
    }

    public function generate(Request $request)
    {
        $fecha_ini = $request->input("fecha_ini");
        $fecha_fin = $request->input("fecha_fin");

        $response = $this->generateImeiReport->__invoke($fecha_ini, $fecha_fin)->data();
        return response()->json($response);
    }

    public function deleteReportlog($id)
    {
        $this->deleteReportLog->__invoke($id);
        return response()->json([]);
    }

    public function importBaseImei(Request $request)
    {
        $this->importBaseImei->__invoke($request->file("import_base_imei"));
        return response()->json([]);
    }

    public function deleteBaseImei(Request $request)
    {
        $this->deleteBaseImei->__invoke($request->file("delete_base_imei"));
        return response()->json([]);
    }

    public function reportsView()
    {
        $config = [
            'title' => 'IMEIs CDR Reports',
            'downloadUrl' => url('imei-cdr-automatico/reports/[id]/download'),
        ];
        return view("imei_cdr.reports", compact("config"));
    }

    public function reportSearch()
    {
        $response = $this->getReport->__invoke([])->data();
        return response()->json([
            "data" => $response
        ]);
    }

    public function downloadReport($id)
    {
        $response = $this->exportImeiReport->__invoke($id)->data();
        return response($response['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
