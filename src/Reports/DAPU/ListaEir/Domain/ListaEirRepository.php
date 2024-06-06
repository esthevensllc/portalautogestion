<?php

namespace AMovil\Reports\DAPU\ListaEir\Domain;

interface ListaEirRepository
{
    public function getByImei($imei);
}
