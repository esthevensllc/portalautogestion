<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Domain;

use DateTime;

interface LineasMTCOsiptelRepository
{
    // public function prepagoExistForToday(): bool;
    // public function postpagoExistForToday(): bool;
    public function getReportePrepago($ticket, $offset = 0, $limit = null);
    public function getReportePostpago($ticket, $offset = 0, $limit = null);
    public function saveLineasPrepagoForToday($ticket);
    public function saveLineasPostpagoForToday($ticket);
    public function countLineasPrepagoForToday($ticket): int;
    public function countLineasPostpagoForToday($ticket): int;

    // OSIPTEL
    public function getLineasPostpagoOsiptel($ticket, $offset = 0, $limit = null);
    public function saveLineasPostpagoOsiptelForToday($ticket);
    public function countLineasPostpagoOsiptelForToday($ticket): int;
    
    public function getLineasPrepagoOsiptel($ticket, $offset = 0, $limit = null);
    public function saveLineasPrepagoOsiptelForToday($ticket);
    public function countLineasPrepagoOsiptelForToday($ticket): int;

    public function getTicketProcessed($tipo_plan, $tipo_solicitud);
    public function saveLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file, $fecha_carga);
    public function saveDataLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file);

    public function findReportBy($ticket, $tipo, DateTime $fecha);
    public function deleteBy($ticket, $tipo, DateTime $fecha);

    // OSIPTEL MSISDN
    public function countLineasOsiptelMsisdn($ticket,$tipo_reporte);
    public function getReporteOsiptelMsisdn($ticket,$tipo_plan, $offset=0, $limit = null);
    public function saveLineasOsiptelMsisdn($ticket,$tipo_plan);
    public function countLineasOsiptelMsisdnV2($ticket,$tipo_plan);
    public function saveLineasOsiptelMsisdnV2($ticket,$tipo_reporte);
    public function saveLineasOsiptelHist($ticket,$tipo_reporte);
    public function saveLineasOsiptelLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file, $fecha_carga);
    public function getTicketProcessedMsisdn($tipo_plan);
    public function findReportMsisdnBy($ticket, $tipo);
    public function deleteMsisdnBy($ticket, $tipo);
}
