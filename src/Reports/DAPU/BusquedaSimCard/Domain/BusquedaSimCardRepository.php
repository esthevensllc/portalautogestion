<?php

namespace AMovil\Reports\DAPU\BusquedaSimCard\Domain;

interface BusquedaSimCardRepository
{
    public function getByIccid(string $iccid);
}
