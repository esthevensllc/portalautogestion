<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Services;

use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;

class FijaTicketReportFinder
{
    private $repo;
    public function __construct(FijaTicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getByCriteria($filters)
    {
        $data = $this->repo->getTicketsByCriteria($filters);
        return $data;
    }
}
