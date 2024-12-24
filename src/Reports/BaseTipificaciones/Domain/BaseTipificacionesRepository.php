<?php

namespace AMovil\Reports\BaseTipificaciones\Domain;

interface BaseTipificacionesRepository
{
    public function procesarData(array $lista);
    public function validarData();
}
