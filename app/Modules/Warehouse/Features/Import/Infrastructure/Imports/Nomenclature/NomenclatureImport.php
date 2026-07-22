<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature\ImportNomenclatureFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Infrastructure\Traits\CachesImportFailures;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
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
        private readonly ImportNomenclatureFromRowServiceInterface $service,
        private readonly TypeRepositoryInterface $types,
        private readonly BrandRepositoryInterface $brands,
        private readonly NomenclatureRepositoryInterface $nomenclatures,
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
            $id = isset($rowValues[0]) && trim((string) $rowValues[0]) !== '' ? (int) trim((string) $rowValues[0]) : null;
            $partNumber = trim((string) ($rowValues[5] ?? ''));
            $wasExisting = $id !== null
                || ($partNumber !== '' && $this->nomenclatures->findByPartNumber($partNumber) !== null);

            try {
                $nomenclature = $this->service->importFromRow($rowValues, $types, $brands);
                $this->dispatchNomenclatureMutationEvent($nomenclature->toArray(), $wasExisting);
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

    /**
     * Диспатчит публичный факт изменения номенклатуры для внешних фич, например MoySklad.
     *
     * @param  array<string, mixed>  $nomenclature
     */
    private function dispatchNomenclatureMutationEvent(array $nomenclature, bool $wasExisting): void
    {
        $userId = $this->userId ?? 0;
        $operationId = $this->runId ?? 'warehouse-nomenclature-import';

        if ($wasExisting) {
            event(new NomenclatureUpdated(
                userId: $userId,
                operationId: $operationId,
                nomenclature: $nomenclature,
            ));

            return;
        }

        event(new NomenclatureCreated(
            userId: $userId,
            operationId: $operationId,
            nomenclature: $nomenclature,
        ));
    }
}
