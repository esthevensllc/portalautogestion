<?php

namespace AMovil\Reports\OltCmts\Domain;

use DateTime;

interface OltCmtsNuevoRepository
{
    public function getReportTypes();
    public function getOltsValues(DateTime $fecha);
    public function getCmtsValues(DateTime $fecha);
    public function getOltFinalReport(array $tickets);
    public function getCmtsFinalReport(array $tickets);
    public function getFilteredFinalReport(int $typeId, DateTime $fecha, array $values);
    public function cleanupTemporaryTables(?string $userIdentifier = null);
    public function getOltReport(DateTime $fecha, array $olts);
    public function getCmtsReport(DateTime $fecha, array $cmts);
    public function getOltListSummary();
}
