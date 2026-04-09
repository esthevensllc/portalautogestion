<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain;

class InformeStatus
{
    const CARGADO = 'CARGADO';
    const APROBADO = 'APROBADO';
    const DESAPROBADO = 'DESAPROBADO';
    const EN_ESPERA = 'EN_ESPERA';
    const REVISADO = 'REVISADO';
    const PROCESADO = 'PROCESADO';
    const EN_EJECUCION_PRE = 'EN_EJECUCION_PRE';
    const EN_ESPERA_PRE = 'EN_ESPERA_PRE';
    const EN_EJECUCION_POST = 'EN_EJECUCION_POST';
    const EN_ESPERA_POST = 'EN_ESPERA_POST';
    const FASE_FINAL = 'FASE_FINAL';
}
