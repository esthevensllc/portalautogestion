<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use AMovil\Shared\Application\Response;

class GetBloqueoControlRegLog
{
    private $logRepo;

    public function __construct(BloqueoControlRegLogRepository $repoLog)
    {
        $this->logRepo = $repoLog;
    }

    public function __invoke() :Response
    {
        $data = $this->logRepo->getByCriteria();
        return Response::respData($data);
    }

    public function getTiposOperacion()
    {
        return $this->logRepo->getTiposOperacion();
    }

    public function getTiposDocumento()
    {
        return $this->logRepo->getTiposDocumento();
    }
}
