<?php

namespace AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Domain;

interface ListaExcepcionesImeiImsiRepository
{
    public function getByImeis(array $imeis);
}
