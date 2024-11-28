<?php

namespace AMovil\Reports\DAPU\Sots\Services;

use AMovil\Reports\DAPU\Sots\Domain\DapuSotRepository;
use AMovil\Shared\Application\Response;

class DapuSotFinder
{
    private $repo;

    public function __construct(DapuSotRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($docCliente)
    {
        $data = $this->repo->getByDocCliente($docCliente);
        return Response::respData($data);
    }
}
