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

        $response = $this->buildResponse(
            $identifiers['input'],
            $history,
            $summary,
            $customerInfo
        );

        return $this->applyCalculatedFixedChargeToChart($response, $customerInfo);
    }

    private function applyCalculatedFixedChargeToChart(array $response, ?array $customerInfo): array
    {
        $cargoFijoReal = $this->parseAmount($customerInfo['cargo_fijo'] ?? 0);
        $cargoFijoCalculado = $this->parseAmount(
            $customerInfo['cargo_fijo_calculado'] ?? $cargoFijoReal
        );
        $importeTotal = $this->parseAmount($response['totals']['importe_total'] ?? 0);

        $response['totals']['cargo_fijo'] = $cargoFijoCalculado;
        $response['totals']['cargo_fijo_real'] = $cargoFijoReal;
        $response['totals']['cargo_fijo_calculado'] = $cargoFijoCalculado;
        $response['totals']['factor_aplicado'] = $this->parseAmount(
            $customerInfo['factor_aplicado'] ?? 1
        );
        $response['totals']['saldo'] = $cargoFijoCalculado - $importeTotal;

        return $response;
    }
}

