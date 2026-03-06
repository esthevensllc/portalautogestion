<?php

namespace AMovil\Reports\BajaPrepago\Domain;

use Generator;

interface BajaPrepagoRepository
{
    public function insertPreimport(Generator $msisdnList);
    public function validatePreimport($baseId);
    public function getPreimportSummary($baseId);
    public function insertFromPreimport($baseId);
    public function createLog($baseId, $clase, $subClase, $notas, $username, $cantLineas, $ipAddress);
    public function findLogByBaseId($baseId);
    public function getPreimportByBaseId($baseId, $estado, $username);
    public function getLogByCriteria($criteria);
    public function getClaseList();
    public function getSubClaseList();
    public function getNotasList();
}
