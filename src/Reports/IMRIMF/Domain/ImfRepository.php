<?php

namespace AMovil\Reports\IMRIMF\Domain;

interface ImfRepository
{
    public function getCustomerInfo(string $telefono, string $producto): ?array;

    public function getCustomerContextByCustomerId(string $customerId, string $producto): ?array;

    public function getHistory(string $telefono): array;

    public function getHistoryByIdentifiers(array $identifiers): array;

    public function getSummary(string $telefono): array;

    public function getSummaryByIdentifiers(array $identifiers): array;
}

