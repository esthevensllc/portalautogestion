<?php

namespace AMovil\Reports\IMRIMF\Domain;

interface ImrRepository
{
    public function getHistory(string $telefono): array;

    public function getSummary(string $telefono): array;
}
