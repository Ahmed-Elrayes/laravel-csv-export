<?php

declare(strict_types=1);

namespace Elrayes\LaravelCsvExport\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @template TModel of mixed
 */
interface DataExporterInterface
{
    /**
     * Return either a Builder (or Builder-like) query or an in-memory Collection
     * to be iterated and exported.
     *
     * @return Builder|Collection<int, TModel>|BuilderContract
     */
    public function query(): Builder|Collection|BuilderContract;

    /**
     * Provide the CSV headings.
     *
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * Map a single row into a flat array of scalar values for CSV.
     *
     * @param TModel $row
     * @return array<int, scalar|null>
     */
    public function map(mixed $row): array;
}
