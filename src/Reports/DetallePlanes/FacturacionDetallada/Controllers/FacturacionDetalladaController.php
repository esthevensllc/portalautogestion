<?php

namespace AMovil\Reports\DetallePlanes\FacturacionDetallada\Controllers;

use AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain\TipoInputFacturacionDetallada;
use AMovil\Reports\DetallePlanes\FacturacionDetallada\Services\ExportFacturacionDetallada;
use AMovil\Reports\DetallePlanes\FacturacionDetallada\Services\FacturacionDetalladaFinder;
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
            "title" => "Facturación Detallada",
            "url" => url("detalle-planes/facturacion-detallada/export"),
            "tiposInput" => $this->finder->getTiposInput(),
        ];
        return view("detalle_planes.export_facturacion_detallada", compact("config"));
    }

    public function export(Request $request)
    {
        $value = null;
        $tipoInputId = (int) $request->input("tipo_input_id");
        switch ($tipoInputId) {
            case TipoInputFacturacionDetallada::NUMERO_CUENTA:
                $value = $request->input("num_cuenta");
                break;
            case TipoInputFacturacionDetallada::EXCEL:
                break;
            default:
                break;
        }

        $response = $this->exporter->__invoke(
            $request->input("tipo_input_id"),
            $request->file("excel"),
            $value,
            $request->input("periodo")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
