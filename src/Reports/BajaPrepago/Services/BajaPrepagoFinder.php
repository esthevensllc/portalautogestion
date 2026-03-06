<?php

namespace AMovil\Reports\BajaPrepago\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoRepository;
use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoStatus;
use AMovil\Shared\Application\Response;

class BajaPrepagoFinder
{
    private $repository;
    private $authService;

    public function __construct(BajaPrepagoRepository $repository, AuthService $authService)
    {
        $this->repository = $repository;
        $this->authService = $authService;
    }

    public function getLogs(){
        return $this->repository->getLogByCriteria([]);
    }

    public function getBasePreimportByBaseIdAndEstado($baseId, $estado) {
        if ($estado !== BajaPrepagoStatus::EN_PROCESO && $estado !== BajaPrepagoStatus::NO_VALIDADO) {
            return Response::respError(["message" => "El estado ingresado no esta válido"]);
        }
        $data = $this->exportTxt($baseId, $estado);
        return Response::respData($data);
    }

    public function exportTxt($baseId, $estado){
        $data = $this->repository->getPreimportByBaseId($baseId, $estado, $this->authService->getUserIdentifier());
        foreach($data as $row){
            yield $row->msisdn;
        }
    }
}
