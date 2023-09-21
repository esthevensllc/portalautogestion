<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;

class DeleteAutomaticReportLog
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id)
    {
        $this->repo->deleteAutomaticReportLog($id);
    }
}
