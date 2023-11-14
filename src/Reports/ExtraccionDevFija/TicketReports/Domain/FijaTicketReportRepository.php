<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Domain;

interface FijaTicketReportRepository
{
    public function deleteBy($ticket);
    public function getTicketsByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0);
}
