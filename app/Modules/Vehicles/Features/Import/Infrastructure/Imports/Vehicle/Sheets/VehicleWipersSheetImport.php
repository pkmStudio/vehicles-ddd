<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleWiperSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Обрабатывает лист «дворники» и сохраняет спецификации дворников для ТС.
 */
final class VehicleWipersSheetImport implements ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    /**
     * Получить ключи отчёта ошибок для листа дворников.
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
     * Обработать пачку строк листа дворников.
     *
     * Шаги:
     * 1) Лениво получить маппер строки и сервис сохранения спецификации.
     * 2) Обрезать пробелы в строковых ячейках, собрать DTO и сохранить спецификацию в транзакции.
     * 3) Записать ошибки в cache-отчёт для невалидных строк или ошибок сборки details.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $upsertWiperSpec = $this->upsertWiperSpec();
        $trimString = fn ($value) => is_string($value) ? trim($value) : $value;

        foreach ($collection as $indexRow => $row) {
            $row = $row->map($trimString);
            $rowValues = $row->toArray();
            try {
                $vehicleRow = $rowMapper->map($rowValues);

                $upsertWiperSpecCallback = fn () => $upsertWiperSpec->upsertFromRow($vehicleRow);

                DB::transaction($upsertWiperSpecCallback);
            } catch (ImportRowValidationException|DetailsDataBuildException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Дворники',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    ),
                );
            }
        }
    }

    /**
     * Вернуть номер первой строки с данными на листе дворников.
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
     * Получить маппер листа дворников.
     *
     * Шаги:
     * 1) Резолвить маппер из контейнера во время обработки queued job.
     * 2) Использовать его для сборки DTO строки и details.
     */
    private function rowMapper(): VehicleWiperSheetRowMapper
    {
        return app(VehicleWiperSheetRowMapper::class);
    }

    /**
     * Получить сервис сохранения спецификации дворников.
     *
     * Шаги:
     * 1) Резолвить сервис из контейнера во время обработки queued job.
     * 2) Не хранить dependency graph в сериализованном Excel-адаптере.
     */
    private function upsertWiperSpec(): VehicleWiperSpecificationImportServiceInterface
    {
        return app(VehicleWiperSpecificationImportServiceInterface::class);
    }
}
