<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCrossImportCompleted;
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
 * Excel-адаптер привязки двигателей к группам (механика): парсит коды из ячейки и на каждый код
 * зовёт сценарий назначения группы, транслируя его исход в отчёт об ошибках.
 *
 * @deprecated Фича группировки двигателей по кросс-кодам ещё на большой бизнес-доработке —
 *   правила группировки не финальны. Рабочий код, живой Rabbit-триггер, не удалять — просто не
 *   удивляться, если логика назначения группы поменяется целиком.
 */
final class EngineCrossImport implements EngineCrossImportInterface, ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithMultipleSheets, WithStartRow
{
    use CachesImportFailures;

    private const int GROUP_ID = 0;

    private const int CODES = 1;

    private ?ImportRunContextDTO $context = null;

    private ?AssignEngineGroupServiceInterface $service = null;

    /**
     * Подготовить импорт к сериализации в очередь.
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
     * Восстановить импорт после очереди.
     *
     * Шаги:
     * 1) Вернуть контекст запуска, если он был сериализован.
     * 2) Восстановить ключ списка ошибок.
     * 3) Восстановить ключ блокировки списка ошибок.
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
     * Запустить импорт кросс-групп двигателей.
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
     * Обработать пачку строк кросс-групп двигателей.
     *
     * Шаги:
     * 1) Пройти по строкам текущего чанка.
     * 2) Передать индекс строки и значения в обработчик одной строки.
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $this->processRow($indexRow, $row->toArray());
        }
    }

    /**
     * Обработать одну строку кросс-групп двигателей.
     *
     * Шаги:
     * 1) Прочитать group_id и ячейку с кодами двигателей; пустые строки пропустить.
     * 2) Разбить ячейку кодов и назначить группу каждому коду.
     * 3) Записать ошибку, если двигатель не найден или группа была переназначена.
     */
    private function processRow(int $indexRow, array $row): void
    {
        $groupId = isset($row[self::GROUP_ID]) && $row[self::GROUP_ID] !== '' ? (int) $row[self::GROUP_ID] : null;
        $rawCodes = isset($row[self::CODES]) ? (string) $row[self::CODES] : null;

        if (empty($groupId) || empty($rawCodes)) {
            return;
        }

        foreach ($this->parseCodes($rawCodes) as $code) {
            $result = $this->service()->assignGroup($code, $groupId);

            if (! $result->found) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'code_engine',
                    errors: ["Код двигателя '{$code}' не найден"],
                    values: ['group_id' => $groupId, 'code' => $code],
                ));

                continue;
            }

            if ($result->reassigned) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: 'group_id',
                    errors: ["Группа для '{$code}' изменена с {$result->previousGroupId} на {$groupId}"],
                    values: ['code' => $code, 'old_group' => $result->previousGroupId, 'new_group' => $groupId],
                ));
            }
        }
    }

    /**
     * Разобрать ячейку с несколькими кодами двигателей.
     *
     * Шаги:
     * 1) Разделить строку по точке с запятой.
     * 2) Обрезать пробелы вокруг каждого кода.
     * 3) Убрать пустые элементы.
     */
    private function parseCodes(string $rawCell): array
    {
        return array_filter(array_map('trim', explode(';', $rawCell)));
    }

    /**
     * Вернуть размер чанка импорта кросс-групп.
     *
     * Шаги:
     * 1) Зафиксировать размер пачки для построчного назначения групп.
     * 2) Вернуть значение, которое использует Laravel Excel.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Зарегистрировать событие завершения импорта кросс-групп.
     *
     * Шаги:
     * 1) Вернуть обработчик AfterImport как сериализуемую пару «класс/метод».
     * 2) Не использовать замыкание внутри импорта в очереди.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать доменное событие завершения импорта кросс-групп.
     *
     * Шаги:
     * 1) Получить импорт из события Laravel Excel и проверить его контекст.
     * 2) Взять пользователя, operation_id и ключ кеша ошибок.
     * 3) Отправить EngineCrossImportCompleted.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var EngineCrossImport $import */
        $import = $event->getConcernable();
        $context = $import->context();

        event(new EngineCrossImportCompleted(
            userId: $context->userId,
            cacheKey: $import->cacheKey,
            operationId: $context->operationId,
        ));
    }

    /**
     * Вернуть номер первой строки данных импорта кросс-групп.
     *
     * Шаги:
     * 1) Не пропускать строки, потому что файл ожидается без заголовка.
     * 2) Начать чтение с первой строки.
     */
    public function startRow(): int
    {
        return 1;
    }

    /**
     * Ограничить импорт первым листом файла кросс-групп.
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
     * Получить сервис назначения группы.
     *
     * Шаги:
     * 1) Лениво получить сервис из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function service(): AssignEngineGroupServiceInterface
    {
        return $this->service ??= app(AssignEngineGroupServiceInterface::class);
    }

    /**
     * Получить обязательный контекст импорта кросс-групп.
     *
     * Шаги:
     * 1) Вернуть сохранённый контекст запуска.
     * 2) Выбросить LogicException, если import пытаются завершить без инициализации.
     */
    private function context(): ImportRunContextDTO
    {
        return $this->context ?? throw new LogicException('Engine cross import context is not initialized.');
    }
}
