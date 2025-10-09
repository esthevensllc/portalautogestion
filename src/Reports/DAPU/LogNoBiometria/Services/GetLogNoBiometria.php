<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Services;

use AMovil\Reports\DAPU\LogNoBiometria\Domain\LogNoBiometriaRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class GetLogNoBiometria
{
    private $repo;

    public function __construct(LogNoBiometriaRepository $repo)
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
