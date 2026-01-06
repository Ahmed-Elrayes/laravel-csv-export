<?php

namespace Elrayes\LaravelCsvExport\Tests\Feature;

use Elrayes\LaravelCsvExport\Exporters\BaseExporter;
use Elrayes\LaravelCsvExport\Facades\CSVExport;
use Elrayes\LaravelCsvExport\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CSVExportTest extends TestCase
{
    public function test_facade_overrides_exporter_settings()
    {
        Storage::fake('local');

        $exporter = new class extends BaseExporter {
            protected int $chunkSize = 10;
            protected ?int $maxLimit = 5;
            protected bool $useMaxLimit = true;
            protected bool $includeBom = false;

            public function query(): Collection
            {
                return collect([
                    ['name' => 'John'],
                    ['name' => 'Jane'],
                    ['name' => 'Doe'],
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                    ['name' => 'Charlie'],
                ]);
            }

            public function headings(): array
            {
                return ['Name'];
            }

            public function map($row): array
            {
                return [$row['name']];
            }
        };

        // Bind the anonymous class so the service can resolve it if needed,
        // though we usually pass the class name. For testing we can mock or use a real class.
        $this->app->bind('TestExporter', fn() => $exporter);

        // 1. Test default behavior (maxLimit = 5)
        CSVExport::toFile('TestExporter', 'export1.csv');
        $content1 = file_get_contents('export1.csv');
        $rows1 = explode("\n", trim($content1));
        $this->assertCount(6, $rows1); // 1 heading + 5 data rows
        unlink('export1.csv');

        // 2. Test Facade override (maxLimit = 2)
        CSVExport::setMaxLimit(2)->toFile('TestExporter', 'export2.csv');
        $content2 = file_get_contents('export2.csv');
        $rows2 = explode("\n", trim($content2));
        $this->assertCount(3, $rows2); // 1 heading + 2 data rows
        unlink('export2.csv');

        // 3. Test includeBom via Facade
        CSVExport::includeBom(true)->toFile('TestExporter', 'export3.csv');
        $content3 = file_get_contents('export3.csv');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content3);
        unlink('export3.csv');
    }

    public function test_facade_reset_state_after_export()
    {
        Storage::fake('local');
        $exporter = new class extends BaseExporter {
            public function query(): Collection { return collect([['name' => 'A']]); }
            public function map($row): array { return [$row['name']]; }
        };
        $this->app->bind('TestExporter', fn() => $exporter);

        CSVExport::includeBom(true)->toFile('TestExporter', 'export1.csv');
        $this->assertStringStartsWith("\xEF\xBB\xBF", file_get_contents('export1.csv'));
        unlink('export1.csv');

        // Second call without includeBom, it should be false (default in exporter)
        CSVExport::toFile('TestExporter', 'export2.csv');
        $this->assertStringStartsNotWith("\xEF\xBB\xBF", file_get_contents('export2.csv'));
        unlink('export2.csv');
    }
}
