<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers\ManufacturerSheetRowMapper;
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
 * Excel/CSV-адаптер импорта производителей (mfa_id, name, provider) из внешнего файла:
 * читает файл по чанкам и на каждую строку зовёт построчный сценарий. Бизнес-логика строки —
 * в UpsertManufacturerFromSheetService.
 */
final class ManufacturerImport implements ManufacturerImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?ImportRunContextDTO $context = null;

    private ?UpsertManufacturerFromSheetServiceInterface $service = null;

    private ?ManufacturerSheetRowMapper $rowMapper = null;

    /**
     * Получить зависимости для внешнего импорта производителей.
     *
     * Шаги:
     * 1) Принять сервис сохранения производителя из внешнего листа.
     * 2) Принять маппер строки с mfa_id, name и provider.
     * 3) Сохранить зависимости до сериализации задания очереди.
     */
    public function __construct(
        UpsertManufacturerFromSheetServiceInterface $service,
        ManufacturerSheetRowMapper $rowMapper,
    ) {
        $this->service = $service;
        $this->rowMapper = $rowMapper;
    }

    /**
     * Подготовить import производителей к сериализации в очередь.
     *
     * Шаги:
     * 1) Сохранить контекст запуска импорта.
     * 2) Сохранить ключ списка ошибок и ключ блокировки.
     * 3) Не сериализовать сервис и маппер строки.
     *
     * @return array<string, mixed>
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
     * Восстановить import производителей после очереди.
     *
     * Шаги:
     * 1) Вернуть контекст запуска, если он был сериализован.
     * 2) Сбросить сервис и маппер для последующего резолва из контейнера.
     * 3) Восстановить ключи отчёта ошибок.
     *
     * @param  array<string, mixed>  $data
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
     * Запустить внешний импорт производителей.
     *
     * Шаги:
     * 1) Сохранить контекст запуска и рассчитать ключи отчёта ошибок.
     * 2) Передать текущий адаптер в Laravel Excel.
     * 3) Прочитать файл с указанного диска или с диска по умолчанию.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        $this->cacheKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.manufacturer_import_failures'),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.manufacturer_import_failures_lock'),
            $context->operationId,
        );
        Excel::import($this, $path, $disk);
    }

    /**
     * Обработать пачку строк внешнего импорта производителей.
     *
     * Шаги:
     * 1) Получить маппер и сервис записи после возможного восстановления из очереди.
     * 2) Для каждой строки собрать DTO производителя и вызвать сервис сохранения.
     * 3) Записать ошибку в cache-отчёт, если строка не прошла import validation.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $manufacturerRow = $rowMapper->map($rowValues);
                $service->upsertFromRow($manufacturerRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure(
                    row: $index + $this->startRow(),
                    attribute: 'Производитель',
                    errors: [$e->getMessage()],
                    values: $rowValues,
                ));
            }
        }
    }

    /**
     * Вернуть размер чанка внешнего импорта производителей.
     *
     * Шаги:
     * 1) Зафиксировать размер пачки для построчной записи.
     * 2) Вернуть значение, которое использует Laravel Excel.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Зарегистрировать событие завершения внешнего импорта производителей.
     *
     * Шаги:
     * 1) Вернуть обработчик AfterImport как сериализуемую пару class/method.
     * 2) Не использовать closure внутри queued import.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать доменное событие завершения внешнего импорта производителей.
     *
     * Шаги:
     * 1) Получить import из события Laravel Excel и проверить его контекст.
     * 2) Взять пользователя, operation_id и cache key ошибок.
     * 3) Отправить ManufacturerImportCompleted.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var ManufacturerImport $import */
        $import = $event->getConcernable();
        $context = $import->context();

        event(new ManufacturerImportCompleted(
            userId: $context->userId,
            cacheKey: $import->cacheKey,
            operationId: $context->operationId,
        ));
    }

    /**
     * Вернуть номер первой строки данных внешнего импорта производителей.
     *
     * Шаги:
     * 1) Пропустить строку заголовков Excel.
     * 2) Начать чтение со второй строки.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Ограничить импорт первым листом файла производителей.
     *
     * Шаги:
     * 1) Вернуть текущий объект как обработчик нулевого листа.
     * 2) Игнорировать остальные листы книги.
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    /**
     * Получить сервис сохранения производителя.
     *
     * Шаги:
     * 1) Вернуть уже переданный сервис, если import не проходил через очередь.
     * 2) Иначе резолвить сервис из контейнера во время обработки.
     */
    private function service(): UpsertManufacturerFromSheetServiceInterface
    {
        return $this->service ??= app(UpsertManufacturerFromSheetServiceInterface::class);
    }

    /**
     * Получить маппер внешней строки производителя.
     *
     * Шаги:
     * 1) Вернуть уже переданный маппер, если import не проходил через очередь.
     * 2) Иначе резолвить маппер из контейнера во время обработки.
     */
    private function rowMapper(): ManufacturerSheetRowMapper
    {
        return $this->rowMapper ??= app(ManufacturerSheetRowMapper::class);
    }

    /**
     * Получить обязательный контекст внешнего импорта производителей.
     *
     * Шаги:
     * 1) Вернуть сохранённый контекст запуска.
     * 2) Выбросить LogicException, если import пытаются завершить без инициализации.
     */
    private function context(): ImportRunContextDTO
    {
        return $this->context ?? throw new LogicException('Manufacturer import context is not initialized.');
    }
}
