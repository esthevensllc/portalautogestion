<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;

class GetImeiCDRReportLog
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($filters)
    {
        return Response::respData($this->repo->getAutomaticReportByCriteria($filters));
    }

    public function __invokeOnline($filters)
    {
        return Response::respData($this->repo->getAutomaticOnlineReportByCriteria($filters));
    }
}
