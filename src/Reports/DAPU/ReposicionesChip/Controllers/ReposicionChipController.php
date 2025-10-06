<?php

namespace AMovil\Reports\DAPU\ReposicionesChip\Controllers;

use AMovil\Reports\DAPU\ReposicionesChip\Services\ExportReposicionChip;
use AMovil\Reports\DAPU\ReposicionesChip\Services\ReposicionChipFinder;
use Illuminate\Http\Request;

class ReposicionChipController
{
    private $finder;
    private $export;

    public function __construct(ReposicionChipFinder $finder, ExportReposicionChip $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "REPOSICIONES DE CHIP",
            "url" => asset("dapu/reposiciones-chip/json"),
            "url_export" => asset("dapu/reposiciones-chip/export"),
            "fields" => [
                "codigo_subclase" => ["label" => "CODIGO_SUBCLASE"],
                "tipo_linea" => ["label" => "TIPO_LINEA"],
                "desc_tipificacion" => ["label" => "DESC_TIPIFICACION"],
                "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
                "numero_telefono" => ["label" => "NUMERO_TELEFONO"],
                "tipo" => ["label" => "TIPO"],
                "punto_atencion" => ["label" => "PUNTO_ATENCION"],
                "x_iccid" => ["label" => "X_ICCID"],
                "codigo_tipificacion" => ["label" => "CODIGO_TIPIFICACION"],
                "usuario_reg" => ["label" => "USUARIO_REG"],
            ],
            "form_view" => "dapu.reposiciones_chip_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->finder->__invoke(
            $request->input('linea'),
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('linea'),
        )->data();

        $headers_type = [
            'csv' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        $headers = $headers_type[$response['type']];
        
        return response($response['content'], 200, $headers);
    }
}
