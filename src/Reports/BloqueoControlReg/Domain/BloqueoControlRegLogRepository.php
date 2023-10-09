<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

interface BloqueoControlRegLogRepository
{
    public function saveLog($id, $tipoDocumentoId, $filename, $sizeBytes);
    public function getByCriteria($filters = []);
}
