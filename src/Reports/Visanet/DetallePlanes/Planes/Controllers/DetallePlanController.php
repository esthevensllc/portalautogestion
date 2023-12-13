<?php

namespace AMovil\Reports\DetallePlanes\Planes\Controllers;

use AMovil\Reports\DetallePlanes\Planes\Domain\TipoInputDetallePlan;
use AMovil\Reports\DetallePlanes\Planes\Services\DetallePlanesFinder;
use AMovil\Reports\DetallePlanes\Planes\Services\ExportDetallePlanes;
use Illuminate\Http\Request;

class DetallePlanController
{
    private $finder;
    private $exporter;

    public function __construct(DetallePlanesFinder $finder, ExportDetallePlanes $exporter)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "Detalle de Planes",
            "url" => url("detalle-planes/export"),
            "tiposInput" => $this->finder->getTiposInput(),
        ];
        return view("detalle_planes.export_detalle_planes", compact("config"));
    }

    public function export(Request $request)
    {
        $value = null;
        $tipoInputId = (int) $request->input("tipo_input_id");
        switch ($tipoInputId) {
            case TipoInputDetallePlan::NUMERO_DOCUMENTO:
                $value = $request->input("num_documento");
                break;
            case TipoInputDetallePlan::NUMERO_CUENTA:
                $value = $request->input("num_cuenta");
                break;
            case TipoInputDetallePlan::LINEAS:
                $value = $request->input("lineas");
                break;
            case TipoInputDetallePlan::EXCEL:
                break;
            default:
                break;
        }

        $response = $this->exporter->__invoke(
            $request->input("tipo_input_id"),
            $request->file("excel"),
            $value,
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
