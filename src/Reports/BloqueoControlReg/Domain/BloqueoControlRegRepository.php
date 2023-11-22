<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

interface BloqueoControlRegRepository
{
    public function saveReporteSIBMED($id, array $values);
    public function saveReporteDAPU($id, string $filePath, int $imei);
    public function findReporteDAPU($id);
    public function getReporte($id);
}
