<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Elrayes\LaravelCsvExport\Services\CSVExportService setChunkSize(int $chunkSize)
 * @method static \Elrayes\LaravelCsvExport\Services\CSVExportService setMaxLimit(?int $maxLimit)
 * @method static \Elrayes\LaravelCsvExport\Services\CSVExportService setUseMaxLimit(bool $useMaxLimit)
 * @method static \Elrayes\LaravelCsvExport\Services\CSVExportService includeBom(bool $include = true)
 * @method static string toFile(string $exporterClass, string $path)
 * @method static string store(string $exporterClass, string $path, string $disk = 'local')
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(string $exporterClass, string $fileName)
 * @method static \Symfony\Component\HttpFoundation\BinaryFileResponse download(string $exporterClass, string $fileName)
 */
class CSVExport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'csvexport';
    }
}
