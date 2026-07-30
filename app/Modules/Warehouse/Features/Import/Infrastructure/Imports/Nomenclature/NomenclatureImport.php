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
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\WarehouseImportException;
use App\Modules\Warehouse\Features\Import\Infrastructure\Traits\CachesImportFailures;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
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
 * Импортирует Warehouse-номенклатуру из Excel: чанками, в очереди, с накоплением ошибок построчно
 * в cache. Резолв type/brand и сборка details делегированы Application-сервису.
 *
 * Экспортные файлы содержат второй, служебный лист "Справочники" (списки для выпадающих списков
 * Excel) — без WithMultipleSheets Laravel Excel гоняет collection() по КАЖДОМУ листу книги, и
 * строки справочника ошибочно пытаются распарситься как номенклатура. sheets() явно ограничивает
 * импорт первым листом (индекс 0) — там всегда реальные данные, справочник игнорируется.
 */
final class NomenclatureImport implements NomenclatureImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $operationId = null;

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
     * 1) Сохранить userId/operationId контекста и вычислить cache-ключи по operationId.
     * 2) Передать себя в Excel::import — чанки будут обработаны в очереди (ShouldQueue).
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->userId = $context->userId;
        $this->operationId = $context->operationId;
        $this->cacheKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.nomenclature_import_failures',
            ),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config(
                key: 'warehouse.import.failures.cache.keys.nomenclature_import_failures_lock',
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
                ? $this->nomenclatures->findById($id) !== null
                : ($partNumber !== '' && $this->nomenclatures->findByPartNumber($partNumber) !== null);

            try {
                $nomenclature = $this->service->importFromRow($rowValues, $types, $brands);
                $this->dispatchNomenclatureMutationEvent($nomenclature->toArray(), $wasExisting);
            } catch (WarehouseImportException|DetailsDataBuildException $e) {
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
     * Ограничивает импорт первым листом файла — второй лист "Справочники" (выпадающие списки
     * Excel) не относится к данным и не должен парситься как номенклатура.
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
            AfterImport::class => fn () => event(new NomenclatureImportCompleted(
                userId: $this->userId,
                cacheKey: $this->cacheKey,
                operationId: $this->operationId,
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
        $operationId = $this->operationId ?? 'warehouse-nomenclature-import';

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
