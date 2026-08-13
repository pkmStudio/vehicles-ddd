<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер листа «свечи зажигания» (механика): пропускает пустые строки, собирает details
 * из строки по шаблону и на каждую строку зовёт сценарий записи спецификации.
 */
final class EngineSparkPlugsSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 9;

    private ?UpsertEngineSparkPlugSpecServiceInterface $service = null;

    private ?TemplatesClientInterface $templates = null;

    /**
     * Получить ключи отчёта ошибок для листа свечей зажигания.
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
     * Подготовить сериализуемое состояние листа свечей для очереди.
     *
     * Шаги:
     * 1) Сохранить только ключ списка ошибок.
     * 2) Сохранить только ключ блокировки списка ошибок.
     * 3) Не сериализовать сервисы и клиент Templates.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'cacheKey' => $this->cacheKey,
            'lockKey' => $this->lockKey,
        ];
    }

    /**
     * Восстановить состояние листа свечей после очереди.
     *
     * Шаги:
     * 1) Вернуть ключ списка ошибок из сериализованных данных.
     * 2) Вернуть ключ блокировки списка ошибок.
     * 3) Сбросить runtime-зависимости для последующего резолва из контейнера.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->cacheKey = (string) $data['cacheKey'];
        $this->lockKey = (string) $data['lockKey'];
        $this->service = null;
        $this->templates = null;
    }

    /**
     * Обработать пачку строк листа свечей зажигания.
     *
     * Шаги:
     * 1) Пропустить строки без eng_id или без заполненных колонок спецификации.
     * 2) Собрать details свечей через Templates и сохранить спецификацию двигателя в транзакции.
     * 3) Записать ошибки в cache-отчёт, если двигатель не найден или details не собираются.
     *
     * @throws LockTimeoutException
     */
    public function collection(Collection $collection): void
    {
        $templates = $this->templates();
        $service = $this->service();

        foreach ($collection as $indexRow => $row) {
            $engId = $row[0] ?? null;

            if (! $engId) {
                continue;
            }

            $rowValues = $row->toArray();
            $specValues = array_slice($rowValues, self::SPEC_START_COLUMN);
            $isFilledSpecValue = fn ($value) => $value !== null && $value !== '';
            $filledSpecValues = array_filter($specValues, $isFilledSpecValue);

            if (empty($filledSpecValues)) {
                continue;
            }

            try {
                DB::transaction(function () use ($engId, $indexRow, $rowValues, $service, $templates): void {
                    $details = $templates->buildVehicleDetails(
                        template: DetailTemplateEnum::SPARK_PLUGS,
                        row: $rowValues,
                        startIndex: self::SPEC_START_COLUMN,
                    );

                    $spec = $service->upsertByEngine((int) $engId, $details);

                    if (! $spec) {
                        $this->onFailure(new Failure(
                            row: $indexRow + $this->startRow(),
                            attribute: 'eng_id',
                            errors: ["Двигатель с eng_id {$engId} не найден."],
                            values: $rowValues,
                        ));
                    }
                });
            } catch (DetailsDataBuildException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Свечи зажигания',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    )
                );
            }
        }
    }

    /**
     * Вернуть номер первой строки с данными на листе свечей.
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
     * Получить сервис сохранения свечей двигателя.
     *
     * Шаги:
     * 1) Резолвить сервис из контейнера во время обработки queued job.
     * 2) Не хранить dependency graph в сериализованном Excel-адаптере.
     */
    private function service(): UpsertEngineSparkPlugSpecServiceInterface
    {
        return $this->service ??= app(UpsertEngineSparkPlugSpecServiceInterface::class);
    }

    /**
     * Получить клиент Templates для сборки details.
     *
     * Шаги:
     * 1) Резолвить клиент из контейнера во время обработки queued job.
     * 2) Использовать его для сборки typed details свечей из колонок листа.
     */
    private function templates(): TemplatesClientInterface
    {
        return $this->templates ??= app(TemplatesClientInterface::class);
    }
}
