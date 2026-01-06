<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Providers;

use Illuminate\Support\ServiceProvider;
use Elrayes\LaravelCsvExport\Services\CSVExportService;
use Elrayes\LaravelCsvExport\Console\Commands\MakeExportCommand;

class CSVExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('csvexport', function ($app) {
            return new CSVExportService();
        });

        $this->app->singleton(CSVExportService::class, function ($app) {
            return $app->make('csvexport');
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeExportCommand::class,
            ]);
        }
    }
}
