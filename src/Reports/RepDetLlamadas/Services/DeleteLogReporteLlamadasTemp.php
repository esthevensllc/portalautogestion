<?php

namespace AMovil\Reports\RepDetLlamadas\Services;

use AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository;

class DeleteLogReporteLlamadasTemp
{
    private $repo;
    private $storagePath = "/space/www/html/portalautogestion_rel_llamadas";

    public function __construct(DetalleLlamadasRepository $repo)
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
