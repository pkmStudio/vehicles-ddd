<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers\EngineMainSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер основного листа двигателей (механика): сопоставляет колонки Excel
 * с EngineSheetRowDTO и на каждую строку зовёт обычный сценарий upsert двигателя.
 */
final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private ?UpsertEngineFromRowServiceInterface $service = null;

    private ?EngineMainSheetRowMapper $rowMapper = null;

    /**
     * Получить ключи отчёта ошибок для основного листа двигателей.
     *
     * Шаги:
     * 1) Принять ключ списка ошибок и ключ блокировки от многостраничного адаптера.
     * 2) Сохранить их в trait, чтобы ошибки всех листов попадали в один отчёт запуска.
     */
    public function __construct(
        string $cacheKey,
        string $lockKey,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * Подготовить сериализуемое состояние листа двигателей для очереди.
     *
     * Шаги:
     * 1) Сохранить только ключ списка ошибок.
     * 2) Сохранить только ключ блокировки списка ошибок.
     * 3) Оставить runtime-зависимости для ленивого получения из контейнера.
     *
     * @return array{cacheKey: string, lockKey: string}
     */
    public function __serialize(): array
    {
        return [
            'cacheKey' => $this->cacheKey,
            'lockKey' => $this->lockKey,
        ];
    }

    /**
     * Восстановить состояние листа двигателей после очереди.
     *
     * Шаги:
     * 1) Вернуть ключ списка ошибок из сериализованных данных.
     * 2) Вернуть ключ блокировки списка ошибок.
     * 3) Runtime-зависимости остаются ленивыми и не входят в состояние листа.
     *
     * @param  array{cacheKey: string, lockKey: string}  $data
     */
    public function __unserialize(array $data): void
    {
        $this->cacheKey = (string) $data['cacheKey'];
        $this->lockKey = (string) $data['lockKey'];
    }

    /**
     * Обработать пачку строк основного листа двигателей.
     *
     * Шаги:
     * 1) Лениво получить маппер строки и сервис сохранения после восстановления queued job.
     * 2) Для каждой строки собрать DTO и сохранить двигатель внутри транзакции.
     * 3) Записать ошибку в cache-отчёт, если строка не прошла import validation.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            try {
                DB::transaction(function () use ($rowMapper, $rowValues, $service): void {
                    $engineRow = $rowMapper->map($rowValues);
                    $service->upsertFromRow($engineRow);
                });
            } catch (ImportRowValidationException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Двигатели',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    )
                );
            }
        }
    }

    /**
     * Вернуть номер первой строки с данными на основном листе двигателей.
     *
     * Шаги:
     * 1) Пропустить строку заголовков Excel.
     * 2) Начать обработку со второй строки.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Получить сервис сохранения двигателя из строки листа.
     *
     * Шаги:
     * 1) Резолвить сервис из контейнера во время обработки queued job.
     * 2) Не хранить dependency graph в сериализованном Excel-адаптере.
     */
    private function service(): UpsertEngineFromRowServiceInterface
    {
        return $this->service ??= app(UpsertEngineFromRowServiceInterface::class);
    }

    /**
     * Получить маппер основного листа двигателей.
     *
     * Шаги:
     * 1) Резолвить маппер из контейнера во время обработки queued job.
     * 2) Использовать его для перевода сырых ячеек в DTO строки.
     */
    private function rowMapper(): EngineMainSheetRowMapper
    {
        return $this->rowMapper ??= app(EngineMainSheetRowMapper::class);
    }
}
