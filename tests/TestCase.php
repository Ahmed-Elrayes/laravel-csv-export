<?php

namespace Elrayes\LaravelCsvExport\Tests;

if (class_exists(\Orchestra\Testbench\TestCase::class)) {
    abstract class TestCase extends \Orchestra\Testbench\TestCase
    {
        protected function getPackageProviders($app)
        {
            return [
                \Elrayes\LaravelCsvExport\Providers\CSVExportServiceProvider::class,
            ];
        }

        protected function getPackageAliases($app)
        {
            return [
                'CSVExport' => \Elrayes\LaravelCsvExport\Facades\CSVExport::class,
            ];
        }
    }
} else {
    abstract class TestCase extends \Tests\TestCase
    {
        // When running through the main Laravel app,
        // the Service Provider is already registered in bootstrap/providers.php
    }
}
