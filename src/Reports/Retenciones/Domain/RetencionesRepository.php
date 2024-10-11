<?php

namespace AMovil\Reports\Retenciones\Domain;

use DateTime;

interface RetencionesRepository
{
    public function saveAll($data);
    public function saveAllRutas($data);
}
