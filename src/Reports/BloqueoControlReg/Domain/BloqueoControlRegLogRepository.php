<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

interface BloqueoControlRegLogRepository
{
    public function saveLog($id, $tipoOperacionId, $tipoDocumentoId, $filename, $sizeBytes, $filePath, $eirFilename);
    public function getByCriteria($filters = []);
    public function findFileContentById($id);
    public function getTiposOperacion();
    public function getTiposDocumento();
}
