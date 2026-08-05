<?php

namespace AMovil\Reports\IMRIMF\Domain;

interface ImrRepository
{
    public function getCustomerInfo(string $customerId): ?array;

    public function getCustomerInfoByPhone(string $telefono): ?array;

    public function getHistory(string $telefono): array;

    public function getSummary(string $telefono): array;
}
