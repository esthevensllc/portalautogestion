<?php

namespace AMovil\Reports\IMRIMF\Services;

use AMovil\Reports\IMRIMF\Domain\ImfRepository;

class ImfFinder
{
    use ConcernsSearchResponse;

    private ImfRepository $repo;

    public function __construct(ImfRepository $repo)
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
