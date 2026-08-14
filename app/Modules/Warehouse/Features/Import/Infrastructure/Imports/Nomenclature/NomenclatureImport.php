<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature;

use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature\ImportNomenclatureFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\WarehouseImportException;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature\Mappers\NomenclatureImportRowMapper;
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

    private ?ImportNomenclatureFromRowServiceInterface $service = null;

    private ?TypeRepositoryInterface $types = null;

    private ?BrandRepositoryInterface $brands = null;

    /**
     * Сериализует только scalar context queued import adapter-а.
     * Шаги:
     * 1) Не хранить service/repository dependency graph в состоянии adapter-а.
     * 2) Сохранить userId и operationId, нужные для события завершения.
     * 3) Сохранить cache/lock keys, через которые CachesImportFailures накопил ошибки.
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
     * Восстанавливает scalar context после десериализации queued import adapter-а.
     * Шаги:
     * 1) Принять только значения ожидаемых scalar типов.
     * 2) Вернуть userId/operationId для worker-time обработки строк и события завершения.
     * 3) Вернуть cache/lock keys, если они были сохранены при serialize().
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
     * 2) Для каждой строки привести Laravel Excel row к plain array.
     * 3) Передать строку, справочники и import context в Application-сервис.
     * 4) Превратить ожидаемые ошибки парсинга/details в Failure с номером Excel-строки.
     * 5) Сохранить failure в cache через onFailure(), не останавливая весь import chunk.
     */
    public function collection(Collection $collection): void
    {
        $types = $this->types()->all();
        $brands = $this->brands()->all();
        $rowMapper = new NomenclatureImportRowMapper;

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            $partNumber = trim((string) ($rowValues[5] ?? ''));

            try {
                $this->service()->importFromRow(
                    row: $rowMapper->map($rowValues),
                    types: $types,
                    brands: $brands,
                    userId: $this->userId,
                    operationId: $this->operationId,
                );
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
     * Шаги:
     * 1) Вернуть фиксированное значение 2, потому что первая строка файла занята headings.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Возвращает размер чанка чтения номенклатуры.
     * Шаги:
     * 1) Вернуть chunk size 1000 как баланс между количеством jobs и памятью worker-а.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Ограничивает импорт первым листом файла — второй лист "Справочники" (выпадающие списки
     * Excel) не относится к данным и не должен парситься как номенклатура.
     * Шаги:
     * 1) Связать sheet index 0 с текущим import adapter-ом.
     * 2) Не возвращать adapters для остальных листов, чтобы справочники не попали в collection().
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
     * Регистрирует serializable Laravel Excel events для queued import.
     * Шаги:
     * 1) Привязать AfterImport к static callable без closure.
     * 2) Оставить dispatch завершения до окончания всех queued chunks.
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
     * 1) Получить текущий import adapter из Laravel Excel event.
     * 2) Прочитать восстановленный userId/cacheKey/operationId.
     * 3) Опубликовать NomenclatureImportCompleted для reporting/notification listeners.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var NomenclatureImport $import */
        $import = $event->getConcernable();

        event(new NomenclatureImportCompleted(
            userId: $import->userId,
            cacheKey: $import->cacheKey,
            operationId: $import->operationId,
        ));
    }

    /**
     * Возвращает построчный Application-сервис, резолвя его в worker-е при необходимости.
     * Шаги:
     * 1) Лениво получить сервис из container.
     * 2) Закешировать resolved instance в свойстве adapter-а на время обработки.
     * 3) Закешировать resolved instance в свойстве adapter-а.
     */
    private function service(): ImportNomenclatureFromRowServiceInterface
    {
        return $this->service ??= app(ImportNomenclatureFromRowServiceInterface::class);
    }

    /**
     * Возвращает repository типов для preload на chunk.
     * Шаги:
     * 1) Лениво получить TypeRepositoryInterface из Laravel container.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function types(): TypeRepositoryInterface
    {
        return $this->types ??= app(TypeRepositoryInterface::class);
    }

    /**
     * Возвращает repository брендов для preload на chunk.
     * Шаги:
     * 1) Лениво получить BrandRepositoryInterface из Laravel container.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function brands(): BrandRepositoryInterface
    {
        return $this->brands ??= app(BrandRepositoryInterface::class);
    }
}
