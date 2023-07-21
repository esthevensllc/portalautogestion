<?php

namespace AMovil\Reports\General\RepMichaellCIANN\Services;

use AMovil\Reports\General\RepMichaellCIANN\Domain\RepMichaellCIANNRepository;
use AMovil\Shared\Application\Response;

class GetInforFoNumCuenta
{
    private $repo;

    public function __construct(RepMichaellCIANNRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numCuenta)
    {
        return new Response([], $this->repo->getInfoByNumCuenta($numCuenta));
    }
}
