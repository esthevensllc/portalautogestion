<?php

namespace AMovil\Reports\General\ComprobantePago\Controllers;

use AMovil\Reports\General\ComprobantePago\Services\ExportReporteComprobantePago;
use Illuminate\Http\Request;

class ComprobantePagoController
{
    private $export;

    public function __construct(ExportReporteComprobantePago $export)
    {
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            'title' => 'Comprobante Pago',
            'url' => url('comprobante-pago/export'),
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
}
