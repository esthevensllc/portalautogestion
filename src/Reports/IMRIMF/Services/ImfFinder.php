<?php

namespace AMovil\Reports\IMRIMF\Services;

use AMovil\Reports\IMRIMF\Domain\ImfRepository;

class ImfFinder
{
    use ConcernsSearchResponse;

    private const SEARCH_BY_CUSTOMER_ID = 'customer_id';
    private const SEARCH_BY_PHONE = 'telefono';
    private const PRODUCT_FIJA = 'FIJA';
    private const PRODUCT_MOVIL = 'MOVIL';

    private ImfRepository $repo;

    public function __construct(ImfRepository $repo)
    {
        $this->repo = $repo;
    }

    public function search(
        string $value,
        string $searchType = self::SEARCH_BY_PHONE,
        string $producto = self::PRODUCT_MOVIL
    ): array
    {
        $normalizedSearchType = $this->normalizeImfSearchType($searchType);
        $normalizedProduct = $this->normalizeProduct($producto);

        if ($normalizedSearchType === self::SEARCH_BY_CUSTOMER_ID) {
            return $this->searchByCustomerId($value, $normalizedProduct);
        }

        return $this->searchByPhone($value, $normalizedProduct);
    }

    private function searchByPhone(string $telefono, string $producto): array
    {
        $identifiers = $this->normalizeImfIdentifiers($telefono);
        $customerInfo = $this->repo->getCustomerInfo($identifiers['customer_info'], $producto);
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

    private function searchByCustomerId(string $customerId, string $producto): array
    {
        $identifiers = $this->normalizeImfCustomerIdIdentifiers($customerId);
        $context = $this->repo->getCustomerContextByCustomerId(
            $identifiers['customer_info'],
            $producto
        );
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
        $response['totals']['califica_fidelizacion'] = (bool) (
            $customerInfo['califica_fidelizacion'] ?? true
        );
        $response['totals']['saldo'] = $cargoFijoCalculado - $importeTotal;

        return $response;
    }

    private function normalizeProduct(string $producto): string
    {
        $producto = strtoupper(trim($producto));

        if (!in_array($producto, [self::PRODUCT_FIJA, self::PRODUCT_MOVIL], true)) {
            throw new \InvalidArgumentException('El producto IMF debe ser FIJA o MOVIL.');
        }

        return $producto;
    }
}
