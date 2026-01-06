<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Services;

class CSVWriter
{
    /** @var resource|null */
    protected $handle;
    protected string $delimiter;
    protected string $enclosure;
    protected string $escape;

    public function __construct($handle, string $delimiter = ',', string $enclosure = '"', string $escape = "\\")
    {
        if (!is_resource($handle)) {
            throw new \InvalidArgumentException('CSVWriter expects a valid file handle.');
        }
        $this->handle = $handle;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    public function writeBom(): void
    {
        if (!$this->handle) {
            return;
        }
        fwrite($this->handle, "\xEF\xBB\xBF");
    }

    /**
     * @param array<int, mixed> $row
     */
    public function writeRow(array $row): void
    {
        if (!$this->handle) {
            return;
        }
        fputcsv($this->handle, $row, $this->delimiter, $this->enclosure, $this->escape);
    }

    public function close(): void
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
