<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Kit;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit\ImportKitFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\KitImportCompleted;
use App\Modules\Warehouse\Features\Import\Infrastructure\Traits\CachesImportFailures;
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
 * Импортирует Warehouse-наборы (Kit) из Excel: чанками, в очереди, с накоплением ошибок построчно
 * в cache. Резолв состава по артикулам и расчёт свойств набора делегированы Application-сервису.
 */
final class KitImport implements KitImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $runId = null;

    /**
     * Получает построчный сервис импорта наборов.
     */
    public function __construct(
        private readonly ImportKitFromRowServiceInterface $service,
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
                key: 'warehouse.import.failures.cache.keys.kit_import_failures',
            ),
            $context->runId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.kit_import_failures_lock',
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
                $this->service->importFromRow($rowValues);
            } catch (InvalidArgumentException $e) {
                $failure = new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'kit',
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
     * Возвращает размер чанка чтения наборов.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => fn () => event(new KitImportCompleted(
                userId: $this->userId,
                cacheKey: $this->cacheKey,
                runId: $this->runId,
            )),
        ];
    }
}
