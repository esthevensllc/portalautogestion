<?php

namespace AMovil\Reports\RepFiscalia\Controllers;

use AMovil\Reports\RepFiscalia\Services\ExportReporteFiscal;
use AMovil\Reports\RepFiscalia\Services\ReporteFiscalValidator;
use Illuminate\Http\Request;

class ReporteFiscalController
{
    private $exportReporteFiscal;
    private $validator;

    public function __construct(ExportReporteFiscal $exportReporteFiscal, ReporteFiscalValidator $validator)
    {
        $this->exportReporteFiscal = $exportReporteFiscal;
        $this->validator = $validator;
    }

    public function repFiscal()
    {
        $data = [
            'url_export' => asset('rep-fiscalia/rep-fiscal/export'),
            'url_validator' => asset('rep-fiscalia/rep-fiscal/validator'),
            'filename' => 'REPORTE_FISCAL.xlsx',
        ];
        return view('rep_fiscalia.reporte_fiscal', compact('data'));
    }

    public function exportRepFiscal(Request $request)
    {
        $export = $this->exportReporteFiscal->__invoke(
            $request->input('msisdn'),
            $request->input('periodo1'),
            $request->input('periodo2')
        );
        return response($export, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="reporte.xlsx"'
        ]);
    }

    public function validator(Request $request)
    {
        $response = $this->validator->__invoke(
            $request->input('msisdn'),
            $request->input('periodo1'),
            $request->input('periodo2')
        );
        return response()->json($response);
    }
}
