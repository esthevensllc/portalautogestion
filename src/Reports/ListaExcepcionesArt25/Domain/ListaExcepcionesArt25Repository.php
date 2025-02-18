<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Domain;

interface ListaExcepcionesArt25Repository
{
    public function findAll();
    public function findLast();
    public function getByImei(array $imeis);
}
