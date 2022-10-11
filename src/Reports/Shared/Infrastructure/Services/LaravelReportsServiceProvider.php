<?php

namespace AMovil\Reports\Shared\Infrastructure\Services;

use Illuminate\Support\ServiceProvider;

class LaravelReportsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository::class,
            \AMovil\Reports\RepDetLlamadas\Infrastructure\Repository\EloquentDetalleLlamadasRepository::class
        );
        $this->app->bind(
            \AMovil\Reports\RepFiscalia\Domain\ReporteFiscalRepository::class,
            \AMovil\Reports\RepFiscalia\Infrastructure\Repository\EloquentReporteFiscalRepository::class
        );
        $this->app->bind(
            \AMovil\Reports\ReportLog\Domain\ReportLogRepository::class,
            \AMovil\Reports\ReportLog\Infrastructure\EloquentReportLogRepository::class
        );
    }
}
