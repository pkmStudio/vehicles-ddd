<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Imports\PackDimension;

use App\Warehouse\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Warehouse\Import\Domain\Contracts\Services\PackDimension\UpsertPackDimensionFromRowServiceInterface;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use App\Warehouse\Import\Domain\Events\PackDimensionImportCompleted;
use App\Warehouse\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Импортирует упаковочные размеры Warehouse из Excel: чанками, в очереди, с накоплением ошибок
 * построчно в cache. Валидация и запись делегированы Application-сервису.
 */
final class PackDimensionImport implements PackDimensionImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $runId = null;

    /**
     * Получает построчный сервис импорта упаковочных размеров.
     */
    public function __construct(
        private readonly UpsertPackDimensionFromRowServiceInterface $service,
    ) {}

    /**
     * Этот метод запускает Excel-импорт файла в рамках прогона, описанного контекстом.
     * Шаги:
     * 1) Сохранить userId/runId контекста и вычислить cache-ключи по runId.
     * 2) Передать себя в Excel::import — чанки будут обработаны в очереди (ShouldQueue).
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->userId = $context->userId;
        $this->runId = $context->runId;
        $this->cacheKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.pack_dimension_import_failures',
            ),
            $context->runId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.pack_dimension_import_failures_lock',
            ),
            $context->runId,
        );

        Excel::import(
            import: $this,
            filePath: $path,
            disk: $disk,
        );
    }

    /**
     * Этот метод обрабатывает один чанк строк, ошибки — в cache через onFailure.
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            try {
                $this->service->upsertFromRow($rowValues);
            } catch (InvalidArgumentException $e) {
                $failure = new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'pack_dimension',
                    errors: [$e->getMessage()],
                    values: $rowValues,
                );

                $this->onFailure($failure);
            }
        }
    }

    /**
     * Возвращает номер первой строки с данными, пропуская заголовок.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Возвращает размер чанка чтения упаковочных размеров.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => fn () => event(new PackDimensionImportCompleted(
                userId: $this->userId,
                cacheKey: $this->cacheKey,
                runId: $this->runId,
            )),
        ];
    }
}
