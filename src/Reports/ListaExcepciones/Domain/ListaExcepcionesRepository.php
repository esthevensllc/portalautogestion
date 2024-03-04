<?php

namespace AMovil\Reports\ListaExcepciones\Domain;

interface ListaExcepcionesRepository
{
    public function findAll();
    public function findLast();
    public function getByImei($imei);
}
