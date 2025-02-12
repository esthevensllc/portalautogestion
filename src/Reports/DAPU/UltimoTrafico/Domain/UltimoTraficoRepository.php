<?php

namespace AMovil\Reports\DAPU\UltimoTrafico\Domain;

interface UltimoTraficoRepository
{
    public function getByMsisdn(array $values);
    public function getByImeis(array $values);
}
