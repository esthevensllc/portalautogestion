<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;

class ControlEirImeiService
{
    private $repo;
    private $authService;

    public function __construct(BloqueoImeiRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke($filters)
    {
        $response = Response::respData($this->repo->getControlEirImeiByCriteria($filters));

        $imei = $filters[0][1];

        $username = $this->authService->getUserIdentifier();

        if(count($response->data()) > 0){
            $data = $response->data()[0];
            $log = $this->repo->saveSearchEirImeiLog($username, $data['imei'], $data['statusid'], $data['status'], $data['actionid'], $data['reasoncode'], $data['imsi'], $data['time_stamp2'], $data['reg_time']);
        }else{
            $log = $this->repo->saveSearchEirImeiLog($username, $imei, "", "", "", "", "", "", "");
        }

        return $response;
    }
}
