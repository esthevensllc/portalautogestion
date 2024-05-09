<?php

namespace AMovil\Reports\ListaExcepcionesEliminar\Domain;

interface ListaExcepcionesEliminarRepository
{
    public function findAll();
    public function findLast();
    public function getByImei($imei);
}
