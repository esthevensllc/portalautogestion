<?php

namespace AMovil\Reports\ReportLog\Services;

use AMovil\Auth\User\Services\GetUserModules;
use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use AMovil\Shared\Application\Response;

class GetReporteLog
{
    private $repo;
    private $getUserModules;

    public function __construct(ReportLogRepository $repo, GetUserModules $getUserModules)
    {
        $this->repo = $repo;   
        $this->getUserModules = $getUserModules;
    }

    public function __invoke($filters, $order = [], $offset=0, $limit=10): Response
    {
        $resp = $this->getUserModules->__invoke('C19884');
        $trac_names = [];
        foreach($resp['array'] as $trac){
            $trac_names[] = $trac->name;
        }
        // default filters
        $filters[] = "trac_name.in.".implode(",", $trac_names);
        //dd($filters);

        $response = $this->repo->getByCriteria($filters, $order, $offset, $limit);
        return new Response([], $response);
    }
}
