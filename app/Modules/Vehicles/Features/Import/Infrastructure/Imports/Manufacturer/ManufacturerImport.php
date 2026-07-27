<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers\ManufacturerSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel/CSV-адаптер импорта производителей (mfa_id, name, provider) из внешнего файла:
 * читает файл по чанкам и на каждую строку зовёт построчный сценарий. Бизнес-логика строки —
 * в UpsertManufacturerFromSheetService.
 */
final class ManufacturerImport implements ManufacturerImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    public ImportRunContextDTO $context;

    public function __construct(
        private readonly UpsertManufacturerFromSheetServiceInterface $service,
        private readonly ManufacturerSheetRowMapper $rowMapper,
    ) {}

    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.manufacturer_import_failures'),
            $context->runId,
        );
        $this->lockKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.manufacturer_import_failures_lock'),
            $context->runId,
        );
        Excel::import($this, $path, $disk);
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $manufacturerRow = $this->rowMapper->map($rowValues);
                $this->service->upsertFromRow($manufacturerRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure(
                    row: $index + $this->startRow(),
                    attribute: 'Производитель',
                    errors: [$e->getMessage()],
                    values: $rowValues,
                ));
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    public static function afterImport(AfterImport $event): void
    {
        /** @var ManufacturerImport $import */
        $import = $event->getConcernable();

        event(new ManufacturerImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey,
            runId: $import->context->runId,
        ));
    }

    public function startRow(): int
    {
        return 2;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
}
