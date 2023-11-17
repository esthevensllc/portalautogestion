<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Domain;

use DateTime;

interface InformeCCPPRepository
{
    public function create(string $username, string $filename, DateTime $createdAt);
    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0);
}
