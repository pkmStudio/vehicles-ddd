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
     * Сериализует queued import adapter без service dependency graph.
     * Шаги:
     * 1) Сохранить userId и operationId текущего import run.
     * 2) Сохранить cache/lock keys failure store-а.
     * 3) Оставить runtime-зависимости для ленивого получения из container.
     *
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
     * Восстанавливает queued import adapter после Laravel queue unserialize.
     * Шаги:
     * 1) Восстановить scalar context только если значения имеют ожидаемый тип.
     * 2) Вернуть cache key для накопленных row failures.
     * 3) Вернуть lock key для накопленных row failures.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->userId = is_int($data['userId'] ?? null) ? $data['userId'] : null;
        $this->operationId = is_string($data['operationId'] ?? null) ? $data['operationId'] : null;

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
     * Шаги:
     * 1) Получить Application-сервис через lazy service().
     * 2) Для каждой Excel-строки привести row к plain array.
     * 3) Передать строку в импорт упаковочного размера.
     * 4) Превратить WarehouseImportException в Failure с Excel row number и атрибутом pack_dimension.
     * 5) Сохранить failure в cache и продолжить обработку chunk.
     */
    public function collection(Collection $collection): void
    {
        $service = $this->service();
        $rowMapper = new PackDimensionImportRowMapper;

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            try {
                $service->importFromRow($rowMapper->map($rowValues));
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
     * Шаги:
     * 1) Вернуть 2, потому что первая строка Excel-листа содержит headings.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Возвращает размер чанка чтения упаковочных размеров.
     * Шаги:
     * 1) Вернуть 500 как умеренный chunk size для справочника упаковочных размеров.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Ограничивает импорт первым листом файла — второй лист "Справочники" не относится к данным.
     * Шаги:
     * 1) Связать sheet index 0 с текущим PackDimensionImport adapter-ом.
     * 2) Не возвращать adapters для служебного листа справочников.
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
     * Регистрирует serializable Laravel Excel event handlers.
     * Шаги:
     * 1) Привязать AfterImport к static callable.
     * 2) Избежать closure, чтобы queued import оставался сериализуемым.
     *
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
     * Шаги:
     * 1) Получить PackDimensionImport instance из AfterImport concernable.
     * 2) Взять userId/cacheKey/operationId из restored import context.
     * 3) Опубликовать PackDimensionImportCompleted для отчетности и notification flow.
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

    /**
     * Возвращает Application-сервис импорта упаковочных размеров в текущем worker-е.
     * Шаги:
     * 1) Лениво получить ImportPackDimensionFromRowServiceInterface из container.
     * 2) Закешировать resolved service на время обработки chunk.
     */
    private function service(): ImportPackDimensionFromRowServiceInterface
    {
        return $this->service ??= app(ImportPackDimensionFromRowServiceInterface::class);
    }
}
