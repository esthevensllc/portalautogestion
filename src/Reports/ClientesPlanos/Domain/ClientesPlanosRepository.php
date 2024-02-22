<?php

namespace AMovil\Reports\ClientesPlanos\Domain;

use DateTime;

interface ClientesPlanosRepository
{
    public function getReportTypes();
    public function getOltsValues(DateTime $fecha);
    public function getCmtsValues(DateTime $fecha);
    public function getOltReport(DateTime $fecha, array $olts);
    public function getCmtsReport(DateTime $fecha, array $cmts);
    public function getOltListSummary();
}
