<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
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
 * Excel-адаптер импорта свечей по модификациям (механика): парсит ms_id/mod_id, собирает details
 * по шаблону и на каждую строку зовёт сценарий записи свечей двигателям модификации,
 * транслируя его исход (не найдено / пропущенные двигатели) в отчёт об ошибках.
 */
final class EngineSparkPlugSpecificationImport implements EngineSparkPlugSpecificationImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private const int MS_ID = 0;

    private const int MOD_ID = 1;

    private const int SPEC_START_COLUMN = 2;

    private ?ImportRunContextDTO $context = null;

    private ?UpsertSparkPlugSpecByModificationServiceInterface $service = null;

    private ?TemplatesClientInterface $templates = null;

    /**
     * Подготовить import свечей к сериализации в очередь.
     *
     * Шаги:
     * 1) Сохранить контекст запуска импорта.
     * 2) Сохранить ключ списка ошибок и ключ блокировки.
     * 3) Оставить runtime-зависимости для ленивого получения из контейнера.
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
     * Восстановить import свечей после очереди.
     *
     * Шаги:
     * 1) Вернуть контекст запуска, если он был сериализован.
     * 2) Восстановить ключи отчёта ошибок.
     * 3) Оставить сервис и клиент Templates для ленивого получения из контейнера.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $context = $data['context'] ?? null;
        $this->context = $context instanceof ImportRunContextDTO ? $context : null;

        if (is_string($data['cacheKey'] ?? null)) {
            $this->cacheKey = $data['cacheKey'];
        }

        if (is_string($data['lockKey'] ?? null)) {
            $this->lockKey = $data['lockKey'];
        }
    }

    /**
     * Запустить импорт свечей по модификациям.
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
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures'),
            $context->operationId,
        );
        $this->lockKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures_lock'),
            $context->operationId,
        );
        Excel::import($this, $path, $disk);
    }

    /**
     * Обработать пачку строк свечей по модификациям.
     *
     * Шаги:
     * 1) Проверить, что строка содержит числовые ms_id и mod_id.
     * 2) Собрать details свечей через Templates и сохранить спецификацию для модификации.
     * 3) Записать failures для невалидных строк, ненайденных модификаций и пропущенных двигателей.
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $msId = $row[self::MS_ID] ?? null;
            $modId = $row[self::MOD_ID] ?? null;

            if (! is_numeric($msId) || ! is_numeric($modId)) {
                $this->onFailure(new Failure(
                    row: $rowNumber,
                    attribute: 'ms_id/mod_id',
                    errors: ['Строка должна содержать числовые ms_id и mod_id.'],
                    values: $row->toArray(),
                ));

                continue;
            }

            try {
                $details = $this->templates()->buildVehicleDetails(
                    template: DetailTemplateEnum::SPARK_PLUGS,
                    row: $row->toArray(),
                    startIndex: self::SPEC_START_COLUMN,
                );
                $result = $this->service()->upsertByModification((int) $msId, (int) $modId, $details);

                if (! $result->found) {
                    $this->onFailure(new Failure($rowNumber, 'Свечи', [$result->notFoundReason], $row->toArray()));

                    continue;
                }

                foreach ($result->skippedEngines as $skipped) {
                    $this->onFailure(new Failure(
                        $rowNumber,
                        'Двигатель',
                        ["Двигатель {$skipped['code']} (топливо: {$skipped['fuel']}) не нуждается в свечах."],
                        $row->toArray(),
                    ));
                }
            } catch (DetailsDataBuildException $e) {
                $this->onFailure(new Failure($rowNumber, 'Свечи', [$e->getMessage()], $row->toArray()));
            }
        }
    }

    /**
     * Вернуть размер чанка импорта свечей по модификациям.
     *
     * Шаги:
     * 1) Зафиксировать размер пачки для построчной записи спецификаций.
     * 2) Вернуть значение, которое использует Laravel Excel.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Зарегистрировать событие завершения импорта свечей.
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
     * Опубликовать доменное событие завершения импорта двигателей.
     *
     * Шаги:
     * 1) Получить import из события Laravel Excel и проверить его контекст.
     * 2) Взять пользователя, operation_id и cache key ошибок.
     * 3) Отправить EngineImportCompleted.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var EngineSparkPlugSpecificationImport $import */
        $import = $event->getConcernable();
        $context = $import->context();

        event(new EngineImportCompleted(
            userId: $context->userId,
            cacheKey: $import->cacheKey,
            operationId: $context->operationId,
        ));
    }

    /**
     * Вернуть номер первой строки данных импорта свечей.
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
     * Ограничить импорт первым листом файла свечей.
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
     * Получить сервис сохранения свечей по модификации.
     *
     * Шаги:
     * 1) Лениво получить сервис из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function service(): UpsertSparkPlugSpecByModificationServiceInterface
    {
        return $this->service ??= app(UpsertSparkPlugSpecByModificationServiceInterface::class);
    }

    /**
     * Получить клиент Templates для сборки details.
     *
     * Шаги:
     * 1) Лениво получить клиент из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function templates(): TemplatesClientInterface
    {
        return $this->templates ??= app(TemplatesClientInterface::class);
    }

    /**
     * Получить обязательный контекст импорта свечей.
     *
     * Шаги:
     * 1) Вернуть сохранённый контекст запуска.
     * 2) Выбросить LogicException, если import пытаются завершить без инициализации.
     */
    private function context(): ImportRunContextDTO
    {
        return $this->context ?? throw new LogicException('Engine spark plug import context is not initialized.');
    }
}
