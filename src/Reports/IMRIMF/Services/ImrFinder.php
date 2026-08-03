<?php

namespace AMovil\Reports\IMRIMF\Services;

use AMovil\Reports\IMRIMF\Domain\ImrRepository;

class ImrFinder
{
    use ConcernsSearchResponse;

    private const SEARCH_BY_CUSTOMER_ID = 'customer_id';
    private const SEARCH_BY_PHONE = 'telefono';

    private ImrRepository $repo;

    public function __construct(ImrRepository $repo)
    {
        $this->repo = $repo;
    }

    public function search(string $value, string $searchType = self::SEARCH_BY_CUSTOMER_ID): array
    {
        $normalizedSearchType = $this->normalizeImrSearchType($searchType);
        $identifiers = $normalizedSearchType === self::SEARCH_BY_PHONE
            ? $this->normalizeImrPhoneIdentifiers($value)
            : $this->normalizeImrIdentifiers($value);

        $customerInfo = $normalizedSearchType === self::SEARCH_BY_PHONE
            ? $this->repo->getCustomerInfoByPhone($identifiers['customer_info'])
            : $this->repo->getCustomerInfo($identifiers['customer_info']);

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
