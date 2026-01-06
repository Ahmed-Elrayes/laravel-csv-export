<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Services;

use Elrayes\LaravelCsvExport\Contracts\DataExporterInterface;
use Elrayes\LaravelCsvExport\Exporters\BaseExporter;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CSVExportService
{
    protected ?int $chunkSize = null;
    protected ?int $maxLimit = null;
    protected ?bool $useMaxLimit = null;
    protected ?bool $includeBom = null;

    public function setChunkSize(int $chunkSize): static
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    public function setMaxLimit(?int $maxLimit): static
    {
        $this->maxLimit = $maxLimit;
        return $this;
    }

    public function setUseMaxLimit(bool $useMaxLimit): static
    {
        $this->useMaxLimit = $useMaxLimit;
        return $this;
    }

    public function includeBom(bool $include = true): static
    {
        $this->includeBom = $include;
        return $this;
    }

    /**
     * Export using a file handle provided by the caller.
     * The caller owns the handle lifecycle; this method will close it.
     *
     * @template TModel
     * @param DataExporterInterface<TModel> $exporter
     * @param resource $handle
     */
    public function exportToHandle(DataExporterInterface $exporter, $handle): void
    {
        $writer = new CSVWriter($handle);

        $includeBom = $this->includeBom ?? (($exporter instanceof BaseExporter) ? $exporter->shouldIncludeBom() : false);

        if ($includeBom) {
            $writer->writeBom();
        }

        $headings = $exporter->headings();
        if (!empty($headings)) {
            $writer->writeRow($headings);
        }

        $data = $exporter->query();

        $useMax = $this->useMaxLimit ?? (!method_exists($exporter, 'shouldUseMaxLimit') || $exporter->shouldUseMaxLimit());
        $max = $this->maxLimit ?? (method_exists($exporter, 'getMaxLimit') ? $exporter->getMaxLimit() : null);
        $chunk = $this->chunkSize ?? (method_exists($exporter, 'getChunkSize') ? $exporter->getChunkSize() : 1000);

        if ($useMax && $max) {
            if ($data instanceof BuilderContract) {
                $data = $data->limit($max)->get();
            } elseif ($data instanceof Collection) {
                $data = $data->take($max);
            }
        }

        if ($data instanceof Collection) {
            $data->chunk($chunk)->each(function (Collection $chunkRows) use ($exporter, $writer) {
                foreach ($chunkRows as $row) {
                    $writer->writeRow($exporter->map($row));
                }
            });
        } else {
            // Assume chunkable query builder
            $data->chunk($chunk, function (Collection $chunkRows) use ($exporter, $writer) {
                foreach ($chunkRows as $row) {
                    $writer->writeRow($exporter->map($row));
                }
            });
        }

        $writer->close();

        // Reset state after export so it doesn't leak to next call if service is singleton
        $this->resetState();
    }

    protected function resetState(): void
    {
        $this->chunkSize = null;
        $this->maxLimit = null;
        $this->useMaxLimit = null;
        $this->includeBom = null;
    }

    /**
     * Convenience: write to a filesystem path.
     */
    public function toFile(string $exporterClass, string $path): string
    {
        /** @var DataExporterInterface $exporter */
        $exporter = app($exporterClass);
        $fp = @fopen($path, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('Unable to open file for writing: ' . $path);
        }
        $this->exportToHandle($exporter, $fp);
        return $path;
    }

    /**
     * Stream as a download response.
     */
    public function stream(string $exporterClass, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($exporterClass) {
            /** @var DataExporterInterface $exporter */
            $exporter = app($exporterClass);
            $fp = fopen('php://output', 'w');
            $this->exportToHandle($exporter, $fp);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ]);
    }

    /**
     * Save to temp file then return a BinaryFileResponse.
     */
    public function download(string $exporterClass, string $fileName): BinaryFileResponse
    {
        $tempPath = storage_path('app/temp/' . uniqid('csv_', true) . '_' . $fileName);
        @mkdir(dirname($tempPath), 0777, true);
        $this->toFile($exporterClass, $tempPath);
        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store the export to the given storage disk and path.
     * Returns the stored relative path.
     */
    public function store(string $exporterClass, string $path, string $disk = 'local'): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'csv_');
        if ($tempPath === false) {
            $tempPath = storage_path('app/temp/' . uniqid('csv_', true));
        }
        @mkdir(dirname($tempPath), 0777, true);

        $this->toFile($exporterClass, $tempPath);

        $contents = @file_get_contents($tempPath);
        if ($contents === false) {
            @unlink($tempPath);
            throw new \RuntimeException('Failed to read temporary CSV contents.');
        }

        $ok = Storage::disk($disk)->put($path, $contents);
        @unlink($tempPath);

        if (!$ok) {
            throw new \RuntimeException('Failed to store CSV to disk: ' . $disk . ' at path: ' . $path);
        }

        return $path;
    }
}
