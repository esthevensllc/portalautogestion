<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Shared\Application\Response;

class GetReportInputs
{
    private $repo;

    public function __construct(ExtraccionDevFijaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(): Response
    {
        return Response::respData($this->repo->getInputs());
    }
}
