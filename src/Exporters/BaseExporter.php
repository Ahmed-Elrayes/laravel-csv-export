<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Exporters;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Elrayes\LaravelCsvExport\Contracts\DataExporterInterface;

/**
 * @template TModel of mixed
 * @implements DataExporterInterface<TModel>
 */
abstract class BaseExporter implements DataExporterInterface
{
    protected int $chunkSize = 1000;
    protected ?int $maxLimit = 10000;
    protected bool $useMaxLimit = true;
    protected bool $includeBom = false;

    public function setChunkSize(int $chunkSize = 1000): static
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    public function setMaxLimit(?int $maxLimit = 10000): static
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

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function getMaxLimit(): ?int
    {
        return $this->maxLimit;
    }

    public function shouldUseMaxLimit(): bool
    {
        return $this->useMaxLimit;
    }

    public function shouldIncludeBom(): bool
    {
        return $this->includeBom;
    }

    /**
     * Sensible defaults so concrete exporters only override what they need.
     *
     * @return Builder|Collection<int, TModel>|BuilderContract
     */
    public function query(): Builder|Collection|BuilderContract
    {
        return Builder::clone();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * @param TModel $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        return [];
    }
}
