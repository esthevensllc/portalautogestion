<?php

namespace AMovil\Reports\DAPU\Sots\Domain;

interface DapuSotRepository
{
    public function getByDocCliente(string $docCliente);
}
