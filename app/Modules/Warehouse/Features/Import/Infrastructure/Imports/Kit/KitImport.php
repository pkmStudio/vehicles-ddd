<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Kit;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit\ImportKitFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\KitImportCompleted;
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
 * Импортирует Warehouse-наборы (Kit) из Excel: чанками, в очереди, с накоплением ошибок построчно
 * в cache. Резолв состава по артикулам и расчёт свойств набора делегированы Application-сервису.
 *
 * Экспортные файлы содержат второй, служебный лист "Справочники" (см. KitExport) — без
 * WithMultipleSheets Laravel Excel гоняет collection() по КАЖДОМУ листу книги. sheets() явно
 * ограничивает импорт первым листом (индекс 0), справочник игнорируется.
 */
final class KitImport implements KitImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $operationId = null;

    /**
     * Получает построчный сервис импорта наборов.
     */
    public function __construct(
        private readonly ImportKitFromRowServiceInterface $service,
    ) {}

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
                key: 'warehouse.import.failures.cache.keys.kit_import_failures',
            ),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.kit_import_failures_lock',
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
        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            try {
                $this->service->importFromRow($rowValues);
            } catch (WarehouseImportException $e) {
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
        /** @var KitImport $import */
        $import = $event->getConcernable();

        event(new KitImportCompleted(
            userId: $import->userId,
            cacheKey: $import->cacheKey,
            operationId: $import->operationId,
        ));
    }
}
