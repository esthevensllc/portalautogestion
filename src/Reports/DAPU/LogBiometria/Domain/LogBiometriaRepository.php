<?php

namespace AMovil\Reports\DAPU\LogBiometria\Domain;

interface LogBiometriaRepository
{
    public function getByDniAndMsisdn($dni, $msisdn);
}
