<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Imports;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\ImportKitApplicabilityRowServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Applicability\Features\Import\Domain\Events\KitApplicabilityImportCompleted;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Applicability\Features\Import\Infrastructure\Imports\Mappers\KitApplicabilityImportRowMapper;
use App\Modules\Applicability\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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

final class KitApplicabilityImport implements KitApplicabilityImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private ?int $userId = null;

    private ?string $operationId = null;

    private ?ImportKitApplicabilityRowServiceInterface $service = null;

    private ?CacheFactory $cache = null;

    /**
     * Создает serializable queued Excel import adapter.
     *
     * Шаги:
     * 1. Принимает row service для синхронного запуска в текущем процессе.
     * 2. Принимает cache factory для накопления row failures.
     * 3. Сохраняет зависимости nullable, чтобы после queue serialization они могли быть резолвлены заново.
     */
    public function __construct(
        ImportKitApplicabilityRowServiceInterface $service,
        CacheFactory $cache,
    ) {
        $this->service = $service;
        $this->cache = $cache;
    }

    /**
     * Сериализует только scalar state queued import-а.
     *
     * Шаги:
     * 1. Сохраняет user id и operation id текущего import run.
     * 2. Сохраняет cache keys накопленных failures и lock.
     * 3. Не сериализует service/cache dependency graph.
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
     * Восстанавливает scalar state queued import-а после доставки worker-у.
     *
     * Шаги:
     * 1. Восстанавливает user id и operation id только если типы корректны.
     * 2. Сбрасывает service/cache зависимости, чтобы резолвить их в worker-time.
     * 3. Восстанавливает cache keys накопленных failures, если они были сериализованы.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->userId = is_int($data['userId'] ?? null) ? $data['userId'] : null;
        $this->operationId = is_string($data['operationId'] ?? null) ? $data['operationId'] : null;
        $this->service = null;
        $this->cache = null;

        if (is_string($data['cacheKey'] ?? null)) {
            $this->cacheKey = $data['cacheKey'];
        }

        if (is_string($data['lockKey'] ?? null)) {
            $this->lockKey = $data['lockKey'];
        }
    }

    /**
     * Запускает Laravel Excel import файла применяемости.
     *
     * Шаги:
     * 1. Сохраняет user id и operation id из run context для completion event.
     * 2. Формирует cache keys для накопления failures текущего operation id.
     * 3. Передает текущий import adapter в Laravel Excel с path и disk.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->userId = $context->userId;
        $this->operationId = $context->operationId;
        $this->cacheKey = sprintf(
            (string) config('applicability.import.failures.cache.keys.kit_applicability_import_failures'),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config('applicability.import.failures.cache.keys.kit_applicability_import_failures_lock'),
            $context->operationId,
        );

        Excel::import(
            import: $this,
            filePath: $path,
            disk: $disk,
        );
    }

    /**
     * Обрабатывает chunk строк XLSX и импортирует каждую непустую строку.
     *
     * Шаги:
     * 1. Резолвит row service лениво, чтобы queued worker получил свежие зависимости.
     * 2. Пропускает полностью пустые строки по первым трем колонкам.
     * 3. Передает строку в application service импорта применяемости.
     * 4. Конвертирует row validation exception в Laravel Excel failure с фактическим номером строки.
     */
    public function collection(Collection $collection): void
    {
        $service = $this->service();
        $rowMapper = new KitApplicabilityImportRowMapper;

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();

            if ($this->isEmptyRow($rowValues)) {
                continue;
            }

            try {
                $service->importFromRow($rowMapper->map($rowValues));
            } catch (ImportRowValidationException $exception) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'kit_id',
                    errors: [$exception->getMessage()],
                    values: $rowValues,
                ));
            }
        }
    }

    /**
     * Ограничивает импорт ожидаемыми листами ручного XLSX-файла применяемости.
     *
     * Шаги:
     * 1. Назначает текущий import adapter листу `Колодки`.
     * 2. Назначает тот же row contract листам фильтров.
     * 3. Возвращает mapping названий листов для Laravel Excel multi-sheet import.
     */
    public function sheets(): array
    {
        return [
            'Колодки' => $this,
            'Масляные фильтры' => $this,
            'Воздушные фильтры' => $this,
        ];
    }

    /**
     * Возвращает номер первой строки данных после заголовка.
     *
     * Шаги:
     * 1. Фиксирует пропуск первой строки XLSX.
     * 2. Возвращает номер, используемый Laravel Excel и failure row calculation.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Возвращает размер chunk-а для queued Excel import.
     *
     * Шаги:
     * 1. Ограничивает количество строк, обрабатываемых за один проход.
     * 2. Возвращает значение для Laravel Excel chunk reading.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Регистрирует сериализуемые Excel events для queued import.
     *
     * Шаги:
     * 1. Подписывает `AfterImport` на статический callable, а не closure.
     * 2. Возвращает mapping events для Laravel Excel.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Публикует domain event завершения import-а после обработки всех листов.
     *
     * Шаги:
     * 1. Получает исходный import adapter из Laravel Excel event.
     * 2. Берет user id, operation id и cache key накопленных failures.
     * 3. Публикует `KitApplicabilityImportCompleted` для reporting/cleanup listeners.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var KitApplicabilityImport $import */
        $import = $event->getConcernable();

        event(new KitApplicabilityImportCompleted(
            userId: $import->userId,
            cacheKey: $import->cacheKey,
            operationId: $import->operationId,
        ));
    }

    /**
     * Проверяет, что строка не содержит данных в обязательных колонках import-а.
     *
     * Шаги:
     * 1. Читает первые три колонки строки.
     * 2. Приводит значения к строкам и обрезает пробелы.
     * 3. Возвращает `true`, только если `ms_id`, `mod_id` и `kit_id` пустые.
     *
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return trim((string) ($row[0] ?? '')) === ''
            && trim((string) ($row[1] ?? '')) === ''
            && trim((string) ($row[2] ?? '')) === '';
    }

    /**
     * Возвращает cache factory для trait CachesImportFailures.
     *
     * Шаги:
     * 1. Возвращает уже сохраненный cache factory, если import создан в текущем процессе.
     * 2. После queue unserialize резолвит cache factory из container.
     */
    protected function cache(): CacheFactory
    {
        return $this->cache ??= app(CacheFactory::class);
    }

    /**
     * Возвращает row import service для worker-time обработки строк.
     *
     * Шаги:
     * 1. Возвращает constructor-injected service при синхронном запуске.
     * 2. После queue unserialize резолвит service из container.
     */
    private function service(): ImportKitApplicabilityRowServiceInterface
    {
        return $this->service ??= app(ImportKitApplicabilityRowServiceInterface::class);
    }
}
