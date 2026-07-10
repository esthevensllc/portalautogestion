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

        // DS_SUSCRIPTORES requiere el número con el prefijo de país 51.
        $customerInfo = $this->repo->getCustomerInfo($identifiers['customer_info']);

        // Las tablas de acciones IMF almacenan el número sin el prefijo 51.
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
