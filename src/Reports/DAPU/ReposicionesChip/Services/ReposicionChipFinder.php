<?php

namespace AMovil\Reports\DAPU\ReposicionesChip\Services;

use AMovil\Reports\DAPU\ReposicionesChip\Domain\ReposicionChipRepository;
use AMovil\Shared\Application\Response;

class ReposicionChipFinder
{
    private $repo;

    public function __construct(ReposicionChipRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($linea)
    {
        $data = $this->repo->getByLinea($linea);
        return new Response([], $data);
    }
}
