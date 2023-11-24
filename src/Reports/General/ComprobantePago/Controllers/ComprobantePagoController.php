<?php

namespace AMovil\Reports\General\ComprobantePago\Controllers;

use AMovil\Reports\General\ComprobantePago\Services\ExportPlantillaComprobantePago;
use AMovil\Reports\General\ComprobantePago\Services\ExportReporteComprobantePago;
use Illuminate\Http\Request;

class ComprobantePagoController
{
    private $export;
    private $templateExporter;

    public function __construct(ExportReporteComprobantePago $export, ExportPlantillaComprobantePago $templateExporter)
    {
        $this->export = $export;
        $this->templateExporter = $templateExporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Comprobante Pago',
            'url' => url('comprobante-pago/export'),
            'templateUrl' => url('comprobante-pago/export-template'),
        ];
        return view("comprobante_pago/comprobante_pago", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke($request->file("comprobantes_file"))->data();
        return response($response['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }

    public function exportTemplate()
    {
        $response = $this->templateExporter->__invoke()->data();
        return response($response['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
