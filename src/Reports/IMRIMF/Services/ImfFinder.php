<?php

namespace AMovil\Reports\IMRIMF\Services;

use AMovil\Reports\IMRIMF\Domain\ImfRepository;

class ImfFinder
{
    use ConcernsSearchResponse;

    private const SEARCH_BY_CUSTOMER_ID = 'customer_id';
    private const SEARCH_BY_PHONE = 'telefono';

    private ImfRepository $repo;

    public function __construct(ImfRepository $repo)
    {
        $this->repo = $repo;
    }

    public function search(string $value, string $searchType = self::SEARCH_BY_PHONE): array
    {
        $normalizedSearchType = $this->normalizeImfSearchType($searchType);

        if ($normalizedSearchType === self::SEARCH_BY_CUSTOMER_ID) {
            return $this->searchByCustomerId($value);
        }
<<<<<<< Updated upstream

        return $this->searchByPhone($value);
    }

=======

        return $this->searchByPhone($value);
    }

>>>>>>> Stashed changes
    private function searchByPhone(string $telefono): array
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

    private function searchByCustomerId(string $customerId): array
    {
        $identifiers = $this->normalizeImfCustomerIdIdentifiers($customerId);
        $context = $this->repo->getCustomerContextByCustomerId($identifiers['customer_info']);
        $customerInfo = $context['customer_info'] ?? null;
        $actionIdentifiers = array_merge(
            [
                $identifiers['actions'],
                $identifiers['customer_info'],
            ],
            $context['action_identifiers'] ?? []
        );

        $history = $this->repo->getHistoryByIdentifiers($actionIdentifiers);
        $summary = $this->repo->getSummaryByIdentifiers($actionIdentifiers);

        return $this->buildResponse(
            $identifiers['input'],
            $history,
            $summary,
            $customerInfo
        );
    }
}

