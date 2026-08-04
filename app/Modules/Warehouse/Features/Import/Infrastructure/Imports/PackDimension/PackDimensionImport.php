<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension\ImportPackDimensionFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\PackDimensionImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\WarehouseImportException;
use App\Modules\Warehouse\Features\Import\Infrastructure\Traits\CachesImportFailures;
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
 * Импортирует упаковочные размеры Warehouse из Excel: чанками, в очереди, с накоплением ошибок
 * построчно в cache. Валидация и запись делегированы Application-сервису.
 *
 * Экспортные файлы содержат второй, служебный лист "Справочники" (см. PackDimensionExport) — без
 * WithMultipleSheets Laravel Excel гоняет collection() по КАЖДОМУ листу книги. sheets() явно
 * ограничивает импорт первым листом (индекс 0), справочник игнорируется.
 */
final class PackDimensionImport implements PackDimensionImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $operationId = null;

    private ?ImportPackDimensionFromRowServiceInterface $service = null;

    /**
     * Получает построчный сервис импорта упаковочных размеров.
     */
    public function __construct(
        ImportPackDimensionFromRowServiceInterface $service,
    ) {
        $this->service = $service;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'userId' => $this->userId,
            'operationId' => $this->operationId,
            'cacheKey' => $this->cacheKey ?? null,
            'lockKey' => $this->lockKey ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->userId = is_int($data['userId'] ?? null) ? $data['userId'] : null;
        $this->operationId = is_string($data['operationId'] ?? null) ? $data['operationId'] : null;
        $this->service = null;

        if (is_string($data['cacheKey'] ?? null)) {
            $this->cacheKey = $data['cacheKey'];
        }

        if (is_string($data['lockKey'] ?? null)) {
            $this->lockKey = $data['lockKey'];
        }
    }

    /**
     * Этот метод запускает Excel-импорт файла в рамках прогона, описанного контекстом.
     * Шаги:
     * 1) Сохранить userId/operationId контекста и вычислить cache-ключи по operationId.
     * 2) Передать себя в Excel::import — чанки будут обработаны в очереди (ShouldQueue).
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->userId = $context->userId;
        $this->operationId = $context->operationId;
        $this->cacheKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.pack_dimension_import_failures',
            ),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.pack_dimension_import_failures_lock',
            ),
            $context->operationId,
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
        $service = $this->service();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            try {
                $service->importFromRow($rowValues);
            } catch (WarehouseImportException $e) {
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
     * Ограничивает импорт первым листом файла — второй лист "Справочники" не относится к данным.
     *
     * @return array<int, ToCollection>
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Диспатчит событие завершения импорта после обработки queued chunks.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var PackDimensionImport $import */
        $import = $event->getConcernable();

        event(new PackDimensionImportCompleted(
            userId: $import->userId,
            cacheKey: $import->cacheKey,
            operationId: $import->operationId,
        ));
    }

    private function service(): ImportPackDimensionFromRowServiceInterface
    {
        return $this->service ??= app(ImportPackDimensionFromRowServiceInterface::class);
    }
}
