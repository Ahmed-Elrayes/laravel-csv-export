<?php

namespace Elrayes\LaravelCsvExport\Tests\Feature;

use Elrayes\LaravelCsvExport\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeExportCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        if (File::exists(app_path('Export/TestExporter.php'))) {
            File::delete(app_path('Export/TestExporter.php'));
        }

        if (File::exists(app_path('Export/Sub/NestedExporter.php'))) {
            File::delete(app_path('Export/Sub/NestedExporter.php'));
            @rmdir(app_path('Export/Sub'));
        }

        if (File::isDirectory(app_path('Export'))) {
            @rmdir(app_path('Export'));
        }

        parent::tearDown();
    }

    public function test_it_can_generate_exporter_class()
    {
        $this->artisan('make:export', ['name' => 'TestExporter'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Export/TestExporter.php')));

        $content = File::get(app_path('Export/TestExporter.php'));

        $this->assertStringContainsString('namespace App\Export;', $content);
        $this->assertStringContainsString('class TestExporter extends BaseExporter', $content);
    }

    public function test_it_can_generate_exporter_class_in_nested_directory()
    {
        $this->artisan('make:export', ['name' => 'Sub/NestedExporter'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Export/Sub/NestedExporter.php')));

        $content = File::get(app_path('Export/Sub/NestedExporter.php'));

        $this->assertStringContainsString('namespace App\Export\Sub;', $content);
        $this->assertStringContainsString('class NestedExporter extends BaseExporter', $content);
    }
}
