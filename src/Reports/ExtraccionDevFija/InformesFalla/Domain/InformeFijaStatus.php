<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain;

class InformeFijaStatus
{
    const REVISADO = 'REVISADO';
    const APROBADO = 'APROBADO';
    const DESAPROBADO = 'DESAPROBADO';
    const EN_ESPERA = 'EN_ESPERA';
    const EN_EJECUCION = 'EN_EJECUCION';
    const EN_ESPERA_EJECUCION = 'EN_ESPERA_EJECUCION';
    const PROCESADO = 'PROCESADO';
    const SIN_PROCESAR = 'SIN_PROCESAR';
}
