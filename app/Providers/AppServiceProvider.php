<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $service = $this->app->make(\AMovil\Shared\Infrastructure\LaravelSharedServiceProvider::class, ['app' => $this->app]);
        $service->register();
        $service = $this->app->make(\AMovil\Reports\Shared\Infrastructure\Services\LaravelReportsServiceProvider::class, ['app' => $this->app]);
        $service->register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
