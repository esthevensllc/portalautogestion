<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;

class GetImeiCDRConfig
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(): Response
    {
        return Response::respData($this->repo->getConfig());
    }
}
