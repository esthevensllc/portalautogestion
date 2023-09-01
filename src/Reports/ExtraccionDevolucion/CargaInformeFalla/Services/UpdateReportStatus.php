<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;

class UpdateReportStatus
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($status, $id)
    {
        switch ($status) {
            case 'enEjecucionPre':
                $this->repo->enEjecucionPre($id);
                break;
            case 'enEsperaPre':
                $this->repo->enEsperaPre($id);
                break;
            case 'enEjecucionPost':
                $this->repo->enEjecucionPost($id);
                break;
            case 'enEsperaPost':
                $this->repo->enEsperaPost($id);
                break;
            default:
                break;
        }
    }
}
