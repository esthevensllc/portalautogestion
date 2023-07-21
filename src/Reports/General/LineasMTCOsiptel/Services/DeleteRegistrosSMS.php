<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
use DateTime;
use Exception;

class DeleteRegistrosSMS
{
    private $repo;
    public function __construct(LineasMTCOsiptelRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $tipo, $fecha)
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        $report = $this->repo->findReportBy($ticket, $tipo, $dtFecha);
        if($report === null){
            throw new Exception("El registro de sms no existe");
        }

        if((int) $report->flag_extr === 0){
            $this->repo->deleteBy($ticket, $tipo, $dtFecha);
        }else{
            throw new Exception("La carta ya se confirmo y no se puede eliminar");
        }
    }
}
