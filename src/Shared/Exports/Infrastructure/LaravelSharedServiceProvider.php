<?php

namespace AMovil\Shared\Exports\Infrastructure;

use Illuminate\Support\ServiceProvider;

class LaravelSharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \AMovil\Shared\Exports\Domain\ExportService::class,
            SpreedSheetExport::class
        );
    }
}
