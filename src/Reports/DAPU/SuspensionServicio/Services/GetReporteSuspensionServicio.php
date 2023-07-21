<?php

namespace AMovil\Reports\DAPU\SuspensionServicio\Services;

use AMovil\Reports\DAPU\SuspensionServicio\Domain\SuspensionServicioRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class GetReporteSuspensionServicio
{
    private $repo;
    public function __construct(SuspensionServicioRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($msisdn,$dni, $fecha1, $fecha2): Response
    {
        $dt1 = DateTime::createFromFormat("Y-m-d", $fecha1);
        $dt2 = DateTime::createFromFormat("Y-m-d", $fecha2);
        $data = $this->repo->getByMsisdn($msisdn,$dni, $dt1, $dt2);
        return new Response([], $data);
    }
}
