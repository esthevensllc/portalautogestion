<?php

namespace AMovil\Reports\DAPU\LogBiometria\Services;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use AMovil\Shared\Application\Response;

class GetLogBiometria
{
    private $repo;

    public function __construct(LogBiometriaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $msisdn)
    {    
        $data = $this->repo->getByDniAndMsisdn($dni, $msisdn);
        return new Response([], $data);
    }
}
