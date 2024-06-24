<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Domain;

interface ListaExcepcionesMasivoRepository
{
    public function saveLog($id, $tipoOperacionId, $filename, ?int $sizeBytes, $eirFilename, array $lista);
    public function getByCriteria($filters = []);
    public function getTiposOperacion();
    public function getEirReponseByEirId($eirId);
}
