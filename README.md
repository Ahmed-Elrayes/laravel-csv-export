# Elrayes/LaravelCsvExport

Modern, clean CSV export for Laravel 12+ with separation of concerns:
- Contracts for defining what to export
- Exporters to encapsulate query + mapping
- Services to orchestrate writing
- A Facade for ergonomic usage

Licensed under MIT.

## Installation

You can install the package via composer:

```bash
composer require elrayes/laravel-csv-export
```

The service provider and facade will be automatically registered via Laravel's package discovery.

## Running Tests

### Via Laravel Sail

```bash
./vendor/bin/sail test packages/LaravelCsvExport
```

### Standalone (local)

If you have dependencies installed within the package directory:

```bash
cd packages/LaravelCsvExport
composer install
./vendor/bin/phpunit
```

## Concepts

- Elrayes\LaravelCsvExport\Contracts\DataExporterInterface
  - query(): Builder|Collection
  - headings(): array
  - map(mixed $row): array
- Elrayes\LaravelCsvExport\Exporters\BaseExporter
  - Sensible defaults + configuration helpers: chunk size, max limit, include BOM
- Elrayes\LaravelCsvExport\Services\CSVExportService
  - exportToHandle(): core routine using CSVWriter
  - toFile(): write to a path
  - stream(): streamed download
  - download(): generate and return a downloadable response
  - store(): NEW — store resulting CSV to a Laravel Storage disk
- Elrayes\LaravelCsvExport\Services\CSVWriter
  - Low-level wrapper around fputcsv (writeBom, writeRow, close)
- Elrayes\LaravelCsvExport\Facades\CSVExport
  - Facade accessor for easy calls

## Quick start

Generate an exporter using the Artisan command:

```bash
php artisan make:export UserCSVExporter
```

This will create `app/Export/UserCSVExporter.php`. Implement the logic in the generated class.

Example exporter:

```php
namespace App\Export;

use App\Models\User;
use Elrayes\LaravelCsvExport\Exporters\BaseExporter;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserCSVExporter extends BaseExporter
{
    protected bool $includeBom = true; // Excel-friendly

    public function query(): Builder|Collection|BuilderContract
    {
        return User::query()->select(['id', 'name', 'email', 'created_at']);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Registered At'];
    }

    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email,
            optional($row->created_at)->toDateTimeString(),
        ];
    }
}
```

## Usage

Import the Facade:

```php
use Elrayes\LaravelCsvExport\Facades\CSVExport;
use App\Exporters\UserCSVExporter;
```

- Write to a specific file path:

```php
$path = storage_path('app/exports/users.csv');
CSVExport::toFile(UserCSVExporter::class, $path);
```

- Stream as a download (controller action):

```php
return CSVExport::stream(UserCSVExporter::class, 'users.csv');
```

- Using Facade configuration overrides (Fluent API):

```php
return CSVExport::setChunkSize(2000)
    ->setMaxLimit(50000)
    ->includeBom(true)
    ->stream(UserCSVExporter::class, 'users.csv');
```

- Download after generating a temp file:

```php
return app(Elrayes\LaravelCsvExport\Services\CSVExportService::class)
    ->download(UserCSVExporter::class, 'users.csv');
```

- Store to a Laravel Storage disk (new):

```php
// Store on S3 at exports/users.csv
$stored = CSVExport::store(UserCSVExporter::class, 'exports/users.csv', 's3');
// returns 'exports/users.csv' if successful
```

## Configuration knobs

From BaseExporter (or set dynamically on your exporter instance):

- chunk size: how many rows to process per chunk
- max limit: cap total rows to export (null for unlimited)
- use max limit: toggle applying the cap
- include BOM: prepend UTF-8 BOM for Excel compatibility

Example:

```php
$exporter = app(UserCSVExporter::class)
    ->setChunkSize(2000)
    ->setMaxLimit(50000)
    ->setUseMaxLimit(true)
    ->setIncludeBom(true);

// When resolving via the Facade, you can bind a configured instance in the container
app()->bind(UserCSVExporter::class, fn () => $exporter);

CSVExport::stream(UserCSVExporter::class, 'users.csv');
```

## Error handling

Exceptions thrown during writing propagate after being reported via Laravel's exception handler (when using the app). Always wrap in try/catch at call sites if you want custom failure behavior.

## License

MIT © Elrayes


## Advanced typing (PHPDoc generics)

For better static analysis (e.g., with Larastan/PHPStan), the package exposes a generic type parameter for the row model passed to map().

- DataExporterInterface is declared as `@template TModel`
- BaseExporter implements `@implements DataExporterInterface<TModel>`
- map() is documented as `@param TModel $row`
- query() is documented as returning `Builder|Collection<int, TModel>|BuilderContract`

Example with a User model:

```php
use Elrayes\LaravelCsvExport\Exporters\BaseExporter;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends BaseExporter<User>
 */
class UserCSVExporter extends BaseExporter
{
    public function query(): Builder|Collection|BuilderContract
    {
        return User::query()->select(['id', 'name', 'email', 'created_at']);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Registered At'];
    }

    /** @param User $row */
    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email,
            optional($row->created_at)->toDateTimeString(),
        ];
    }
}
```
