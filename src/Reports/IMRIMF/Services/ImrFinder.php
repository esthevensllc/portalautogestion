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

    public function search(string $customerId): array
    {
        $identifiers = $this->normalizeImrIdentifiers($customerId);

        // DS_SUSCRIPTORES.CUENTA_CD requiere el Customer ID sin la letra H.
        $customerInfo = $this->repo->getCustomerInfo($identifiers['customer_info']);

        // Las tablas de acciones IMR almacenan el identificador con la letra H.
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
