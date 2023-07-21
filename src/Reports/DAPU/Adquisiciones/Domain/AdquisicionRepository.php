<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Domain;

interface AdquisicionRepository
{
    public function getByImei(string $imei);
}
