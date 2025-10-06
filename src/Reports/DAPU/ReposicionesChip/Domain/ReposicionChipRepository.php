<?php

namespace AMovil\Reports\DAPU\ReposicionesChip\Domain;

interface ReposicionChipRepository
{
    public function getByLinea(string $linea);
}
