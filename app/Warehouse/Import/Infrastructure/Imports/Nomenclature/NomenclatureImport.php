<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Imports\Nomenclature;

use App\Warehouse\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Services\Nomenclature\UpsertNomenclatureFromRowServiceInterface;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use App\Warehouse\Import\Domain\Events\NomenclatureImportCompleted;
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
use RuntimeException;

/**
 * Импортирует Warehouse-номенклатуру из Excel: чанками, в очереди, с накоплением ошибок построчно
 * в cache. Резолв type/brand и сборка details делегированы Application-сервису.
 */
final class NomenclatureImport implements NomenclatureImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $runId = null;

    /**
     * Получает построчный сервис импорта и справочники типов/брендов.
     */
    public function __construct(
        private readonly UpsertNomenclatureFromRowServiceInterface $service,
        private readonly TypeRepositoryInterface $types,
        private readonly BrandRepositoryInterface $brands,
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
                key: 'warehouse.import.failures.cache.keys.nomenclature_import_failures',
            ),
            $context->runId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.nomenclature_import_failures_lock',
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
     * Этот метод обрабатывает один чанк строк.
     * Шаги:
     * 1) Предзагрузить справочники типов и брендов один раз на чанк.
     * 2) На каждой строке вызвать Application-сервис, ошибки — в cache через onFailure.
     */
    public function collection(Collection $collection): void
    {
        $types = $this->types->all();
        $brands = $this->brands->all();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            $partNumber = trim((string) ($rowValues[5] ?? ''));

            try {
                $this->service->upsertFromRow($rowValues, $types, $brands);
            } catch (InvalidArgumentException|RuntimeException $e) {
                $failure = new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: "артикул {$partNumber}",
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
     * Возвращает размер чанка чтения номенклатуры.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => fn () => event(new NomenclatureImportCompleted(
                userId: $this->userId,
                cacheKey: $this->cacheKey,
                runId: $this->runId,
            )),
        ];
    }
}
