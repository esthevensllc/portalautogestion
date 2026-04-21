<?php

namespace AMovil\Reports\OSINFOR\Controllers;

use AMovil\Reports\OSINFOR\Services\ExportOSINFORReport;
use Illuminate\Http\Request;

class OSINFORController
{
    private ExportOSINFORReport $exporter;

    public function __construct(ExportOSINFORReport $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'OSINFOR',
            'url_export' => url('osinfor/export'),
            'codigo_cliente' => ExportOSINFORReport::CODIGO_CLIENTE,
            'available_range' => $this->exporter->getAvailableRange(),
        ];

        return view('osinfor.osinfor', compact('config'));
    }

    public function export(Request $request)
    {
        $response = $this->exporter->__invoke(
            (string) $request->input('anio'),
            (string) $request->input('semana')
        );

        if ($response->fails()) {
            return response()->json($response->errors(), 422);
        }

        $data = $response->data();

        return response($data['content'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $data['filename'] . '"',
        ]);
    }
}
