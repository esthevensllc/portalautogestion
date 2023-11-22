<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

interface BloqueoControlRegLogRepository
{
    public function saveLog($id, $tipoOperacionId, $tipoDocumentoId, $filename, $sizeBytes, $filePath, $eirFilename, $cantRegistros, $cantUnicos);
    public function getByCriteria($filters = []);
    public function findFileContentById($id);
    public function getTiposOperacion();
    public function getTiposDocumento();
    public function getTipificaciones();
    public function getTickler();
    public function insertTableControl($tipo_operacion,$tipificacion,$tickler,$intantaneo,$notas);
}
