<?php

namespace AMovil\Reports\DAPU\LogBiometria\Services;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class GetLogBiometria
{
    private $repo;

    public function __construct(LogBiometriaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $periodo)
    {    
        $dtPeriodo = DateTime::createFromFormat("Y-m-d H", $periodo);
        $data = $this->repo->getByDniAndPeriodo($dni, $dtPeriodo);
        return new Response([], $data);
    }
}
