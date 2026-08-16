<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ModificationCatalogImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Modification\ModificationImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use LogicException;
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
 * Excel-адаптер внешнего импорта каталога модификаций.
 */
final class ModificationCatalogImport implements ModificationCatalogImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?ImportRunContextDTO $context = null;

    private ?UpsertModificationFromSheetServiceInterface $service = null;

    private ?ModificationSheetRowMapper $rowMapper = null;

    /**
     * Подготовить adapter к сериализации в очередь.
     *
     * @return array{context: ?ImportRunContextDTO, cacheKey: ?string, lockKey: ?string}
     */
    public function __serialize(): array
    {
        return [
            'context' => $this->context,
            'cacheKey' => $this->cacheKey ?? null,
            'lockKey' => $this->lockKey ?? null,
        ];
    }

    /**
     * Восстановить adapter после очереди.
     *
     * @param  array{context?: ImportRunContextDTO|null, cacheKey?: string|null, lockKey?: string|null}  $data
     */
    public function __unserialize(array $data): void
    {
        $context = $data['context'] ?? null;
        $this->context = $context instanceof ImportRunContextDTO ? $context : null;
        $this->service = null;
        $this->rowMapper = null;

        if (is_string($data['cacheKey'] ?? null)) {
            $this->cacheKey = $data['cacheKey'];
        }

        if (is_string($data['lockKey'] ?? null)) {
            $this->lockKey = $data['lockKey'];
        }
    }

    /**
     * Запустить внешний импорт каталога модификаций.
     *
     * Шаги:
     * 1) Сохранить context и cache keys отчета ошибок.
     * 2) Передать текущий adapter в Laravel Excel.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf((string) config('vehicles.import.failures.cache.keys.modification_import_failures'), $context->operationId);
        $this->lockKey = sprintf((string) config('vehicles.import.failures.cache.keys.modification_import_failures_lock'), $context->operationId);

        Excel::import($this, $path, $disk);
    }

    /**
     * Обработать пачку строк каталога модификаций.
     *
     * Шаги:
     * 1) Лениво получить mapper и service из контейнера.
     * 2) Для каждой строки собрать DTO и выполнить manager upsert.
     * 3) Записать ошибки строки в общий cache-отчет.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $service->upsertFromRow($rowMapper->map($rowValues));
            } catch (ImportRowValidationException|ImportRowReferenceNotFoundException $e) {
                $this->onFailure(new Failure(
                    row: $index + $this->startRow(),
                    attribute: 'Модификация',
                    errors: $e->errors(),
                    values: $rowValues,
                ));
            }
        }
    }

    /**
     * Вернуть размер чанка внешнего импорта модификаций.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Зарегистрировать сериализуемый handler завершения импорта.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать событие завершения импорта модификаций.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var ModificationCatalogImport $import */
        $import = $event->getConcernable();
        $context = $import->context();

        event(new ModificationImportCompleted(
            userId: $context->userId,
            cacheKey: $import->cacheKey,
            operationId: $context->operationId,
        ));
    }

    /**
     * Вернуть первую строку данных.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Ограничить импорт первым листом workbook-а.
     *
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * Получить service обработки строки.
     */
    private function service(): UpsertModificationFromSheetServiceInterface
    {
        return $this->service ??= app(UpsertModificationFromSheetServiceInterface::class);
    }

    /**
     * Получить mapper строки.
     */
    private function rowMapper(): ModificationSheetRowMapper
    {
        return $this->rowMapper ??= app(ModificationSheetRowMapper::class);
    }

    /**
     * Получить обязательный context запуска импорта.
     */
    private function context(): ImportRunContextDTO
    {
        return $this->context ?? throw new LogicException('Modification catalog import context is not initialized.');
    }
}
