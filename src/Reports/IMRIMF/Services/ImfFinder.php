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
        $identifiers = $this->normalizeImfIdentifiers($telefono);
        $customerInfo = $this->repo->getCustomerInfo($identifiers['customer_info']);
        $history = $this->repo->getHistory($identifiers['actions']);
        $summary = $this->repo->getSummary($identifiers['actions']);

        return $this->buildResponse(
            $identifiers['input'],
            $history,
            $summary,
            $customerInfo
        );
    }
}
