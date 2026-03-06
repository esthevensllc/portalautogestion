<?php

namespace AMovil\Reports\OltCmts\Domain;

use DateTime;

interface OltCmtsRepository
{
    public function getReportTypes();
    public function getOltsValues(DateTime $fecha);
    public function getCmtsValues(DateTime $fecha);
    public function getOltFinalReport(array $tickets);
    public function getCmtsFinalReport(array $tickets);
    public function getOltReport(DateTime $fecha, array $olts);
    public function getCmtsReport(DateTime $fecha, array $cmts);
    public function getOltListSummary();
}
