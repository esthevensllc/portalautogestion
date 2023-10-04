<?php

namespace AMovil\Shared\Infrastructure;

use Illuminate\Support\ServiceProvider;

class LaravelSharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \AMovil\Shared\Exports\Domain\ExportService::class,
            \AMovil\Shared\Exports\Infrastructure\SpreedSheetExport::class
        );
        $this->app->bind(
            \AMovil\Shared\FileStorage\Domain\StorageService::class,
            \AMovil\Shared\FileStorage\Infrastructure\LaravelStorageService::class
        );
        $this->app->bind(
            \AMovil\Shared\Remedy\Domain\RemedyService::class,
            \AMovil\Shared\Remedy\Infrastructure\SoapRemedyService::class
        );
    }
}
