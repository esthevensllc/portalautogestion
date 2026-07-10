<?php

namespace AMovil\Reports\IMRIMF\Domain;

interface ImfRepository
{
    public function getCustomerInfo(string $telefono): ?array;

    public function getHistory(string $telefono): array;

    public function getSummary(string $telefono): array;
}
