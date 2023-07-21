<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
use DateTime;
use Exception;

class DeleteRegistrosMsisdn
{
    private $repo;
    public function __construct(LineasMTCOsiptelRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $tipo)
    {
        //$dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        $report = $this->repo->findReportMsisdnBy($ticket, $tipo);
        if($report === null){
            throw new Exception("El registro de msisdn no existe");
        }else{
            $this->repo->deleteMsisdnBy($ticket, $tipo);

        }
    }
}
