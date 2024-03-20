<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Services;

use AMovil\Reports\CargosFijosServiciosActivos\Domain\DetalleRepository;

class DeleteLogReporteTemp
{
    private $repo;
    private $storagePath = "/space/reportes/CARGOS_FIJOS_SERVICIOS_ACTIVOS";

    public function __construct(DetalleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        $reportes = $this->repo->getLogReporteTemp();
        foreach($reportes as $row){
            unlink("{$this->storagePath}/{$row->filename}");
        }
        $this->repo->deleteLogReporteTemp();
    }
}
