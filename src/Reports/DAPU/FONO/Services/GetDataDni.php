<?php

namespace AMovil\Reports\DAPU\FONO\Services;

use AMovil\Reports\DAPU\FONO\Domain\FonoRepository;
use AMovil\Shared\Application\Response;

class GetDataDni
{
    private $repo;
    public function __construct(FonoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($fono)
    {
        $data = $this->repo->getByFono($fono);
        return new Response([], $data);
    }
}
