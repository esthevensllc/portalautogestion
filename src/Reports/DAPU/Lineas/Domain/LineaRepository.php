<?php

namespace AMovil\Reports\DAPU\Lineas\Domain;

interface LineaRepository
{
    public function getUsuariosByLinea(array $values);
    public function getUsuariosByDni(array $values);
}
