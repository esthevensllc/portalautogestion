<?php

namespace AMovil\Reports\BasesClientesPrepago\Domain;

use DateTime;

interface BasesClientesPrepagoRepository
{
    public function getDep();
    public function getProv($dep);
    public function getDist($dep,$prov);
    public function getReporte($granu, $dep, $prov, $dist);
}
