<?php

namespace AMovil\Reports\IMRIMF\Services;

use AMovil\Reports\IMRIMF\Domain\ImrRepository;

class ImrFinder
{
    use ConcernsSearchResponse;

    private ImrRepository $repo;

    public function __construct(ImrRepository $repo)
    {
        $this->repo = $repo;
    }

    public function search(string $telefono): array
    {
        $telefono = $this->validateTelefono($telefono);

        $history = $this->repo->getHistory($telefono);
        $summary = $this->repo->getSummary($telefono);

        return $this->buildResponse($telefono, $history, $summary);
    }
}
