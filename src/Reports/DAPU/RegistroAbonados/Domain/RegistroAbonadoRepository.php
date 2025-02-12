<?php

namespace AMovil\Reports\DAPU\RegistroAbonados\Domain;

interface RegistroAbonadoRepository
{
    public function getByMsisdn(array $values);
    public function getByNumDocumento(array $values);
}
