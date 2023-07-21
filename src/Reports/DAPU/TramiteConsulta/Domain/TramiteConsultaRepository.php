<?php

namespace AMovil\Reports\DAPU\TramiteConsulta\Domain;

interface TramiteConsultaRepository
{
    public function getReporteF1(array $input);
    public function getReporteF2(array $input);
}
