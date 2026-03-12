<?php

namespace AMovil\Reports\FacturaDetallada\FacturacionDetallada\Controllers;

use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Domain\TipoInputFacturacionDetallada;
use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Services\ExportFacturacionDetallada;
use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Services\FacturacionDetalladaFinder;
use Illuminate\Http\Request;

class FacturacionDetalladaController
{
    private $finder;
    private $exporter;

    public function __construct(FacturacionDetalladaFinder $finder, ExportFacturacionDetallada $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "Factura Detallada",
            "url" => url("factura-detallada/export"),
            "tiposInput" => $this->finder->getTiposInput(),
        ];
        return view("factura-detallada.export_facturacion_detallada", compact("config"));
    }

    public function export(Request $request)
    {

        $value = $request->input("num_cuenta");

        $response = $this->exporter->__invoke(
            $value,
            $request->input("periodo")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
